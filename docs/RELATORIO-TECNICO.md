# Relatório técnico para o cliente

## Preparação e download

Na OS, abra **Execução e testes → Preparar relatório técnico**. O mesmo atalho fica em **Impressão, navegação e comunicação**. O acesso exige as permissões `vOs` e `vCliente`.

1. Revise o problema relatado e o diagnóstico explicado ao cliente. Esses campos começam com os textos da OS, em formato simples, preservando parágrafos. Acrescente recomendações e cuidados quando necessário. Os textos preparados para o documento não alteram a OS.
2. Confira o escopo aprovado. Serviços e peças vêm exclusivamente da versão vigente do orçamento aprovada pelo cliente. Se não houver aprovação válida, o relatório informa essa situação. Mudanças no diagnóstico, itens ou cliente não podem reutilizar uma aprovação incompatível.
3. Selecione os procedimentos e resultados dos checklists que devem aparecer. Apenas itens respondidos são candidatos; resultados como Atenção, Não realizado e Não se aplica mantêm sua identificação. O limite é de 200 resultados. A ausência de resultados não é apresentada como conclusão de testes.
4. Escolha até seis fotos das vistorias de entrada/saída ou dos checklists desta OS. Nenhuma foto começa selecionada. Não são usadas fotos genéricas do cadastro do equipamento nem imagens de outros atendimentos.
5. Gere a prévia, confira o conteúdo e confirme a revisão antes de **Baixar relatório em PDF**. O documento usa A4, identificação da empresa, cliente, responsável técnico, equipamento, problema, diagnóstico, escopo aprovado, procedimentos, testes, recomendações e fotos selecionadas.

Observações de execução da OS, anotações internas, observações dos checklists, referências de autorização e observações internas das fotos não são copiadas. As descrições dos itens e as imagens selecionadas aparecem para revisão; confira se são adequadas para o cliente antes do download. Recomendações não substituem as condições do termo de garantia.

## Prévia e privacidade

A prévia fica na sessão autenticada por até 30 minutos. Uma nova prévia substitui a anterior. Se os dados usados na preparação mudarem, o download é recusado e exige nova revisão. **Voltar e ajustar conteúdo** preserva os textos e as seleções para uma nova conferência.

O PDF é baixado pelo navegador. Esta etapa não arquiva o documento na OS nem o publica no portal; guarde o arquivo baixado quando precisar preservar a versão entregue. Não envia WhatsApp ou e-mail, não cria cobrança, não registra entrega e não aprova serviços. Os relatórios financeiros existentes continuam nas rotas originais; o relatório técnico usa o controller `Relatoriotecnico`.

As rotas são autenticadas, com `no-store`. O download exige POST com CSRF, chave de prévia da mesma sessão, OS correspondente e confirmação de revisão. Seleções de fotos e resultados são conferidas no servidor. O PDF incorpora somente imagens JPEG privadas da própria OS; não recebe HTML, URLs ou caminhos de arquivo do formulário. O cache temporário do mPDF fica fora da área pública, separado por usuário do processo e com diretório privado.

Não há alteração de esquema do banco. O ponto anterior foi preservado na tag `restore/pre-technical-report-20261026` e em backup privado verificado por restauração isolada. Não há credenciais ou dumps no repositório.

## Validação

`tests/technical_report.php` cobre a origem dos dados, aprovação vigente, isolamento entre OS, exclusão de observações privadas, limites, escape dos textos e geração de PDF sem alterações operacionais. É executado junto à suíte de integração.

`tests/checklists_http.py` valida preparação, prévia, confirmação, download autenticado e recusa de prévia alterada. Seus registros e imagens são sintéticos e o teste recusa executar fora de localhost/127.0.0.1. As páginas do PDF de demonstração foram renderizadas para conferência visual de margens, tabelas, fotos e paginação.
