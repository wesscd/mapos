# Comunicação automática — primeira etapa do plano

## Ativação

Acesse **Configurações → Comunicação** com permissão de configurar o sistema.
As automações começam desativadas e os clientes começam sem autorização de envio.
Não há importação nem disparo retroativo dos eventos anteriores à instalação.

1. Clique em **Conectar / gerar QR Code**. No WhatsApp do celular, abra
   **Aparelhos conectados → Conectar aparelho** e leia o QR Code da tela.
2. Para testar Gmail, informe o endereço e uma **senha de app Google de 16
   caracteres**, gerada em uma conta com verificação em duas etapas. Não utilize
   a senha normal da conta. Contas administradas podem restringir senhas de app.
3. Envie um teste para um contato sob seu controle. Testes explícitos ignoram a
   suspensão das automações e o horário, mas passam pela mesma fila de envio.
4. Confira o histórico, habilite os canais e ajuste horários, modelos e lembretes.
5. Consulte o nome ou código do cliente na central e registre os canais e a autorização.
   O próprio cliente também pode escolher ou suspender os canais pelo menu
   **Comunicação** de sua área. O telefone e o e-mail são os do cadastro.

O envio automático requer canal global habilitado, modelo ativo, destinatário
válido e autorização do cliente. Nenhum desses controles realiza fallback de
canal implicitamente: o cliente pode autorizar WhatsApp, e-mail, ambos ou nenhum.
Desativar globalmente pausa a fila; retirar a autorização cancela os avisos ainda
pendentes quando o worker os processa. Alterar o contato também invalida o destino
anterior. O horário usa America/Sao_Paulo e aceita intervalos dentro do mesmo dia.

## Eventos e lembretes

Os modelos padrão usam uma ação principal: consultar o atendimento, decidir sobre
o orçamento ou combinar a retirada. A abertura confirma o registro da solicitação,
sem afirmar que o equipamento foi recebido fisicamente. Avisos de retirada não
disputam atenção com um segundo convite para abrir o portal. O campo `{status}`
traduz as etapas internas para o cliente; por exemplo, uma OS faturada aparece
como `Valores do atendimento registrados`, sem confirmar pagamento ou entrega.
Se apenas o status foi alterado sem faturamento, o aviso informa que os dados do
atendimento foram atualizados, sem afirmar que existe um lançamento financeiro.

A revisão dos modelos atualiza somente textos idênticos aos padrões anteriores,
preservando personalizações e a opção de modelo ativo/inativo. Mensagens já
capturadas na fila e no histórico mantêm seu texto original.

São registrados avisos de abertura, orçamento disponível, mudança de status,
alteração de prazo, serviço finalizado e entrega. O orçamento exige login no
portal e só pode ser aprovado na versão atual. Uma solicitação substituída ou já
respondida deixa de ser elegível para envio. Modificar somente o responsável não
gera aviso de alteração de prazo.

**Finalizado** indica que o serviço está pronto para retirada. **Faturado** é uma
situação financeira e não confirma entrega. O botão **Confirmar entrega ao
cliente**, disponível em OS finalizada ou faturada para quem pode editar OS,
registra a data, interrompe avisos de retirada e gera o aviso de entrega.

Lembretes são gerados por orçamento pendente e por conclusão ainda sem entrega.
O intervalo é de 1 a 30 dias e o limite de 0 a 5 por ciclo, por canal. Não são
enviados vários lembretes vencidos de uma vez após uma interrupção. A primeira
execução pode gerar um lembrete de uma OS antiga ainda elegível, desde que os
canais e a preferência do cliente tenham sido habilitados explicitamente.

## Arquitetura e proteção dos dados

- PHP/CodeIgniter registra a alteração da OS, o evento e a notificação na mesma
  transação. A mesma biblioteca atende os fluxos administrativo, cliente e API.
- O worker CLI usa um lock MySQL por banco para evitar dois processadores
  simultâneos. Não existem endpoints públicos de processamento da fila.
- O serviço Node.js/Baileys está apenas na rede interna Docker, sem porta
  publicada no host. A API exige um token privado, nunca entregue ao navegador.
- A sessão usa SQLite com persistência de credenciais e chaves Signal, WAL e
  synchronous FULL. Não utiliza `useMultiFileAuthState` em produção.
- O pacote Baileys está fixado em **6.7.24**, versão sem sufixo de pré-lançamento
  no canal `legacy` consultado na implementação. O canal `latest` apontava para
  uma versão 7 candidata a lançamento; atualizações devem passar pelos testes.
- Credenciais e chaves ficam no volume `whatsapp_data`, executado pelo usuário
  `node`. O SMTP fica em `communication_private`, arquivo `smtp.json` com modo
  0600. Esse SMTP também é utilizado pelos envios manuais e pela fila legada.
- O token está em `.secrets/whatsapp-token`, ignorado no Git e bloqueado pelo
  Nginx. Não publique esse arquivo, o volume de sessão ou os backups.
- Mensagens recebidas não são armazenadas como uma caixa de entrada nem causam
  respostas automáticas. Esta entrega cobre notificações transacionais de OS.

Baileys utiliza o protocolo do WhatsApp Web e é um projeto independente do
WhatsApp. Desconexões exigem reconexão ou novo pareamento; o atendimento continua
funcionando. Referências: https://baileys.wiki/installation e
https://baileys.wiki/authentication/session-management.

## Estados e reenvio

O pareamento distingue leitura do QR, conclusão do vínculo e conexão pronta. O
reinício solicitado pelo WhatsApp após a leitura (`515`) é imediato e preserva as
credenciais, sem aplicar o atraso das falhas de rede. O QR segue as janelas da
biblioteca (primeiro: 60 segundos; seguintes: 20 segundos) e é ocultado ao expirar
ou ao começar a conclusão do pareamento. Uma tentativa não lida que expira para
e aguarda o administrador gerar um novo QR, em vez de reconectar indefinidamente.

O último código de desconexão, data e indicação de vínculo são armazenados no
SQLite para diagnóstico. Não são registrados QR Codes, chaves, mensagens ou
identificadores da conta nesses diagnósticos. O código aparece na central para
facilitar a investigação de uma eventual nova falha. Esse indicador de conexão
é separado do estado de entrega das mensagens abaixo.

O gateway persiste uma chave e o identificador da mensagem antes de enviá-la.
Repetir a mesma chave consulta seu resultado e não retransmite a mensagem.
Confirmações de entrega/leitura podem chegar depois e atualizar o histórico.
Há intervalo mínimo de 10 segundos entre envios de WhatsApp.

- `pendente`: aguarda horário, serviço ou nova tentativa segura.
- `enviando`: envio iniciado pelo worker.
- `enviado`: aceito pelo serviço; SMTP não confirma entrega final.
- `entregue` / `lido`: confirmação do WhatsApp, quando disponibilizada.
- `falhou`: número inválido ou limite de cinco tentativas seguras atingido.
- `incerto`: o envio pode ter ocorrido; não há reenvio automático.
- `cancelado`: cancelamento administrativo, autorização retirada ou evento antigo.

Erros antes da transmissão permitem tentativas com intervalo crescente.
Interrupção durante SMTP ou envio WhatsApp é tratada conservadoramente como
incerta. Confira no aparelho/provedor; não existe botão para reenviar mensagens
incertas. O administrador pode repetir mensagens com falha segura ou cancelar
mensagens pendentes. O histórico registra as tentativas sem registrar credenciais.

Os controles reduzem duplicações; não prometem entrega exatamente uma vez em
redes externas ou após recuperação de um backup antigo.

## Operação, testes e recuperação

No ambiente local, o worker fica fora da inicialização padrão para evitar disparos
acidentais durante o desenvolvimento. Para processar testes pelo formulário:

```powershell
docker compose --env-file .env.local -f compose.dev.yaml --profile communication up -d --build communication-worker
.\scripts\test.ps1
docker compose --env-file .env.local -f compose.dev.yaml exec -T whatsapp npm test
```

Em produção, `communication-worker` é iniciado pelo Compose. A publicação pausa
o worker durante as migrations e recria os serviços necessários. O Nginx bloqueia
`services/`, além dos diretórios privados já protegidos.

O backup inclui token, arquivo SMTP privado e snapshot SQLite consistente feito
pela API de backup do SQLite. A verificação restaura o MySQL em banco temporário,
valida o JSON SMTP e verifica a integridade SQLite em arquivo temporário.
Backups anteriores à comunicação continuam compatíveis com a verificação.

Na recuperação real, mantenha o worker parado e os canais desativados. Restaure
o MySQL e os arquivos da mesma cópia, recupere `communication-private.tar.gz` no
volume SMTP com proprietário www-data e modo 0600, e `whatsapp.sqlite` no volume
do gateway com proprietário node. Não mantenha arquivos WAL/SHM de outra cópia.
Confira mensagens enviadas depois da data do backup antes de liberar a fila;
sessões antigas podem exigir um novo pareamento. Uma única conta deve ser usada
em uma única instância deste gateway, sem reconectar simultaneamente o backup.

## Próximas etapas do plano

1. Comunicação: implementação pronta para pareamento e teste real dos canais.
2. Equipamentos: cadastro permanente, histórico, fotos e recepção/entrega documentada.
3. Operação: Kanban, regras de transição e tempos por etapa.
4. Rentabilidade: horas, custo técnico e despesas por OS.
5. Compras: reservas, movimentações e reposição de estoque.
6. Qualidade: garantias vinculadas e custo dos retornos.
