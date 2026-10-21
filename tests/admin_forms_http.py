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
# Generate a valid, unique synthetic CPF; no real customer information is used.
numbers = [int(c, 16) % 10 for c in key[:9]]
for count in (9, 10):
    check = sum(numbers[i] * (count + 1 - i) for i in range(count)) * 10 % 11
    numbers.append(0 if check == 10 else check)
cpf = ''.join(map(str, numbers))
customer = {'nomeCliente': 'Piloto visual ' + key + ' \"Cliente\" & teste', 'documento': cpf, 'email': key + '@example.test', 'contato': 'Contato sintético', 'telefone': '1133334444', 'celular': '11999990000', 'rua': 'Rua de teste', 'numero': '10', 'complemento': 'Sala teste', 'bairro': 'Centro', 'cidade': 'Cidade teste', 'estado': 'SP', 'cep': '', 'senha': '', 'fornecedor': '1'}
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

product = {'descricao': 'Produto visual ' + key + ' \"Notebook\" & teste', 'codDeBarra': key, 'unidade': 'UNID', 'precoCompra': '100.00', 'precoVenda': '137.50', 'estoque': '3', 'estoqueMinimo': '1', 'entrada': '1'}
assert 'Produto adicionado com sucesso' in post('produtos/adicionar', product)
listing = get('produtos?pesquisa=' + key)
product_id = re.search(r'produtos/editar/(\d+)', listing).group(1)
path = 'produtos/editar/' + product_id
fields = Fields(get(path))
assert fields.values['descricao'] == product['descricao'] and fields.values['unidade'] == 'UNID' and float(fields.values['precoVenda']) == 137.5 and 'entrada' in fields.checked and 'saida' not in fields.checked
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

service = {'nome': 'Serviço ' + key + ' \"PC\" & teste', 'preco': '85.50', 'descricao': 'Análise e limpeza de teste'}
assert 'Serviço adicionado com sucesso' in post('servicos/adicionar', service)
listing = get('servicos?pesquisa=' + key)
assert '&amp;quot;' not in listing and '&amp;amp;' not in listing, 'Legacy escaped text is not escaped a second time in catalog lists'
service_id = re.search(r'servicos/editar/(\d+)', listing).group(1)
path = 'servicos/editar/' + service_id
fields = Fields(get(path))
assert fields.values['nome'] == service['nome'] and float(fields.values['preco']) == 85.5 and fields.values['descricao'] == service['descricao']
service.update(idServicos=service_id, preco='99.90', descricao='Descrição atualizada')
assert 'Serviço editado com sucesso' in post(path, service)
fields = Fields(get(path))
assert float(fields.values['preco']) == 99.9 and fields.values['descricao'] == 'Descrição atualizada'
invalid = dict(service, nome='', preco='101.25', descricao='Descrição após erro')
fields = Fields(post(path, invalid))
assert fields.values['nome'] == '' and fields.values['preco'] == '101.25' and fields.values['descricao'] == 'Descrição após erro'
assert float(Fields(get(path)).values['preco']) == 99.9
for route in ['produtos', 'servicos']:
    assert 'Nenhum resultado para esta busca.' in get(route + '?pesquisa=catalog-no-match-' + key)
assert 'R$ 149,90' in get('produtos/visualizar/' + product_id)
print('Catalog checks passed: service create/edit, retained textarea after validation errors, saved prices, empty search results and formatted product detail.')
print('Synthetic service fixture ID: ' + service_id)

# Exercise encoded historical data without changing any real record.
from html import escape
legacy_product = dict(product, descricao=escape(product['descricao'], quote=True))
assert 'Produto editado com sucesso' in post('produtos/editar/' + product_id, legacy_product)
assert Fields(get('produtos/editar/' + product_id)).values['descricao'] == product['descricao'], 'Legacy entities render as plain characters in edit fields'
assert '&amp;quot;' not in get('produtos/visualizar/' + product_id), 'Legacy entities are not visibly escaped twice in product details'
assert 'Produto editado com sucesso' in post('produtos/editar/' + product_id, product)
long_service = dict(service, nome='N' * 46)
assert 'ui-notice-error' in post('servicos/editar/' + service_id, long_service), 'Names exceeding the actual service storage limit are rejected instead of silently truncated'
assert Fields(get('servicos/editar/' + service_id)).values['nome'] == service['nome']
print('Text checks passed: raw special characters, safely displayed legacy entities and explicit storage-length validation.')
