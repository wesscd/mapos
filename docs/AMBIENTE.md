# Ambiente do fork

Base: MapOS 4.55.4. Os ambientes usam PHP 8.4, Composer 2, MySQL 8.4 e Nginx.

## Desenvolvimento no Windows

Pré-requisito: Docker Desktop em execução, com containers Linux.
O código local é montado no container: alterações em PHP, CSS e JavaScript aparecem sem reconstruir a imagem.

```powershell
.\scripts\dev.ps1 up
.\scripts\dev.ps1 status
.\scripts\dev.ps1 logs
.\scripts\dev.ps1 php -v
.\scripts\dev.ps1 composer validate --no-check-publish
.\scripts\dev.ps1 composer format
.\scripts\dev.ps1 php index.php tools migrate
.\scripts\dev.ps1 stop
```

URL: http://localhost:8000/. A porta fica restrita ao computador local.
O banco não publica portas; seus dados ficam no volume `mapos-dev_db`.
Parar os containers preserva os dados. Não use `down -v` se quiser preservar o banco.

Os acessos administrativos criados nesta instalação estão em
`%LOCALAPPDATA%\MapOSDeployment\ACESSOS.txt`, fora do repositório e com acesso restrito ao usuário Windows.

As senhas de banco ficam em `.env.local`, ignorado pelo Git:

```dotenv
MAPOS_HTTP_PORT=8000
MAPOS_DB_PASSWORD=defina-uma-senha-aleatoria
MAPOS_DB_ROOT_PASSWORD=defina-outra-senha-aleatoria
```

A configuração da aplicação fica em `application/.env`, também ignorada pelo Git.
Não copie esse arquivo de produção para desenvolvimento.

### Instalação inicial em outro computador

Depois de criar `.env.local`, execute `dev.ps1 up`. Para inicializar um banco vazio,
envie um JSON pela entrada padrão de `docker compose --env-file .env.local -f compose.dev.yaml exec -T php php scripts/install.php`.
Campos: `dbpassword`, `full_name`, `email`, `password` e `base_url`.
Os padrões são host `db`, usuário `mapos` e banco `mapos`.
Use `http://localhost:8000/` como URL e uma senha exclusiva para o administrador.
O instalador recusa banco não vazio ou aplicação já configurada.
Para desenvolvimento, ajuste `APP_ENVIRONMENT=development` em `application/.env`.
O assistente web `/install/` permanece bloqueado no Nginx.

## Servidor

Código: `/opt/mapos-baraotech`, também acessível pelo link `/opt/mapos`. Composição: `compose.prod.yaml`, projeto Docker `mapos-baraotech`.
URL: https://os.baraotech.online/.

```bash
cd /opt/mapos
docker compose --env-file .env.local -f compose.prod.yaml ps
docker compose --env-file .env.local -f compose.prod.yaml logs --tail 100
docker compose --env-file .env.local -f compose.prod.yaml exec -T php composer check-platform-reqs --no-dev
```

O MySQL e o PHP usam rede própria. Apenas o Nginx do MapOS participa também da rede
externa `ultra-itsm-itsm_ultra_public`, com alias `barao-os-web`.
O proxy existente atende o domínio por um virtual host separado, sem publicar portas adicionais.
As credenciais ficam fora do Git, na pasta privada de implantação. O projeto e o alias são definidos por `MAPOS_PROJECT_NAME` e `MAPOS_PROXY_ALIAS` em `.env.local`.

O proxy do UltraITSM inclui `/etc/nginx/certs/mapos.conf` a partir da montagem TLS existente.
Esse include foi acrescentado à configuração e à imagem do proxy da release instalada.
Ao implantar uma nova release do UltraITSM, preserve esse include no template Nginx.
O backup anterior do template fica em `/root/mapos-proxy-backup/`.

O certificado do MapOS é independente do certificado do UltraITSM. O Certbot usa
`/opt/mapos-private/acme`, com validação HTTP pelo webroot `/opt/mapos-baraotech`.
O arquivo `/etc/cron.d/mapos` agenda a renovação e o processamento da fila de e-mails.
O hook `/usr/local/sbin/mapos-renew-certificate` copia o certificado renovado para
a montagem TLS do proxy e recarrega o Nginx após validar sua configuração.
Configure o SMTP em Configurações > Sistema > E-mail antes de usar notificações.
O acesso administrativo inicial de produção está em `/opt/mapos-baraotech-private/admin.json`. A instalação de 22/10/2026 usa uma base nova, sem importar cadastros, OS, anexos, credenciais SMTP ou sessão do WhatsApp da instalação anterior.

Antes de publicar melhorias, valide localmente e faça backup do banco, `application/.env`
e diretórios de anexos e uploads. Mudanças de schema devem usar migrations.

## Identidade visual Barão Tech

Em Configurações > Sistema > Tema do Sistema, selecione **Barão Tech · verde escuro**. O tema usa as cores e o logotipo da landing page e se aplica ao painel administrativo e à área do cliente, incluindo os logins. A seleção pode ser revertida aos temas anteriores sem mudar os dados. A folha de estilo é restrita à tela; as impressões mantêm seu layout documental.
