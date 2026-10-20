# Kanban de OS

O menu **Kanban de OS**, a lista de OS e a tela de operações oferecem acesso ao quadro em `/index.php/kanban`. Exige `vOs`; mover cartões exige também `eOs`.

As colunas são Abertas, Orçamento (inclui Negociação), Aguardando aprovação, Autorizadas, Em execução, Aguardando peças e Aguardando retirada. Aguardando aprovação é derivada de uma resposta de orçamento pendente enquanto a OS está em uma fase inicial: não é um novo status de banco. Solicitar aprovação e responder ao orçamento continuam nos fluxos próprios. Mover para Autorizadas é uma decisão operacional da equipe e não registra aprovação do cliente.

Finalizadas ou faturadas sem entrega física continuam em Aguardando retirada. O filtro de encerradas acrescenta entregues nos últimos 30 dias e canceladas cuja abertura ocorreu nos últimos 30 dias. Para consultar outros históricos, use a lista de OS. Há busca por número, cliente, equipamento, modelo e série, além de filtro de técnico. O quadro mostra até 500 OS filtradas e avisa quando o limite é atingido.

Cada cartão mostra cliente, equipamentos vinculados, técnico, prazo e status. Atrasos são destacados em atendimentos ainda não concluídos. A interface permite arrastar para colunas com destino de status ou usar o seletor de etapa, inclusive no celular. A coluna de aprovação é informativa; não recebe cartões por arraste.

Toda movimentação chama o mesmo serviço transacional da OS: atualiza conclusão, timeline, estoque no cancelamento e captura de avisos conforme as preferências existentes. O quadro não fatura, não registra retirada, não altera valores e não reabre OS entregues/faturadas/canceladas. Uma mudança concorrente de status bloqueia o cartão desatualizado. Se duas pessoas trabalharem no mesmo quadro, recarregue para consultar o estado atual.

`scripts/test.ps1` verifica classificação, atrasos, filtros, movimentação, concorrência, encerramento, aprovação e cancelamento com devolução de estoque. A CI também executa upload multipart dos cinco ângulos, leitura privada das imagens, comprovante e movimentação real em HTTP com um cliente sintético e aplicação loopback. `tests/http_fixture.php --local-fixture` recusa URLs fora de localhost/127.0.0.1; `tests/inspection_http.py` também recusa destinos de produção. Esses testes criam dados de demonstração e não excluem cadastros reais.
