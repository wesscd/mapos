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
        self.feed(body)
    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'input' and attrs.get('name'):
            self.values[attrs['name']] = attrs.get('value', '')
            if 'checked' in attrs:
                self.checked.add(attrs['name'])
        if tag == 'select' and attrs.get('name'):
            self.values[attrs['name']] = attrs.get('data-selected', '')

get('clientes/adicionar')
login = post('login/verificarLogin', {'email': credentials['email'], 'senha': credentials['password']})
assert json.loads(login).get('result') is True
key = uuid.uuid4().hex[:12]
# Generate a valid, unique synthetic CPF; no real customer information is used.
numbers = [int(c, 16) % 10 for c in key[:9]]
for count in (9, 10):
    check = sum(numbers[i] * (count + 1 - i) for i in range(count)) * 10 % 11
    numbers.append(0 if check == 10 else check)
cpf = ''.join(map(str, numbers))
customer = {'nomeCliente': 'Piloto visual ' + key, 'documento': cpf, 'email': key + '@example.test', 'contato': 'Contato sintético', 'telefone': '1133334444', 'celular': '11999990000', 'rua': 'Rua de teste', 'numero': '10', 'complemento': 'Sala teste', 'bairro': 'Centro', 'cidade': 'Cidade teste', 'estado': 'SP', 'cep': '', 'senha': '', 'fornecedor': '1'}
body = post('clientes/adicionar', customer)
assert 'Cliente adicionado com sucesso' in body, 'Synthetic customer creation succeeds'
listing = get('clientes?pesquisa=' + key)
customer_id = re.search(r'clientes/editar/(\d+)', listing).group(1)
path = 'clientes/editar/' + customer_id
fields = Fields(get(path))
assert fields.values['nomeCliente'] == customer['nomeCliente'] and fields.values['estado'] == 'SP' and 'fornecedor' in fields.checked
assert fields.values['senha'] == '', 'Edit forms never expose the stored password'
customer['idClientes'] = customer_id
customer['contato'] = 'Contato atualizado'
customer.pop('fornecedor')
body = post(path, customer)
assert 'Cliente editado com sucesso' in body
fields = Fields(get(path))
assert fields.values['contato'] == 'Contato atualizado' and 'fornecedor' not in fields.checked
invalid = dict(customer, nomeCliente='', cidade='Cidade após erro', estado='RJ', fornecedor='1')
fields = Fields(post(path, invalid))
assert fields.values['nomeCliente'] == '' and fields.values['cidade'] == 'Cidade após erro' and fields.values['estado'] == 'RJ' and 'fornecedor' in fields.checked, 'Invalid customer edits retain entered values instead of restoring database values'
assert Fields(get(path)).values['cidade'] == 'Cidade teste', 'Invalid edits never change the saved record'

product = {'descricao': 'Produto visual ' + key, 'codDeBarra': key, 'unidade': 'UNID', 'precoCompra': '100.00', 'precoVenda': '137.50', 'estoque': '3', 'estoqueMinimo': '1', 'entrada': '1'}
assert 'Produto adicionado com sucesso' in post('produtos/adicionar', product)
listing = get('produtos?pesquisa=' + key)
product_id = re.search(r'produtos/editar/(\d+)', listing).group(1)
path = 'produtos/editar/' + product_id
fields = Fields(get(path))
assert fields.values['unidade'] == 'UNID' and float(fields.values['precoVenda']) == 137.5 and 'entrada' in fields.checked and 'saida' not in fields.checked
product.update(idProdutos=product_id, precoVenda='149.90', saida='1')
product.pop('entrada')
assert 'Produto editado com sucesso' in post(path, product)
fields = Fields(get(path))
assert float(fields.values['precoVenda']) == 149.9 and 'saida' in fields.checked and 'entrada' not in fields.checked
invalid = dict(product, descricao='', precoVenda='151.25', unidade='CAIXA', entrada='1')
fields = Fields(post(path, invalid))
assert fields.values['descricao'] == '' and fields.values['precoVenda'] == '151.25' and fields.values['unidade'] == 'CAIXA' and 'entrada' in fields.checked
assert float(Fields(get(path)).values['precoVenda']) == 149.9
print('Admin form checks passed: synthetic create/edit, values after validation errors, checkbox states, unit selection, saved prices and blank password fields.')
print('Synthetic fixture IDs: customer=' + customer_id + ', product=' + product_id)
