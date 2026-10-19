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
for path in ["operacoes", "os", "clientes", "produtos", "servicos", "mapos/configurar", "comunicacao", "comunicacao/tentativas/0"]:
    response = client.open(base + "/index.php/" + path, timeout=30)
    body = response.read().decode()
    assert response.status == 200 and all(error not in body for error in ["A PHP Error was encountered", "Database Error", "An uncaught Exception was encountered", "Fatal error:"]), path
    assert "login" not in response.url, path
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
print("HTTP checks passed: authentication, refreshed CSRF, eight application pages and protected paths.")
