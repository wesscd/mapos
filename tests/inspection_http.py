"""Mutating end-to-end checks, exclusively for a loopback application and synthetic fixture."""
import binascii
import datetime
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
assert urllib.parse.urlsplit(base).hostname in ('localhost', '127.0.0.1'), 'Use a loopback application only'
credentials = json.loads(Path(sys.argv[2]).read_text())
customer = json.loads(Path(sys.argv[3]).read_text(encoding='utf-8-sig'))['customer']
jar = http.cookiejar.CookieJar()
client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))


def get(path):
    return client.open(base + path, timeout=30)


def post(path, fields, files=None):
    fields = dict(fields, MAPOS_CSRF_TOKEN=next(c.value for c in jar if c.name == 'MAPOS_CSRF_COOKIE'))
    headers = {}
    if files:
        boundary = 'mapos-' + uuid.uuid4().hex
        chunks = []
        for name, value in fields.items():
            chunks.append(('--' + boundary + '\r\nContent-Disposition: form-data; name="' + name + '"\r\n\r\n' + str(value) + '\r\n').encode())
        for name, data in files.items():
            chunks.append(('--' + boundary + '\r\nContent-Disposition: form-data; name="' + name + '"; filename="demonstracao.png"\r\nContent-Type: image/png\r\n\r\n').encode() + data + b'\r\n')
        data = b''.join(chunks) + ('--' + boundary + '--\r\n').encode()
        headers['Content-Type'] = 'multipart/form-data; boundary=' + boundary
    else:
        data = urllib.parse.urlencode(fields).encode()
    return client.open(urllib.request.Request(base + path, data=data, headers=headers), timeout=90)


get('login').read()
assert json.loads(post('login/verificarLogin', {'email': credentials['email'], 'senha': credentials['password']}).read())['result']
response = post('equipamentos/salvar', {'clientes_id': customer, 'equipamento': 'Notebook demonstracao - vistoria', 'marca': 'Exemplo', 'modelo': 'Pro 14', 'num_serie': 'DEMO-VISTORIA', 'cor': 'Prata', 'descricao': 'Demonstracao HTTP local'})
equipment = re.search(r'equipamentos/visualizar/(\d+)', response.url).group(1)
response.read()


def chunk(kind, data):
    return struct.pack('!I', len(data)) + kind + data + struct.pack('!I', binascii.crc32(kind + data) & 0xffffffff)


png = b'\x89PNG\r\n\x1a\n' + chunk(b'IHDR', struct.pack('!2I5B', 12, 8, 8, 2, 0, 0, 0)) + chunk(b'IDAT', zlib.compress((b'\0' + b'\x80\xa0\xc0' * 12) * 8)) + chunk(b'IEND', b'')
fields = {'cliente': 'Demonstracao HTTP - vistoria', 'clientes_id': customer, 'usuarios_id': '1', 'dataInicial': datetime.date.today().strftime('%d/%m/%Y'), 'dataFinal': (datetime.date.today() + datetime.timedelta(days=3)).strftime('%d/%m/%Y'), 'status': 'Aberto', 'garantia': '0', 'descricaoProduto': 'Notebook demonstracao - vistoria', 'defeito': 'Teste de envio de imagens', 'observacoes': 'Demonstracao local. Imagens artificiais de teste.', 'laudoTecnico': '', 'equipamento_id': equipment, 'vistoria_estado': 'marcas', 'vistoria_condicoes': 'Demonstracao: risco na tampa.', 'vistoria_acessorios': 'Carregador', 'vistoria_receber': '1', 'vistoria_entregue_por': 'Cliente de demonstracao'}
angles = ['frente', 'verso', 'aberto', 'fechado', 'teclado']
for angle in angles:
    fields['vistoria_obs_' + angle] = 'Imagem de teste - ' + angle
response = post('os/adicionar', fields, {'vistoria_foto_' + angle: png for angle in angles})
order = re.search(r'os/editar/(\d+)', response.url).group(1)
body = response.read().decode()
assert all(error not in body for error in ['Database Error', 'An uncaught Exception', 'A PHP Error was encountered'])
photos = set(re.findall(r'equipamentos/vistoriaFoto/(\d+)', body))
assert len(photos) == 5
for photo in photos:
    response = get('equipamentos/vistoriaFoto/' + photo)
    assert response.headers.get_content_type() == 'image/jpeg' and 'no-store' in response.headers['Cache-Control'] and response.read().startswith(b'\xff\xd8')
private = urllib.request.urlopen(base + 'equipamentos/vistoriaFoto/' + next(iter(photos)), timeout=30)
assert 'login' in private.url
receipt = re.search(r'equipamentos/comprovante/(\d+)', body).group(1)
printed = get('equipamentos/comprovante/' + receipt).read().decode()
assert all('Imagem de teste - ' + angle in printed for angle in angles)
response = post('kanban/mover/' + order, {'status_anterior': 'Aberto', 'status': 'Em Andamento'})
board = response.read().decode()
active = board.split('data-kanban-target="Em Andamento"', 1)[1].split('</section>', 1)[0]
assert 'data-kanban-card="' + order + '"' in active
print(json.dumps({'result': 'HTTP upload, five angles, physical receipt, private photos and Kanban movement verified', 'equipment': equipment, 'order': order, 'receipt': receipt}))
