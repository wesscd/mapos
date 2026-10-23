"""End-to-end checklist checks with synthetic records, restricted to loopback."""
import binascii
import datetime
import html
import http.cookiejar
import json
import re
import struct
import sys
import urllib.parse
import urllib.request
import uuid
import zlib
from pathlib import Path

base = sys.argv[1].rstrip('/') + '/index.php/'
assert urllib.parse.urlsplit(base).hostname in ('localhost', '127.0.0.1'), 'Checklist fixtures require loopback'
credentials = json.loads(Path(sys.argv[2]).read_text())
fixture = json.loads(Path(sys.argv[3]).read_text(encoding='utf-8-sig'))
jar = http.cookiejar.CookieJar()
client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))


def get(path):
    return client.open(base + path, timeout=90)


def post(path, fields, photo=None):
    fields = dict(fields, MAPOS_CSRF_TOKEN=next(c.value for c in jar if c.name == 'MAPOS_CSRF_COOKIE'))
    headers = {}
    if photo:
        boundary = 'checklist-' + uuid.uuid4().hex
        parts = [('--' + boundary + '\r\nContent-Disposition: form-data; name="' + key + '"\r\n\r\n' + str(value) + '\r\n').encode() for key, value in fields.items()]
        parts.append(('--' + boundary + '\r\nContent-Disposition: form-data; name="checklist_foto"; filename="teste.PNG"\r\nContent-Type: image/png\r\n\r\n').encode() + photo + b'\r\n')
        data = b''.join(parts) + ('--' + boundary + '--\r\n').encode()
        headers['Content-Type'] = 'multipart/form-data; boundary=' + boundary
    else:
        data = urllib.parse.urlencode(fields).encode()
    return client.open(urllib.request.Request(base + path, data=data, headers=headers), timeout=90)


def item_forms(body):
    result = []
    for form in re.findall(r'<form\b[^>]*>(.*?)</form>', body, re.S):
        fields = dict((name, html.unescape(value)) for name, value in re.findall(r'<input[^>]*name="([^"]+)"[^>]*value="([^"]*)"', form))
        if fields.get('acao') == 'item':
            result.append((fields, form))
    return result


get('login').read()
assert json.loads(post('login/verificarLogin', {'email': credentials['email'], 'senha': credentials['password']}).read())['result']
response = post('equipamentos/salvar', {'clientes_id': fixture['customer'], 'equipamento': 'Notebook demonstracao - checklist', 'marca': 'Exemplo', 'modelo': 'Pro 14', 'num_serie': 'DEMO-CHECKLIST', 'checklist_tipo': 'notebook'})
equipment = re.search(r'equipamentos/visualizar/(\d+)', response.url).group(1)
response.read()
response = post('os/adicionar', {'cliente': 'Demonstracao HTTP - vistoria', 'clientes_id': fixture['customer'], 'usuarios_id': 1, 'dataInicial': datetime.date.today().strftime('%d/%m/%Y'), 'dataFinal': datetime.date.today().strftime('%d/%m/%Y'), 'status': 'Aberto', 'garantia': 0, 'descricaoProduto': 'Notebook demonstracao - checklist', 'defeito': 'Verificar estabilidade', 'equipamento_id': equipment})
order = re.search(r'os/editar/(\d+)', response.url).group(1)
body = response.read().decode()
assert 'Execução e conferência final' in body and 'Inspecionar ventoinhas' in body, 'Typed equipment must populate both checklist phases'
forms = item_forms(body)
assert len(forms) >= 10, 'OS must expose independent item forms'
item = forms[0][0]
action = 'checklists/salvar/' + order
blocked = post(action, dict(item, resultado='atencao', observacoes='')).read().decode()
assert 'Rascunho preservado para correção' in blocked, 'Invalid results must preserve an actionable draft'


def chunk(kind, data):
    return struct.pack('!I', len(data)) + kind + data + struct.pack('!I', binascii.crc32(kind + data) & 0xffffffff)


png = b'\x89PNG\r\n\x1a\n' + chunk(b'IHDR', struct.pack('!2I5B', 12, 8, 8, 2, 0, 0, 0)) + chunk(b'IDAT', zlib.compress((b'\0' + b'\x80\xa0\xc0' * 12) * 8)) + chunk(b'IEND', b'')
body = post(action, dict(item, resultado='conforme', observacoes='Teste de ventoinha concluido'), png).read().decode()
assert 'checklists/foto/' in body, 'Successful multipart upload must expose a private evidence link'
photo = re.search(r'checklists/foto/(\d+)', body).group(1)
image = get('checklists/foto/' + photo)
assert image.headers.get_content_type() == 'image/jpeg' and image.read().startswith(b'\xff\xd8') and 'no-store' in image.headers['Cache-Control']
anonymous = urllib.request.urlopen(base + 'checklists/foto/' + photo, timeout=30)
assert 'login' in anonymous.url, 'Checklist evidence must require authentication'
intervention = next(fields for fields, form in item_forms(body) if 'name="autorizacao"' in form)
blocked = post(action, dict(intervention, resultado='realizado', autorizacao='')).read().decode()
assert 'Rascunho preservado para correção' in blocked, 'Execution interventions require an authorization reference'
body = post(action, dict(intervention, resultado='realizado', autorizacao='Escopo aprovado de demonstracao', observacoes='Nota interna de demonstracao')).read().decode()
summary = get('checklists/resumo/' + order).read().decode()
assert 'Limpeza interna' in summary and 'Nota interna de demonstracao' not in summary and 'Escopo aprovado de demonstracao' not in summary, 'Procedure printout must separate completed work from private notes'
history = get('checklists/historico/' + intervention['item_id']).read().decode()
assert 'Nota interna de demonstracao' in history and 'Antes' in history and 'Depois' in history, 'Technical audit must retain both states'
models = get('checklists').read().decode()
assert 'Modelos de checklist técnico' in models and 'novos_execucao' in models, 'System configuration must expose editable models'
print(json.dumps({'result': 'Checklist HTTP snapshots, forms, authorization, private photo and summary verified', 'order': order, 'equipment': equipment}))
