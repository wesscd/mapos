# Cadastro e movimentação de equipamentos

## Página da OS por etapas

A edição e a consulta usam seis abas: **Resumo**, **Equipamento e vistoria**, **Análise técnica**, **Orçamento**, **Entrega** e **Histórico e anexos**. O cabeçalho mostra cliente, responsável, etapa, equipamento e total, com um atalho para a próxima ação. O faturamento e os meios de pagamento ficam em Orçamento; impressão e comunicação ficam no menu “Impressão, navegação e comunicação”. Serviços, Produtos e Desconto são seções de Orçamento; Anotações e Anexos ficam no Histórico.

A aba Equipamento e vistoria permanece visível e marcada como obrigatória para atendimentos com equipamento físico. A indicação de pendência é calculada pelos registros: visitar a aba não confirma recebimento. Para mudar uma OS com equipamento vinculado para **Em Andamento**, é necessário registrar a entrada física de todos os itens. A abertura em andamento com vínculo também exige recebimento na mesma transação. Entradas antigas com condições e acessórios preservados continuam válidas mesmo sem fotos categorizadas. Atendimentos sem equipamento vinculado continuam possíveis. Nenhum recebimento é criado automaticamente pela mudança de aba, abertura pelo portal ou vínculo de cadastro.

Os campos da Análise técnica e do Resumo pertencem ao mesmo salvamento da OS. Trocar de aba preserva o que foi digitado. Uma faixa indica alterações pendentes e exige salvar a OS antes de executar outra ação. A saída da página avisa sobre o rascunho não salvo. Os demais formulários (checklist, fotos, itens e entrega) salvam suas próprias ações. Falhas de validação mantêm os textos enviados para correção. A aba principal escolhida é lembrada por OS na sessão do navegador; os retornos de recebimento, orçamento e entrega abrem a etapa correspondente. Links anteriores como `#equipamentos` e `#tab4` continuam funcionando. A navegação aceita teclado e os formulários permanecem acessíveis se o recurso de abas não carregar.

## Fluxo da ordem de serviço revisado

1. **Abertura:** selecionar cliente e responsável nas sugestões, registrar equipamento, problema e condições iniciais. A criação pelo portal não confirma recebimento físico. Cliente, responsável, etapa, datas e garantia são validados antes do salvamento.
2. **Análise:** registrar o diagnóstico em Laudo Técnico e as verificações no checklist. Salvar os detalhes antes de solicitar aprovação. Alterações na descrição, problema ou diagnóstico aparecem no histórico e invalidam a aprovação anterior.
3. **Orçamento:** incluir serviços e produtos pelas sugestões do catálogo, com quantidades e preços negociados. Serviço aceita quantidade fracionada e preço zero; produtos exigem quantidade inteira. Alterar itens/valores cancela a validade da aprovação anterior e remove o desconto para recálculo. A tela atualiza orçamento e histórico após salvar cada item; erros de validação são exibidos sem deixar a lista presa no carregamento.
4. **Aprovação e execução:** a área do cliente apresenta os itens, valor e análise preservados no orçamento. A resposta pertence àquele registro. O status operacional “Aprovado”, selecionado pela equipe, é independente dessa resposta. Orçamentos antigos sem análise preservada continuam respondíveis quando os itens e valores não mudaram. A equipe acompanha o trabalho pelas etapas e pelo checklist.
5. **Conclusão e entrega:** finalizar o serviço não confirma retirada. Registrar vistoria de saída e conferência para todos os equipamentos recebidos, identificar quem retirou e confirmar o aceite informado pela equipe. Depois da entrega, itens, desconto, diagnóstico e checklist ficam preservados; um retorno exige nova OS.
6. **Faturamento:** usar a ação Faturar, que cria o lançamento e atualiza a OS na mesma transação. A etapa Faturado não pode ser atribuída manualmente. É possível faturar depois da retirada usando os valores já registrados, preservando a data de entrega. Serviços gratuitos permanecem com valor zero na edição, consulta, portal, e-mail e impressão.

Validação adicional: `tests/order_lifecycle.php` cobre abertura inválida, atualização do diagnóstico, orçamento substituído, preços/quantidades, preservação após entrega e faturamento posterior. O teste HTTP local cobre inclusão de produtos e serviços, preço zero, orçamento invalidado, entrega e faturamento sem operar sobre dados de produção. Não são adicionadas regras de SLA, prioridade ou novos campos de prazo.

O menu **Equipamentos** identifica o bem do cliente por nome, marca, modelo, série e cor. A busca aceita cliente, equipamento, modelo e série. Equipamentos existentes nas tabelas originais permanecem disponíveis quando possuem um cliente válido; nenhuma descrição antiga da OS é convertida automaticamente em equipamento ou recebimento.

O cadastro utiliza as permissões de clientes: `vCliente` para consulta, fotos e etiquetas; `aCliente` para criar; `eCliente` para editar e adicionar fotos. O histórico de OS exige também `vOs`. Vincular e receber equipamentos exige `eOs` e `vCliente`. Comprovantes exigem `vOs` e `vCliente`. Não são links públicos e o QR exige login autorizado.

Em **Nova OS**, selecione o cliente nas sugestões e use o campo **Equipamento do cliente (opcional)**, logo abaixo. A lista carrega até 200 equipamentos recentes apenas desse cliente. Para cadastrar um novo equipamento sem perder o formulário, abra o botão de cadastro em outra aba e depois clique em **Atualizar equipamentos**. O vínculo e a OS são salvos na mesma transação; uma seleção inválida desfaz ambos. O campo exige a permissão `vCliente` além de `aOs`.

Na seção **Equipamentos e recebimento físico** da OS já salva (edição ou visualização), escolha outros equipamentos do mesmo cliente ou abra o cadastro de um novo. Uma OS pode possuir vários equipamentos. Vincular não confirma a entrada física. Após a conferência, informe quem entregou, acessórios (inclusive “Nenhum”) e condições aparentes.

## Vistoria de entrada por atendimento

Em Nova OS, a seção **Registrar estado e fotos do equipamento na entrada** fica depois de cliente, equipamento e prazo. É opcional. Para utilizá-la, selecione um equipamento e preencha o estado observado, condições/avarias e acessórios. Os estados são bom estado aparente, marcas de uso, danos aparentes e não avaliado. Nenhuma opção é escolhida automaticamente.

Os campos de fotos são **Frente, Verso, Aberto, Fechado e Teclado**, com observação individual de até 500 caracteres. Cada ângulo é opcional e deve ser usado somente quando aplicável ao equipamento. O campo abre a câmera em celulares compatíveis ou permite selecionar arquivo. Aceita JPEG/PNG até 8 MB e 24 MP; corrige orientação EXIF e reduz para até 1600 pixels. As imagens são armazenadas como JPEG privado no banco, sem metadados de localização. Datas exibidas são do registro no sistema, não uma declaração sobre quando a câmera produziu a imagem original.

Marcar **Confirmar também que este equipamento foi recebido fisicamente** exige o nome de quem entregou e gera o comprovante no mesmo salvamento. Sem essa marcação, a vistoria é registrada sem afirmar posse física. Falha no equipamento ou em qualquer foto desfaz OS, vínculo, vistoria, imagens e captura de notificações juntos. Se o formulário apresentar erro, é necessário selecionar os arquivos novamente; o navegador não os restaura.

Também é possível registrar vistoria na OS já salva. Cada vínculo possui uma vistoria; estado e observações originais não são sobrescritos. Antes da confirmação do recebimento, podem ser acrescentadas fotos (até três por ângulo, 15 no total), sem substituir imagens existentes. A confirmação do recebimento encerra a vistoria e inclui a cópia dos seus dados e referências às imagens no comprovante. Depois disso, não é permitido alterar ou acrescentar fotos de entrada. Novas informações podem ser registradas como anotações no atendimento. Uma OS com vistoria também não pode ser excluída.

A vistoria pertence ao vínculo equipamento/OS; fotos gerais do cadastro não são reutilizadas como se fossem fotos da entrada atual. A consulta das novas imagens exige `vOs` e `vCliente`; registrar/acrescentar na OS existente exige `eOs` e `vCliente`. Na abertura, exige `aOs` e `vCliente`. A impressão do comprovante inclui estado, condições, responsável pelo registro, datas, ângulos e observações. A assinatura continua sendo coletada no papel, sem alegação de assinatura eletrônica.

O recebimento gera um comprovante com data, usuário responsável e cópia imutável dos dados do cliente, empresa, equipamento, acessórios, condições e identificadores das fotos daquele momento. Alterações posteriores do cadastro não reescrevem esse documento. A impressão permite salvar PDF e coletar assinaturas no papel; o sistema não apresenta o registro interno como assinatura do cliente. Um equipamento com entrada pendente de saída não pode ter outro recebimento registrado em paralelo. Se o serviço for cancelado depois da entrada, a confirmação da entrega continua disponível para documentar a devolução.

## Conferência de entrega

Na seção **Equipamentos e recebimento físico**, cada item recebido apresenta **Conferência para entrega**. Abra **Registrar vistoria de saída**, informe estado, condições aparentes, acessórios que serão devolvidos e o resultado da conferência em relação à entrada. Divergências exigem ressalvas; podem ser registradas sem impedir uma retirada conferida. As fotos de saída usam os mesmos cinco ângulos e limites da entrada, são opcionais e não substituem as fotos originais.

Depois de salvar, **Comparar fotos de entrada e saída por posição** mostra os registros lado a lado, com observações e datas. Os dados de entrada vêm do comprovante preservado, inclusive em equipamentos cujo cadastro foi editado depois. Quando não houver foto para um ângulo, a tela informa a ausência, sem inferir que o equipamento estava sem avarias. Entradas antigas sem vistoria categorizada continuam disponíveis com condições, acessórios e fotos gerais preservadas.

Após finalizar a OS, informe o nome de quem retira e marque a confirmação de conferência e aceite apenas após a conferência presencial. Trata-se de aceite informado pela equipe, com usuário e data, não de assinatura eletrônica do cliente. É possível coletar a assinatura no comprovante impresso. A entrega exige recebimento e vistoria de saída de todos os equipamentos vinculados. O salvamento é atômico: uma pendência impede a entrega inteira, sem emitir comprovantes parciais ou disparar mensagens de entrega.

O comprovante de saída preserva a entrada original, a vistoria de saída, suas fotos e ressalvas, o nome de quem retirou e o registro de aceite. Após confirmar a entrega, não é permitido acrescentar fotos ou alterar a vistoria de saída. OS já entregues antes desta atualização não são modificadas. OS antigas sem equipamento vinculado mantêm a confirmação de entrega anterior. O serviço cancelado com recebimento físico também permite registrar a vistoria e a devolução.

Atendimento com comprovantes não pode ser excluído; uma entrega documentada não pode ser reaberta apagando a movimentação: registre o retorno em nova OS. Não é permitido mudar o cliente da OS ou o proprietário de um equipamento com histórico.

A migração `20261021100000_delivery_inspections.php` permite uma vistoria de cada tipo por vínculo. Mantém todos os registros existentes como entrada. Sua reversão é bloqueada quando há vistorias de saída, para não apagar evidências. Permissões e privacidade das fotos são as mesmas da entrada (`eOs` e `vCliente` para registrar; `vOs` e `vCliente` para consultar fotos e comprovantes).

Fotos gerais do cadastro (separadas da vistoria da OS): JPEG/PNG até 2 MB e 12 megapixels; até 12 por equipamento. São decodificadas, reduzidas para até 1600 pixels e regravadas em JPEG, removendo metadados e conteúdo adicional. Armazenadas em tabela privada, são entregues por rota autenticada com `no-store`; integram o backup MySQL existente, sem depender de arquivos públicos. O volume de fotos aumenta o banco e o tamanho dos backups. Não há exclusão ou substituição de fotos/documentos nesta etapa, para preservar referências históricas.

Validação: `scripts/test.ps1` executa regras de propriedade, idempotência, entrada/saída, preservação de histórico, fotos e QR em banco descartável. `tests/http_smoke.py` também verifica as duas novas telas iniciais. A migração `20261020160000_equipment_intake.php` cria apenas as tabelas de fotos e comprovantes; as tabelas de marcas, equipamentos e vínculos já existiam.

A migração `20261020180000_intake_inspections.php` acrescenta as tabelas de vistoria e fotos por atendimento. Elas integram o backup do banco existente. `tests/inspections.php` verifica imutabilidade, isolamento, orientação EXIF e rollback; `tests/inspection_http.py` cobre o envio multipart e o acesso privado a imagens em aplicação local de teste.

### Interface reconstruída

Abertura, edição, consulta e listagem compartilham os componentes de `application/views/os/`. Os seis painéis são renderizados no servidor. Nenhum bloco é transferido entre painéis por JavaScript. Desconto, faturamento, Pix, cobrança, anotações e anexos usam formulários ou seções expansíveis; as opções de impressão são links acessíveis por teclado e toque. Não há dependência de modais Bootstrap ou de submenus por hover nessas telas.

`assets/js/os-workspace.js` concentra navegação, autocompletes, prévia de desconto, prevenção de perda da análise e submissões JSON. Uma ação JSON concluída recarrega o estado completo e preserva a aba, evitando controles com eventos perdidos e totais antigos após substituir fragmentos. Falhas exibem a mensagem do servidor e mantêm o preenchimento para correção. Não há repetição automática de pedidos de gravação. O salvamento de resumo/análise continua sendo nativo e único; salve essa OS antes de preencher uma ação independente, como um novo checklist.

A listagem e o orçamento consideram `tipo_desconto` para distinguir ausência de desconto de desconto integral. O QR Pix e o código Copia e Cola partem do mesmo payload, com valor líquido persistido. Datas inválidas retornam mensagens de validação. Não há migração nem alteração de registros históricos nesta reconstrução.

Validação visual local: busca e inclusão de serviço, desconto de R$ 10 em OS de R$ 100, salvamento e troca de aba, proteção de diagnóstico não salvo e retorno do checklist. A integração HTTP também exige IDs únicos, formulários independentes e totais coerentes na consulta, edição e listagem. Confira teclado e largura móvel ao alterar os componentes.

A busca da listagem aceita nome/documento do cliente e número da OS (também com `#`). A paginação usa os mesmos filtros e uma busca sem correspondência mantém o resultado vazio.

### Envio de orçamento pelo WhatsApp

Na aba **Orçamento**, a seção **WhatsApp do orçamento** permite conferir destinatário, total líquido e a mensagem antes de confirmar. O envio usa a fila e o gateway Baileys existentes. Não abre o WhatsApp Web e não envia e-mail junto. O link da mensagem leva à área autenticada do cliente para consultar e aprovar ou recusar. A alternativa manual continua disponível antes de haver uma comunicação registrada.

A prévia é somente leitura: não publica aprovação nem envia mensagens. A confirmação valida novamente orçamento, diagnóstico, telefone e modelo. Mudanças exigem atualizar a prévia. A primeira confirmação publica a solicitação de aprovação e grava a mensagem na mesma transação. Solicitações pendentes com o mesmo conteúdo são reutilizadas, inclusive ao clicar novamente em solicitar aprovação.

Uma mensagem automática já registrada para a mesma solicitação e destinatário é exibida no acompanhamento. Pendências, envios e confirmações incertas não geram mensagens duplicadas. Em falha ou cancelamento, a equipe pode confirmar nova tentativa da mesma mensagem, preservando seu histórico. O estado é atualizado ao usar **Atualizar acompanhamento**. Aprovação respondida ou OS encerrada não é reaberta pelo envio.

São respeitados a preferência/autorização do cliente, o canal/modelo habilitado e os horários da Central. O campo **{valor}** está disponível nos modelos; modelos antigos de orçamento sem esse campo recebem o total no texto renderizado, sem reescrever modelos nem mensagens já registradas. O destino é normalizado com DDI e DDD. Nenhuma credencial do gateway é exposta no navegador.

`tests/budget_whatsapp.php` usa apenas transporte simulado e banco descartável para validar autorização, confirmação, prévias desatualizadas, publicação atômica, independência do e-mail, deduplicação, tentativas e invalidação de orçamento. Não realiza envios externos.
