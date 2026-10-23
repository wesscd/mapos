"""Read-only HTTP checks. Credentials are read from an untracked external JSON file."""
import http.cookiejar
import json
import sys
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

base = sys.argv[1].rstrip("/")
credentials = json.loads(Path(sys.argv[2]).read_text())
jar = http.cookiejar.CookieJar()
client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))

page = client.open(base + "/index.php/operacoes", timeout=30)
assert "login" in page.url, "Operations must require authentication"
token = next(cookie.value for cookie in jar if cookie.name == "MAPOS_CSRF_COOKIE")
data = urllib.parse.urlencode({"email": credentials["email"], "senha": credentials["password"], "MAPOS_CSRF_TOKEN": token}).encode()
response = client.open(base + "/index.php/login/verificarLogin", data, timeout=30)
assert json.loads(response.read()).get("result") is True, "Admin login failed"
assert response.headers.get("X-CSRF-Token"), "Responses must expose the refreshed CSRF token"
for path in ["mapos", "operacoes", "kanban", "os", "os/adicionar", "clientes", "produtos", "servicos", "mapos/configurar", "comunicacao", "comunicacao/tentativas/0", "equipamentos", "equipamentos/salvar"]:
    response = client.open(base + "/index.php/" + path, timeout=30)
    body = response.read().decode()
    assert response.status == 200 and all(error not in body for error in ["A PHP Error was encountered", "Database Error", "An uncaught Exception was encountered", "Fatal error:"]), path
    assert "login" not in response.url, path
    if path == "mapos":
        assert "id='source-calendar'" in body and 'class="cardHeader"' in body, "Home must render calendar and section headings"
    if path == "mapos/configurar":
        assert 'id="notification-config"' in body and 'id="notification-model-' in body and 'form="notification-model-' in body, "Notification settings must expose independent centralized editors"
        assert 'name="notifica_whats"' not in body and 'name="email_automatico"' not in body, "Retired legacy notification controls must not compete with centralized templates"
    if path == "comunicacao":
        assert 'mapos/configurar#menu3' in body and 'name="texto"' not in body and 'name="limite_lembretes"' not in body, "Communication center must link to the sole notification editor"
    if path == "os/adicionar":
        assert 'id="equipamento_id"' in body and "os-equipment-picker.js" in body, "New OS must expose the equipment picker"
        assert 'name="vistoria_estado"' in body and 'name="vistoria_foto_teclado"' in body and 'enctype="multipart/form-data"' in body, "New OS must expose intake condition and categorized photos"
response = client.open(base + "/index.php/equipamentos/porCliente/1", timeout=30)
equipment_data = json.loads(response.read())
assert isinstance(equipment_data["equipamentos"], list)
assert "no-store" in response.headers.get("Cache-Control", "")
assert all(set(item) == {"idEquipamentos", "equipamento", "modelo", "num_serie"} for item in equipment_data["equipamentos"])
for path in ["/application/.env", "/.env.local", "/.git/config", "/banco.sql", "/install/index.php", "/scripts/install.php", "/tests/run.php", "/.secrets/whatsapp-token", "/services/whatsapp/package.json", "/index.php/comunicacao_worker/process"]:
    try:
        response = client.open(base + path, timeout=15)
        code = response.status
    except urllib.error.HTTPError as error:
        code = error.code
    assert code in (403, 404), f"Sensitive path exposed: {path}"
try:
    client.open(base + "/index.php/acompanhamento/salvar/1", urllib.parse.urlencode({"acao": "checklist", "descricao": "invalid"}).encode(), timeout=15)
    raise AssertionError("POST without CSRF token was accepted")
except urllib.error.HTTPError as error:
    assert error.code == 403
print("HTTP checks passed: authentication, refreshed CSRF, thirteen application pages, inspection form, equipment picker endpoint and protected paths.")
