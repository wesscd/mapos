# Melhorias do fork

Revisão documental: **22/10/2026**. Consulte a [galeria de telas](GALERIA.md) e a [apresentação do fork](../README.md).

## Interface e identidade visual

O tema selecionável Barão Tech aplica a identidade da marca ao administrativo, aos logins e à área do cliente. Foram padronizados componentes de OS, Kanban, cadastros, equipamentos, produtos e serviços. A impressão mantém seu layout próprio. A edição e a consulta de OS usam seis abas; os detalhes das validações, vistorias e entregas estão em [EQUIPAMENTOS.md](EQUIPAMENTOS.md).

## Notificações centralizadas

Modelos de WhatsApp e e-mail, variáveis, prévias e regras automáticas ficam em **Configurações → Notificações**. A Central de Comunicação mantém a operação dos canais e o histórico. Os antigos editores concorrentes foram retirados. O envio manual pelo serviço Baileys exige conferência do texto e destinatário e usa a mesma fila com controle de duplicação. As regras completas estão em [COMUNICACAO.md](COMUNICACAO.md).

## Equipamentos e entrega

Cadastro vinculado ao cliente, recebimento físico explícito e vistorias de entrada e saída preservam condições, acessórios e evidências. Fotos são categorizadas por perspectiva e acessadas por rotas autenticadas. A retirada registra quem recebeu, aceite e comprovante, sem transformar faturamento em confirmação de entrega.

## Kanban

O quadro organiza as OS por andamento e oferece filtros e movimentação por arraste ou seletor. Aprovação pendente e retirada têm regras próprias; mover o cartão não registra aceite do cliente. Consulte [KANBAN.md](KANBAN.md).


Para conexão WhatsApp/Baileys, Gmail e notificações automáticas, consulte
[COMUNICACAO.md](COMUNICACAO.md).

## Operação

O menu **Operações** reúne OS em aberto, prazos vencidos, conclusões por responsável
e tempo médio entre abertura e conclusão. O período pode ser de 7, 30 ou 90 dias.
Os valores financeiros exigem também a permissão de visualizar lançamentos.
O relatório financeiro mostra as últimas 100 OS faturadas no período.

As datas reais de conclusão e os custos das peças começam a ser registrados nesta
atualização. Registros antigos não recebem datas ou custos inventados. O saldo
mostrado desconta apenas o custo das peças da receita líquida: não representa
lucro contábil e não inclui mão de obra, impostos nem despesas operacionais.

## Acompanhamento das OS

Nas telas de edição e visualização há checklist técnico, prazo, responsável,
histórico dos últimos 100 eventos e versões de orçamento.

1. Adicione os produtos e serviços e confira o desconto.
2. Clique em **Solicitar aprovação do orçamento atual**.
3. O cliente acessa sua área, abre a OS e aprova ou recusa a versão apresentada.
4. A resposta fica registrada no histórico. O responsável continua controlando o status técnico da OS.

Produtos, serviços e descontos alterados tornam necessária uma nova aprovação.
Uma versão substituída não pode ser aceita. A resposta é restrita ao cliente da OS.
O envio da solicitação disponibiliza o orçamento no portal; notificações por
e-mail dependem da configuração SMTP. Não há integração com o UltraITSM.

As alterações de produtos, serviços, descontos, cancelamento, reabertura e
faturamento usam um serviço de transações compartilhado entre navegador e API.
Não são aceitas peças em quantidade fracionária nem movimentações que deixem
o estoque insuficiente. A devolução usa a quantidade armazenada no banco.
O faturamento calcula o valor a partir dos itens, vincula o lançamento à OS e
recusa tentativas repetidas. OS faturadas não permitem alterações de itens.

## Publicação e recuperação

A atualização de arquivos pelo navegador fica desativada. O fork mantém sua
própria branch de implantação. As atualizações do projeto original devem ser
integradas por Git e passar pelos testes antes da publicação.

```bash
cd /opt/mapos
bash scripts/backup.sh
bash scripts/verify-backup.sh /opt/mapos-private/backups/DIRETORIO
bash scripts/deploy.sh codex/deployment-development
```

O backup contém banco, configuração, anexos, uploads, revisão Git e checksums,
em diretório restrito fora da raiz pública. A verificação restaura o SQL em um
banco temporário e extrai os arquivos em um diretório isolado. Não substitui
o banco nem a configuração em uso.

O servidor agenda backup e verificação diários em `/etc/cron.d/mapos-backup`.
As cópias permanecem no próprio servidor. Uma segunda cópia externa depende da
escolha de um destino de armazenamento; ela não foi configurada.

Para recuperação real, interrompa as gravações, confira os checksums, recupere
o banco e os arquivos do mesmo backup e use a revisão registrada em `revision`.
Execute migrations apenas para avançar à revisão pretendida. Não execute `down`
em produção como forma de recuperação, pois migrations reversas podem apagar dados.

## Validação

```powershell
.\scripts\test.ps1
python tests/http_smoke.py http://localhost:8000 "$env:LOCALAPPDATA\MapOSDeployment\local-admin.json"
```

Os testes de integração reinicializam exclusivamente `mapos_fork_test`, criado
para esse fim. O banco da aplicação não é reinicializado. O GitHub Actions
repete a preparação, os testes e as verificações HTTP em containers descartáveis.
O comando `tools migrate` funciona sem dependências de desenvolvimento; seeders
continuam restritos a desenvolvimento.

A proteção CSRF permanece ativa. O token é entregue no HTML e atualizado pelo
cabeçalho `X-CSRF-Token`, mantendo os cookies com `HttpOnly`.
O cache do sanitizador HTML usa `application/cache/htmlpurifier`, sem escrita no vendor.

O framework CodeIgniter 3 e os componentes visuais legados foram preservados.
As melhorias de interface são incrementais e mantêm os fluxos existentes.
