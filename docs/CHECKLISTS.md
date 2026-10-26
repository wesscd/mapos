# Checklists técnicos por equipamento

## Onde configurar

Em **Configurações → Sistema → Checklists técnicos**, edite os modelos de Notebook, Desktop e Outros equipamentos. O acesso exige `cSistema`. Os itens são separados em análise e execução/conferência final. Adicione itens, edite descrições e tipos, ou marque itens para retirada das próximas aplicações. Cada salvamento cria uma nova versão. Desativar o modelo impede novas aplicações, sem alterar as cópias existentes.

No **cadastro do equipamento**, escolha o campo **Tipo para checklist técnico**. Nos novos vínculos desse equipamento com uma OS, o modelo ativo é copiado automaticamente. Os cadastros existentes começam sem tipo e nenhuma OS antiga é alterada automaticamente. Alterar o tipo do equipamento não reescreve seus atendimentos anteriores.

## Como utilizar na OS

1. Use **Análise técnica** para o diagnóstico e as verificações iniciais. Use **Execução e testes** para as observações da execução, os procedimentos e a conferência final.
2. Se a OS é anterior à melhoria, use **Aplicar modelo por tipo de equipamento** na aba Análise técnica, escolhendo um equipamento vinculado ou um checklist geral do atendimento.
3. Abra o item, selecione o resultado e salve. Análise aceita Pendente, Conforme, Atenção e Não se aplica. Execução aceita Pendente, Realizado, Não realizado e Não se aplica.
4. Atenção e Não realizado exigem observação ou justificativa. Itens classificados como **Exige autorização** precisam de referência do escopo aprovado para serem marcados como Realizado. Esse registro não aprova o orçamento em nome do cliente.
5. Acrescente itens específicos da OS quando necessário. Essas inclusões não alteram os modelos reutilizáveis.
6. Consulte o histórico de cada item. Na aba Execução e testes, abra o **Resumo dos procedimentos realizados**, que pode ser impresso e entregue ao cliente.
7. Na aba **Entrega**, confira o resumo de pendências de análise e execução, os resultados Atenção/Não realizado, os acessórios registrados no comprovante de entrada e a existência da vistoria de saída. O painel é orientativo: não bloqueia a retirada nem substitui a conferência presencial. Ausência de checklist não aparece como teste concluído; Não se aplica não é pendência.

Verificações, cuidados complementares e intervenções autorizadas são classificações operacionais. O checklist não adiciona serviços, peças, descontos ou cobranças. Cuidados adicionais devem respeitar o escopo combinado. Itens não aplicáveis podem ser marcados explicitamente, sem inventar resultados.

## Evidências e histórico

Cada item aceita até três fotos JPEG/PNG de até 8 MB e 24 megapixels. As imagens são decodificadas, orientadas e regravadas em JPEG, com remoção de metadados, pela rotina já usada nas vistorias. Elas ficam no banco e são servidas por rota autenticada com `no-store`; consultar ou anexar fotos exige `vCliente` além das permissões de OS. Não há substituição ou exclusão de evidências nesta etapa.

Salvar o resultado exige `vOs` e `eOs`. São registrados responsável, horário, resultado, observações e referência de autorização. O histórico por item preserva os estados antes e depois de cada alteração, mesmo após correção do resultado. A tela mostra os últimos 100 registros daquele item; os demais continuam no banco. Edições simultâneas exigem recarregar a página antes de sobrescrever dados.

OS entregues, faturadas ou canceladas não permitem alterar checklists. A existência de um checklist técnico impede excluir a OS. O resumo imprimível inclui apenas procedimentos de execução marcados como Realizado, com data e responsável; não inclui fotos, observações internas ou referências de autorização. Ele não comprova pagamento ou entrega e não é publicado em rota anônima.

Os itens do checklist anterior foram preservados em uma seção própria; não receberam resultados de análise/execução nem responsáveis inventados. As etapas da OS e o fluxo de aprovação continuam independentes do progresso do checklist: responder a todos os itens não finaliza ou entrega a OS automaticamente.

## Migração, testes e recuperação

A migração `20261023190000_equipment_checklists.php` acrescenta o tipo opcional no equipamento e as tabelas `check_modelos`, `os_check_grupos`, `os_check_itens`, `os_check_fotos` e `os_check_registros`. As fotos e os registros integram os backups MySQL existentes. A reversão recusa apagar checklists já aplicados; não use `down` em produção para desfazer atendimentos.

Foi criada a tag Git **restore/pre-checklists-20261023**, anterior à melhoria. O backup correspondente está no armazenamento privado de implantação e foi verificado por restauração isolada. A recuperação usa o banco, os arquivos privados e a revisão do mesmo ponto, preservando separadamente qualquer atividade posterior que precise ser mantida. Não há credenciais ou dumps neste repositório.

`tests/checklists.php` cobre cópias por OS, versões, autorização, concorrência, ausência de cobrança, fotos, histórico e bloqueios de encerramento/reversão. `tests/checklists_http.py` cobre o fluxo por HTTP com dados sintéticos e recusa destinos fora de localhost/127.0.0.1. O workflow de qualidade executa ambos. As prévias e os testes não enviam mensagens para clientes.
