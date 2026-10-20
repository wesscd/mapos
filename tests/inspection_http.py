"""Mutating end-to-end checks, exclusively for a loopback application and synthetic fixture."""
import binascii
import datetime
import http.cookiejar
import urllib.error
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
fixture = json.loads(Path(sys.argv[3]).read_text(encoding='utf-8-sig'))
customer = fixture['customer']
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
assert json.loads(post('os/adicionarServico', {'idOsServico': order, 'idServico': fixture['service'], 'quantidade': '0.5', 'preco': '50'}).read())['result']
assert json.loads(post('os/adicionarProduto', {'idOsProduto': order, 'idProduto': fixture['product'], 'quantidade': '1', 'preco': '25'}).read())['result']
response = post('acompanhamento/salvar/' + order, {'acao': 'orcamento'})
assert 'Pendente' in response.read().decode()
assert json.loads(post('os/adicionarServico', {'idOsServico': order, 'idServico': fixture['service'], 'quantidade': '1', 'preco': '0'}).read())['result']
body = get('os/editar/' + order).read().decode()
assert 'Substituido' in body and 'os-budget' in body
assert re.search(r'TOTAL SERVIÇOS:</b></td>\s*<td[^>]*><b>R\$ 25,00', get('os/imprimir/' + order).read().decode()), 'Printing preserves the negotiated labor amount including free services'
try:
    post('os/adicionarServico', {'idOsServico': order, 'idServico': '999999999', 'quantidade': '1', 'preco': '50'})
    raise AssertionError('Unknown catalog item was accepted')
except urllib.error.HTTPError as error:
    assert error.code == 400 and 'Selecione um serviço válido' in json.loads(error.read())
response = post('kanban/mover/' + order, {'status_anterior': 'Aberto', 'status': 'Em Andamento'})
board = response.read().decode()
active = board.split('data-kanban-target="Em Andamento"', 1)[1].split('</section>', 1)[0]
assert 'data-kanban-card="' + order + '"' in active
response = post('kanban/mover/' + order, {'status_anterior': 'Em Andamento', 'status': 'Finalizado'})
response.read()
body = get('os/visualizar/' + order).read().decode()
link = re.search(r'name="vinculo_id" value="(\d+)"', body).group(1)
response = post('acompanhamento/salvar/' + order, {'acao': 'entrega', 'retirado_por': 'Cliente demonstracao', 'aceite_entrega': '1'})
assert 'Registre a vistoria de saída' in response.read().decode(), 'Cannot deliver without exit evidence'
fields = dict(vinculo_id=link, tipo='saida', vistoria_estado='marcas', vistoria_condicoes='Marcas originais preservadas. Teste de saída.', vistoria_acessorios='Carregador substituído', vistoria_conferencia='divergencia', vistoria_ressalvas='Substituição apresentada ao cliente; imagem artificial de teste.')
for angle in angles:
    fields['vistoria_obs_' + angle] = 'Foto de saída - ' + angle
response = post('equipamentos/vistoria/' + order, fields, {'vistoria_foto_' + angle: png for angle in angles})
body = response.read().decode()
exit_photos = set(re.findall(r'equipamentos/vistoriaFoto/(\d+)', body)) - photos
assert len(exit_photos) == 5 and 'Comparar fotos de entrada e saída por posição' in body
assert 'Divergência registrada' in body and 'Substituição apresentada ao cliente' in body
response = post('acompanhamento/salvar/' + order, {'acao': 'entrega', 'retirado_por': 'Cliente demonstracao'})
assert 'Confirme a conferência e o aceite' in response.read().decode(), 'Cannot infer acceptance from a recipient name'
response = post('acompanhamento/salvar/' + order, {'acao': 'entrega', 'retirado_por': 'Cliente demonstracao', 'aceite_entrega': '1'})
body = response.read().decode()
assert 'Entrega registrada em' in body and 'Entrega ao cliente registrada.' in body
exit_receipts = set(re.findall(r'equipamentos/comprovante/(\d+)', body)) - {receipt}
assert len(exit_receipts) == 1
printed = get('equipamentos/comprovante/' + exit_receipts.pop()).read().decode()
assert all(text in printed for text in ['Vistoria de entrada', 'Vistoria de saída', 'Conferência e aceite na retirada', 'Cliente demonstracao', 'Substituição apresentada ao cliente'])
assert all('Foto de saída - ' + angle in printed for angle in angles)
response = post('equipamentos/vistoria/' + order, dict(vinculo_id=link, tipo='saida', complementar='1'), {'vistoria_foto_frente': png})
assert 'Este atendimento não permite registrar vistoria.' in response.read().decode()
try:
    post('os/adicionarServico', {'idOsServico': order, 'idServico': fixture['service'], 'quantidade': '1', 'preco': '1'})
    raise AssertionError('Delivered work was modified')
except urllib.error.HTTPError as error:
    assert error.code == 400 and 'entrega já foi registrada' in json.loads(error.read())
response = post('os/faturar', {'os_id': order, 'descricao': 'Fatura demonstracao HTTP', 'valor': '1', 'vencimento': datetime.date.today().strftime('%d/%m/%Y'), 'cliente': 'Demonstracao HTTP', 'tipo': 'receita'})
assert json.loads(response.read())['result'], 'Delivered order can still be invoiced using its persisted item totals'
body = get('os/visualizar/' + order).read().decode()
assert 'Faturado' in body and 'Entrega registrada em' in body
print(json.dumps({'result': 'HTTP intake and exit uploads, comparison, acceptance guards, immutable delivery receipt and Kanban verified', 'equipment': equipment, 'order': order, 'receipt': receipt}))
