"""Create/edit regression checks on synthetic loopback-only records."""
import http.cookiejar
import json
import re
import sys
import urllib.parse
import urllib.request
import uuid
from html.parser import HTMLParser
from pathlib import Path

base = sys.argv[1].rstrip('/') + '/index.php/'
assert urllib.parse.urlsplit(base).hostname in ('localhost', '127.0.0.1'), 'Synthetic writes are loopback-only'
credentials = json.loads(Path(sys.argv[2]).read_text())
jar = http.cookiejar.CookieJar()
client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))

def get(path):
    return client.open(base + path, timeout=40).read().decode()

def post(path, fields):
    fields = dict(fields, MAPOS_CSRF_TOKEN=next(c.value for c in jar if c.name == 'MAPOS_CSRF_COOKIE'))
    return client.open(base + path, urllib.parse.urlencode(fields).encode(), timeout=40).read().decode()

class Fields(HTMLParser):
    def __init__(self, body):
        super().__init__()
        self.values = {}
        self.checked = set()
        self.textarea = None
        self.textarea_content = []
        self.feed(body)
    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'input' and attrs.get('name'):
            self.values[attrs['name']] = attrs.get('value', '')
            if 'checked' in attrs:
                self.checked.add(attrs['name'])
        if tag == 'textarea':
            self.textarea = attrs.get('name')
            self.textarea_content = []
        if tag == 'select' and attrs.get('name'):
            self.values[attrs['name']] = attrs.get('data-selected', '')

    def handle_data(self, data):
        if self.textarea:
            self.textarea_content.append(data)
    def handle_endtag(self, tag):
        if tag == 'textarea' and self.textarea:
            self.values[self.textarea] = ''.join(self.textarea_content)
            self.textarea = None

get('clientes/adicionar')
login = post('login/verificarLogin', {'email': credentials['email'], 'senha': credentials['password']})
assert json.loads(login).get('result') is True

key = uuid.uuid4().hex[:12]
numbers = [int(c, 16) % 10 for c in key[:9]]
for count in (9, 10):
    check = sum(numbers[i] * (count + 1 - i) for i in range(count)) * 10 % 11
    numbers.append(0 if check == 10 else check)
customer = {'nomeCliente': 'Equipamentos piloto ' + key, 'documento': ''.join(map(str, numbers)), 'email': key + '@example.test', 'telefone': '1133334444', 'celular': '11999990000', 'rua': 'Rua de teste', 'numero': '1', 'bairro': 'Centro', 'cidade': 'Cidade teste', 'estado': 'SP', 'senha': ''}
assert 'Cliente adicionado com sucesso' in post('clientes/adicionar', customer)
customer_id = re.search(r'clientes/editar/(\d+)', get('clientes?pesquisa=' + key)).group(1)
equipment = {'clientes_id': customer_id, 'equipamento': 'Notebook "' + key + '" & teste', 'marca': 'Marca piloto', 'modelo': 'Modelo piloto', 'num_serie': key, 'cor': 'Prata', 'descricao': 'Observação do cadastro'}
body = post('equipamentos/salvar', equipment)
assert 'Equipamento salvo.' in body and '&quot;' in body
equipment_id = re.search(r'equipamentos/salvar/(\d+)', body).group(1)
path = 'equipamentos/salvar/' + equipment_id
equipment['modelo'] = 'Modelo atualizado'
assert 'Equipamento salvo.' in post(path, equipment)
invalid = dict(equipment, equipamento='', cor='Azul após erro')
body = post(path, invalid)
fields = Fields(body)
assert 'Editar equipamento' in body and fields.values['cor'] == 'Azul após erro' and fields.values['equipamento'] == ''
assert Fields(get(path)).values['cor'] == 'Prata'
matches = json.loads(get('equipamentos/clientes?q=' + key))
assert any(str(c['idClientes']) == customer_id for c in matches['clientes'])
assert all(set(c) == {'idClientes', 'nomeCliente'} for c in matches['clientes'])
assert json.loads(get('equipamentos/clientes?q=semresultado' + key))['clientes'] == []
listing = get('equipamentos?cliente=' + customer_id + '&q=' + key)
assert 'equipamentos/visualizar/' + equipment_id in listing
assert equipment_id not in re.findall(r'equipamentos/visualizar/(\d+)', get('equipamentos?cliente=0&q=semresultado' + key))
detail = get('clientes/visualizar/' + customer_id)
assert 'equipamentos/visualizar/' + equipment_id in detail
assert 'equipamentos/salvar?cliente=' + customer_id in detail
assert 'Nenhuma venda cadastrada' in detail

# Upload a small synthetic PNG through the actual multipart form.
import struct, zlib
def chunk(kind, content):
    return struct.pack('>I', len(content)) + kind + content + struct.pack('>I', zlib.crc32(kind + content) & 0xffffffff)
png = b'\x89PNG\r\n\x1a\n' + chunk(b'IHDR', struct.pack('>IIBBBBB', 2, 2, 8, 2, 0, 0, 0)) + chunk(b'IDAT', zlib.compress(b'\x00' + b'\x60\x90\xc0'*2 + b'\x00' + b'\x60\x90\xc0'*2)) + chunk(b'IEND', b'')
boundary = 'MaposPilot' + key
token = next(c.value for c in jar if c.name == 'MAPOS_CSRF_COOKIE')
payload = ('--' + boundary + '\r\nContent-Disposition: form-data; name="MAPOS_CSRF_TOKEN"\r\n\r\n' + token + '\r\n--' + boundary + '\r\nContent-Disposition: form-data; name="foto"; filename="piloto.png"\r\nContent-Type: image/png\r\n\r\n').encode() + png + ('\r\n--' + boundary + '--\r\n').encode()
request = urllib.request.Request(base + 'equipamentos/adicionarFoto/' + equipment_id, payload, headers={'Content-Type': 'multipart/form-data; boundary=' + boundary})
body = client.open(request, timeout=40).read().decode()
assert 'Foto registrada.' in body
photo_id = re.search(r'equipamentos/foto/(\d+)', body).group(1)
photo = client.open(base + 'equipamentos/foto/' + photo_id, timeout=40)
assert photo.headers['Content-Type'].startswith('image/jpeg') and photo.read().startswith(b'\xff\xd8')
assert 'etiqueta' in get('equipamentos/etiqueta/' + equipment_id).lower()
print('Equipment checks passed: create/edit, validation preserves fields, customer lookup, scoped lists, customer links, PNG upload and QR label.')
print('Synthetic fixture IDs: customer=' + customer_id + ', equipment=' + equipment_id)
