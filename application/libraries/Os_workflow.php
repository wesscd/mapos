<?php

defined('BASEPATH') or exit('No direct script access allowed');
/** Coordinates OS changes, stock and the customer approval snapshot. */
class Os_workflow
{
    public const STATUSES = ['Aberto', 'Orçamento', 'Negociação', 'Aprovado', 'Aguardando Peças', 'Em Andamento', 'Finalizado', 'Cancelado'];
    private $ci;
    private $actor;

    public function setActor($id)
    {
        $this->actor = (int) $id;
    }

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    public function details($id)
    {
        return [
            'equipamentos_disponiveis' => $this->ci->db->select('equipamentos.idEquipamentos, equipamento, num_serie')->from('equipamentos')->join('os', 'os.clientes_id = equipamentos.clientes_id')->where('os.idOs', (int) $id)->order_by('idEquipamentos', 'desc')->limit(200)->get()->result(),
            'equipamentos' => $this->ci->db->select('equipamentos_os.idEquipamentos_os, equipamentos.idEquipamentos, equipamentos.equipamento, equipamentos.num_serie, equipamentos.modelo')->from('equipamentos_os')->join('equipamentos', 'equipamentos.idEquipamentos = equipamentos_os.equipamentos_id')->where('os_id', (int) $id)->get()->result(),
            'recibos' => $this->ci->db->select('equipamento_recibos.id, vinculo_id, tipo, equipamento_recibos.created_at')->from('equipamento_recibos')->join('equipamentos_os', 'equipamentos_os.idEquipamentos_os = equipamento_recibos.vinculo_id')->where('os_id', (int) $id)->get()->result(),
            'checklist' => $this->ci->db->order_by('id')->get_where('os_checklist', ['os_id' => $id])->result(),
            'eventos' => $this->ci->db->order_by('id', 'desc')->limit(100)->get_where('os_eventos', ['os_id' => $id])->result(),
            'aprovacoes' => $this->ci->db->order_by('id', 'desc')->get_where('os_aprovacoes', ['os_id' => $id])->result(),
        ];
    }

    public function workspaceState($order, array $details)
    {
        $links = array_map(fn ($equipment) => (int) $equipment->idEquipamentos_os, $details['equipamentos']);
        $received = array_unique(array_map(fn ($receipt) => (int) $receipt->vinculo_id, array_filter($details['recibos'], fn ($receipt) => $receipt->tipo === 'entrada')));
        $inspections = $links ? $this->ci->db->select('vinculo_id, tipo')->where_in('vinculo_id', $links)->get('os_vistorias')->result() : [];
        $entry = count(array_filter($inspections, fn ($inspection) => $inspection->tipo === 'entrada'));
        $exit = count(array_filter($inspections, fn ($inspection) => $inspection->tipo === 'saida'));
        $equipmentLabel = ! $links ? 'Sem vínculo' : (count($received) < count($links) ? 'Recebimento pendente' : ($entry < count($links) ? 'Entrada registrada' : 'Conferido'));
        $analysisReady = trim(strip_tags((string) $order->laudoTecnico)) !== '';
        $latest = $details['aprovacoes'][0]->status ?? null;
        $budgetLabels = ['pendente' => 'Aguardando cliente', 'aprovado' => 'Aprovado pelo cliente', 'rejeitado' => 'Recusado', 'substituido' => 'Revisar orçamento'];
        // Older intake receipts already preserve conditions and accessories, even without categorized photos.
        $equipmentPending = $links && count($received) < count($links);
        if ($order->entregue_em) {
            $next = ['entrega', 'Consultar entrega'];
        } elseif ($order->status === 'Cancelado' && ! $received) {
            $next = ['historico', 'Consultar histórico'];
        } elseif ($equipmentPending) {
            $next = ['equipamento', 'Conferir entrada'];
        } elseif (in_array($order->status, ['Finalizado', 'Faturado', 'Cancelado'], true)) {
            $next = ['entrega', 'Conferir retirada'];
        } elseif (! $analysisReady) {
            $next = ['analise', 'Registrar diagnóstico'];
        } elseif ($latest === 'pendente') {
            $next = ['orcamento', 'Acompanhar aprovação'];
        } elseif (in_array($latest, ['rejeitado', 'substituido'], true)) {
            $next = ['orcamento', 'Revisar orçamento'];
        } else {
            $next = $latest ? ['analise', 'Acompanhar execução'] : ['orcamento', 'Preparar orçamento'];
        }
        return [
            'received' => count($received), 'equipmentCount' => count($links), 'entryInspections' => $entry, 'exitInspections' => $exit,
            'equipment' => $order->entregue_em ? 'Histórico preservado' : $equipmentLabel,
            'equipmentPending' => ! $order->entregue_em && $equipmentPending,
            'analysis' => $analysisReady ? 'Registrada' : ($order->entregue_em ? 'Não registrado' : 'Diagnóstico pendente'),
            'budget' => $order->faturado ? 'Faturado' : ($budgetLabels[$latest] ?? 'Preparar'),
            'delivery' => $order->entregue_em ? 'Entregue' : ($exit < count($links) ? 'Vistoria de saída pendente' : 'Conferir retirada'),
            'nextTab' => $next[0], 'nextLabel' => $next[1],
        ];
    }

    private function transaction(callable $work)
    {
        $db = $this->ci->db;
        $debug = $db->db_debug;
        $db->db_debug = false;
        $db->trans_begin();
        try {
            $result = $work();
            if (! $db->trans_status()) {
                throw new RuntimeException('Não foi possível concluir a operação. Tente novamente.');
            }
            $db->trans_commit();
            return $result;
        } catch (Throwable $error) {
            $db->trans_rollback();
            throw $error;
        } finally {
            $db->db_debug = $debug;
        }
    }

    private function lockOrder($id)
    {
        $order = $this->ci->db->query('SELECT * FROM os WHERE idOs = ? FOR UPDATE', [(int) $id])->row();
        if (! $order) {
            throw new DomainException('Ordem de serviço não encontrada.');
        }
        return $order;
    }

    private function requireIntakeForExecution($id, $status, $previous = null)
    {
        if ($status !== 'Em Andamento' || $previous === 'Em Andamento') {
            return;
        }
        $linked = $this->ci->db->where('os_id', $id)->count_all_results('equipamentos_os');
        $received = $this->ci->db->from('equipamento_recibos')->join('equipamentos_os', 'equipamentos_os.idEquipamentos_os = equipamento_recibos.vinculo_id')
            ->where('os_id', $id)->where('tipo', 'entrada')->count_all_results();
        if ($received < $linked) {
            throw new DomainException('Antes de iniciar o reparo, registre a entrada física de todos os equipamentos na aba Equipamento e vistoria. Vincular o cadastro ou visitar a aba não confirma recebimento.');
        }
    }

    private function editable($order, $allowDelivered = false)
    {
        if ($order->entregue_em && ! $allowDelivered) {
            throw new DomainException('A entrega já foi registrada. Preserve esta OS e abra uma nova para o retorno do equipamento.');
        }
        if ((int) $order->faturado || in_array($order->status, ['Cancelado', 'Faturado'], true)) {
            throw new DomainException('Reabra a OS antes de alterar seus itens. OS faturadas não permitem alterações de valores.');
        }
    }

    public function dateFromForm($value)
    {
        if (is_string($value) && preg_match('~^(\d{2})/(\d{2})/(\d{4})$~D', $value, $parts)) {
            return $parts[3] . '-' . $parts[2] . '-' . $parts[1];
        }
        return $value;
    }

    public function validatedDateFromForm($value)
    {
        $date = $this->dateFromForm($value);
        if (! $this->validDate($date)) {
            throw new DomainException('Informe uma data válida no formato dd/mm/aaaa.');
        }
        return $date;
    }

    private function validDate($value)
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)
            && ($date = DateTimeImmutable::createFromFormat('!Y-m-d', $value)) && $date->format('Y-m-d') === $value;
    }

    private function validateOrder(array $data)
    {
        foreach (['descricaoProduto', 'defeito', 'observacoes', 'laudoTecnico'] as $field) {
            if (isset($data[$field]) && (! is_string($data[$field]) || strlen($data[$field]) > 65535)) {
                throw new DomainException('Informe textos válidos de até 64 KB para a descrição, problema, observações e diagnóstico.');
            }
        }
        foreach (['clientes_id' => ['clientes', 'idClientes'], 'usuarios_id' => ['usuarios', 'idUsuarios']] as $field => [$table, $key]) {
            if (! filter_var($data[$field] ?? null, FILTER_VALIDATE_INT) || ! $this->ci->db->get_where($table, [$key => $data[$field]])->row()) {
                throw new DomainException('Selecione um cliente e um responsável válidos nas sugestões.');
            }
        }
        foreach (['dataInicial', 'dataFinal'] as $field) {
            $value = $data[$field] ?? null;
            if ($value !== null && $value !== '' && ! $this->validDate($value)) {
                throw new DomainException('Informe datas válidas no formato dia/mês/ano.');
            }
        }
        if (isset($data['garantia']) && $data['garantia'] !== '' && (filter_var($data['garantia'], FILTER_VALIDATE_INT) === false || $data['garantia'] < 0 || $data['garantia'] > 36500)) {
            throw new DomainException('Informe a garantia em dias inteiros, entre 0 e 36500.');
        }
    }

    private function lineValues($quantity, $price, $product)
    {
        if (! is_numeric($quantity) || ! is_finite((float) $quantity) || $quantity <= 0
            || ! is_numeric($price) || ! is_finite((float) $price) || $price < 0
            || ($product && filter_var($quantity, FILTER_VALIDATE_INT) === false)) {
            throw new DomainException('Informe quantidade e preço válidos. Produtos exigem quantidade inteira.');
        }
        $price = round((float) $price, 2);
        if ($quantity > 2147483647 || $price > 99999999.99 || $quantity * $price > 99999999.99) {
            throw new DomainException('Quantidade ou valor excede o limite permitido para o item.');
        }
        return [$quantity, $price];
    }

    public function event($id, $type, $description, array $context = [])
    {
        $this->ci->db->insert('os_eventos', [
            'os_id' => (int) $id,
            'usuario_id' => $this->actor ?: ($this->ci->session->userdata('id_admin') ?: null),
            'tipo' => $type,
            'descricao' => $description,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $source = 'event:' . $this->ci->db->insert_id();
        $events = ['criacao' => 'abertura', 'status' => 'status', 'prazo' => 'prazo', 'entrega' => 'entrega'];
        $event = $events[$type] ?? null;
        if ($type === 'status') {
            $order = $this->ci->db->get_where('os', ['idOs' => $id])->row();
            $event = $order && $order->status === 'Finalizado' ? 'pronto' : 'status';
        } elseif ($type === 'orcamento' && isset($context['aprovacao'])) {
            $event = 'orcamento';
        }
        if ($event) {
            $this->ci->load->library('comunicacao_service');
            $this->ci->comunicacao_service->capture($id, $event, $source, $context);
        }
    }

    public function createOrder(array $data, array $equipmentIds = [], ?array $inspection = null)
    {
        return $this->transaction(function () use ($data, $equipmentIds, $inspection) {
            if (! in_array($data['status'] ?? null, self::STATUSES, true) || ! empty($data['faturado'])) {
                throw new DomainException('Selecione uma etapa válida. Utilize a ação Faturar para gerar o lançamento financeiro.');
            }
            $this->validateOrder($data);
            if ($data['status'] === 'Finalizado') {
                $data['concluida_em'] = date('Y-m-d H:i:s');
            }
            if (count($equipmentIds) > 20) {
                throw new DomainException('Vincule até 20 equipamentos por vez.');
            }
            $this->ci->db->insert('os', $data);
            $id = $this->ci->db->insert_id();
            $this->event($id, 'criacao', 'OS criada. Status: ' . $data['status']);
            if ($equipmentIds) {
                $this->ci->load->library('equipamento_service');
                foreach ($equipmentIds as $equipmentId) {
                    if (filter_var($equipmentId, FILTER_VALIDATE_INT) === false || $equipmentId <= 0) {
                        throw new DomainException('Selecione um equipamento válido para este cliente.');
                    }
                    $linkId = $this->ci->equipamento_service->link($id, (int) $equipmentId);
                }
            }
            if ($inspection !== null) {
                if (count(array_unique($equipmentIds, SORT_REGULAR)) !== 1) {
                    throw new DomainException('Selecione um equipamento do cliente para registrar sua vistoria.');
                }
                $this->ci->load->library('vistoria_service');
                $this->ci->vistoria_service->register($id, $linkId, $inspection['dados'], $inspection['fotos']);
                if (($inspection['dados']['vistoria_receber'] ?? '') === '1') {
                    $this->ci->equipamento_service->intake($id, $linkId, ['entregue_por' => $inspection['dados']['vistoria_entregue_por'] ?? '',
                        'acessorios' => $inspection['dados']['vistoria_acessorios'] ?? '', 'condicoes' => $inspection['dados']['vistoria_condicoes'] ?? '']);
                }
            }
            $this->requireIntakeForExecution($id, $data['status']);
            return $id;
        });
    }

    public function deliver($id, $recipient = '', $accepted = false)
    {
        return $this->transaction(function () use ($id, $recipient, $accepted) {
            $order = $this->lockOrder($id);
            $cancelledReturn = $order->status === 'Cancelado' && $this->ci->db->from('equipamento_recibos')->join('equipamentos_os', 'equipamentos_os.idEquipamentos_os = equipamento_recibos.vinculo_id')->where('os_id', $id)->where('tipo', 'entrada')->count_all_results();
            if ((! in_array($order->status, ['Finalizado', 'Faturado'], true) && ! $cancelledReturn) || $order->entregue_em) {
                throw new DomainException('Finalize a OS antes da entrega. Em atendimentos cancelados, a devolução exige recebimento registrado. Não é possível repetir uma entrega.');
            }
            $this->ci->load->library('equipamento_service');
            $this->ci->equipamento_service->deliveryReceipts($id, $recipient, $accepted);
            $this->ci->db->where('idOs', $id)->update('os', ['entregue_em' => date('Y-m-d H:i:s')]);
            $this->event($id, 'entrega', 'Entrega do equipamento ao cliente registrada.');
        });
    }

    private function stock($product, $quantity, $direction)
    {
        if ($quantity <= 0 || ! in_array($direction, ['+', '-'], true)) {
            throw new DomainException('Quantidade inválida.');
        }
        $db = $this->ci->db;
        $db->query('SELECT idProdutos FROM produtos WHERE idProdutos = ? FOR UPDATE', [(int) $product]);
        // The operator is selected exclusively by application code; values use bindings.
        $sql = $direction === '-'
            ? 'UPDATE produtos SET estoque = estoque - ? WHERE idProdutos = ? AND estoque >= ?'
            : 'UPDATE produtos SET estoque = estoque + ? WHERE idProdutos = ?';
        $values = $direction === '-' ? [$quantity, (int) $product, $quantity] : [$quantity, (int) $product];
        $db->query($sql, $values);
        if ($db->affected_rows() !== 1) {
            throw new DomainException('Estoque insuficiente ou produto indisponível.');
        }
    }

    private function invalidateBudget($id)
    {
        $this->ci->db->where('os_id', $id)->where_in('status', ['pendente', 'aprovado'])->update('os_aprovacoes', ['status' => 'substituido']);
    }

    private function resetDiscount($id)
    {
        $this->ci->db->where('idOs', $id)->update('os', ['desconto' => 0, 'valor_desconto' => 0, 'tipo_desconto' => null]);
        $this->invalidateBudget($id);
    }

    public function addProduct(array $item, $controlStock)
    {
        return $this->transaction(function () use ($item, $controlStock) {
            $this->editable($this->lockOrder($item['os_id']));
            [$item['quantidade'], $item['preco']] = $this->lineValues($item['quantidade'] ?? null, $item['preco'] ?? null, true);
            if ($controlStock) {
                $this->stock($item['produtos_id'], $item['quantidade'], '-');
            }
            $product = $this->ci->db->get_where('produtos', ['idProdutos' => $item['produtos_id']])->row();
            if (! $product) {
                throw new DomainException('Produto não encontrado.');
            }
            $item['custo_unitario'] = $product->precoCompra;
            $item['subTotal'] = round($item['preco'] * $item['quantidade'], 2);
            $this->ci->db->insert('produtos_os', $item);
            $id = $this->ci->db->insert_id();
            $this->resetDiscount($item['os_id']);
            $this->event($item['os_id'], 'produto', 'Produto adicionado. Quantidade: ' . $item['quantidade']);
            return $id;
        });
    }

    public function removeProduct($orderId, $itemId, $controlStock)
    {
        return $this->transaction(function () use ($orderId, $itemId, $controlStock) {
            $this->editable($this->lockOrder($orderId));
            $item = $this->ci->db->get_where('produtos_os', ['idProdutos_os' => (int) $itemId, 'os_id' => (int) $orderId])->row();
            if (! $item) {
                throw new DomainException('Produto não encontrado nesta OS.');
            }
            if ($controlStock) {
                $this->stock($item->produtos_id, $item->quantidade, '+');
            }
            $this->ci->db->delete('produtos_os', ['idProdutos_os' => $item->idProdutos_os]);
            $this->resetDiscount($orderId);
            $this->event($orderId, 'produto', 'Produto removido; estoque devolvido quando habilitado.');
        });
    }

    public function service($id, array $data = [], $removeId = null)
    {
        return $this->transaction(function () use ($id, $data, $removeId) {
            $this->editable($this->lockOrder($id));
            if ($removeId) {
                $this->ci->db->delete('servicos_os', ['idServicos_os' => (int) $removeId, 'os_id' => (int) $id]);
                if ($this->ci->db->affected_rows() !== 1) {
                    throw new DomainException('Serviço não encontrado nesta OS.');
                }
            } else {
                [$data['quantidade'], $data['preco']] = $this->lineValues($data['quantidade'] ?? null, $data['preco'] ?? null, false);
                if (! $this->ci->db->get_where('servicos', ['idServicos' => (int) ($data['servicos_id'] ?? 0)])->row()) {
                    throw new DomainException('Selecione um serviço válido nas sugestões.');
                }
                $data['os_id'] = (int) $id;
                $data['subTotal'] = round($data['quantidade'] * $data['preco'], 2);
                $this->ci->db->insert('servicos_os', $data);
                $inserted = $this->ci->db->insert_id();
            }
            $this->resetDiscount($id);
            $this->event($id, 'servico', $removeId ? 'Serviço removido.' : 'Serviço adicionado.');
            return $removeId ? null : $this->ci->db->get_where('servicos_os', ['idServicos_os' => $inserted])->row();
        });
    }

    public function discount($id, $type, $value)
    {
        return $this->transaction(function () use ($id, $type, $value) {
            $this->editable($this->lockOrder($id));
            if (! in_array($type, ['real', 'porcento'], true) || ! is_numeric($value) || ! is_finite((float) $value) || $value < 0) {
                throw new DomainException('Desconto inválido.');
            }
            $this->ci->load->model('os_model');
            $totals = $this->ci->os_model->valorTotalOS($id);
            $gross = $totals['totalProdutos'] + $totals['totalServico'];
            $amount = $type === 'porcento' ? round($gross * $value / 100, 2) : round($value, 2);
            if ($amount > $gross || ($type === 'porcento' && $value > 100)) {
                throw new DomainException('O desconto não pode exceder o valor da OS.');
            }
            $this->ci->db->where('idOs', $id)->update('os', ['tipo_desconto' => $type, 'desconto' => $value, 'valor_desconto' => $gross - $amount]);
            $this->invalidateBudget($id);
            $this->event($id, 'desconto', 'Desconto atualizado. Valor líquido: R$ ' . number_format($gross - $amount, 2, ',', '.'));
        });
    }

    public function changeLine($id, $itemId, $quantity, $price, $product, $controlStock = false)
    {
        return $this->transaction(function () use ($id, $itemId, $quantity, $price, $product, $controlStock) {
            $this->editable($this->lockOrder($id));
            [$quantity, $price] = $this->lineValues($quantity, $price, $product);
            $table = $product ? 'produtos_os' : 'servicos_os';
            $key = $product ? 'idProdutos_os' : 'idServicos_os';
            $item = $this->ci->db->get_where($table, [$key => (int) $itemId, 'os_id' => (int) $id])->row();
            if (! $item) {
                throw new DomainException('Item não encontrado nesta OS.');
            }
            if ($product && $controlStock && (float) $quantity !== (float) $item->quantidade) {
                $delta = $quantity - $item->quantidade;
                $this->stock($item->produtos_id, abs($delta), $delta > 0 ? '-' : '+');
            }
            $this->ci->db->where($key, $itemId)->update($table, ['quantidade' => $quantity, 'preco' => $price, 'subTotal' => round($quantity * $price, 2)]);
            $this->resetDiscount($id);
            $this->event($id, 'item', 'Quantidade ou preço de item atualizado.');
            return $this->ci->db->get_where($table, [$key => $itemId])->row();
        });
    }

    public function invoice($id, array $data)
    {
        return $this->transaction(function () use ($id, $data) {
            $order = $this->lockOrder($id);
            $this->editable($order, true);
            foreach (['data_vencimento', 'data_pagamento'] as $field) {
                if (isset($data[$field]) && $data[$field] !== '' && ! $this->validDate($data[$field])) {
                    throw new DomainException('Informe datas válidas para vencimento e pagamento.');
                }
            }
            $this->ci->load->model('os_model');
            $totals = $this->ci->os_model->valorTotalOS($id);
            $gross = round($totals['totalProdutos'] + $totals['totalServico'], 2);
            $net = $order->tipo_desconto ? (float) $order->valor_desconto : $gross;
            if ($gross <= 0 || $net < 0 || $net > $gross) {
                throw new DomainException('Confira os itens e o desconto antes de faturar.');
            }
            $data = array_merge($data, ['valor' => $gross, 'desconto' => $gross - $net, 'valor_desconto' => $net, 'tipo_desconto' => 'real', 'tipo' => 'receita', 'clientes_id' => $order->clientes_id]);
            $this->ci->db->insert('lancamentos', $data);
            $entry = $this->ci->db->insert_id();
            $this->ci->db->where('idOs', $id)->update('os', ['faturado' => 1, 'status' => 'Faturado', 'valorTotal' => $gross, 'lancamento' => $entry, 'concluida_em' => $order->concluida_em ?: date('Y-m-d H:i:s')]);
            $this->event($id, 'status', $order->status . ' → Faturado');
            return $entry;
        });
    }

    public function transition($id, $status, $expected, $controlStock)
    {
        return $this->transaction(function () use ($id, $status, $expected, $controlStock) {
            $order = $this->lockOrder($id);
            if ($order->status !== $expected) {
                throw new DomainException('Esta OS foi atualizada por outra pessoa. Confira o quadro antes de mover novamente.');
            }
            if (! in_array($status, ['Aberto', 'Orçamento', 'Negociação', 'Aprovado', 'Aguardando Peças', 'Em Andamento', 'Finalizado', 'Cancelado'], true)) {
                throw new DomainException('Etapa inválida. O faturamento deve ser feito na OS.');
            }
            if ($order->entregue_em || $order->faturado || $order->status === 'Cancelado' || $order->status === 'Faturado') {
                throw new DomainException('Abra os detalhes da OS para conferir este atendimento encerrado.');
            }
            return $this->updateOrder($id, ['status' => $status, 'usuarios_id' => $order->usuarios_id, 'dataFinal' => $order->dataFinal], $controlStock);
        });
    }

    public function updateOrder($id, array $data, $controlStock)
    {
        return $this->transaction(function () use ($id, $data, $controlStock) {
            $order = $this->lockOrder($id);
            if ($order->entregue_em) {
                throw new DomainException('A entrega já foi registrada. Preserve esta OS e abra uma nova para o retorno do equipamento.');
            }
            $status = $data['status'] ?? $order->status;
            if (! in_array($status, self::STATUSES, true) && ! ($status === 'Faturado' && $order->status === 'Faturado')) {
                throw new DomainException('Selecione uma etapa válida. Utilize a ação Faturar para gerar o lançamento financeiro.');
            }
            $data['status'] = $status;
            $this->validateOrder(array_merge((array) $order, $data));
            $this->requireIntakeForExecution($id, $status, $order->status);
            if (isset($data['clientes_id']) && (int) $data['clientes_id'] !== (int) $order->clientes_id
                && $this->ci->db->where('os_id', $id)->count_all_results('equipamentos_os')) {
                throw new DomainException('Uma OS com equipamentos vinculados não pode mudar de cliente.');
            }
            if ($order->faturado && isset($data['clientes_id']) && (int) $data['clientes_id'] !== (int) $order->clientes_id) {
                throw new DomainException('O cliente de uma OS faturada deve ser preservado junto do lançamento financeiro.');
            }
            if ((int) $order->faturado && $data['status'] !== $order->status) {
                throw new DomainException('Uma OS faturada não pode mudar de status.');
            }
            $before = $order->status === 'Cancelado';
            $after = $data['status'] === 'Cancelado';
            if ($controlStock && $before !== $after) {
                $items = $this->ci->db->order_by('produtos_id')->get_where('produtos_os', ['os_id' => $id])->result();
                foreach ($items as $item) {
                    $this->stock($item->produtos_id, $item->quantidade, $after ? '+' : '-');
                }
            }
            if ($data['status'] !== $order->status) {
                $data['concluida_em'] = in_array($data['status'], ['Finalizado', 'Faturado'], true) ? date('Y-m-d H:i:s') : null;
                if (! in_array($data['status'], ['Finalizado', 'Faturado'], true)) {
                    $data['entregue_em'] = null;
                }
            }
            $this->ci->db->where('idOs', $id)->update('os', $data);
            if ($data['status'] !== $order->status) {
                $this->event($id, 'status', $order->status . ' → ' . $data['status']);
            }
            if (($data['usuarios_id'] ?? $order->usuarios_id) != $order->usuarios_id || ($data['dataFinal'] ?? $order->dataFinal) !== $order->dataFinal) {
                $this->event($id, 'planejamento', 'Responsável ou prazo atualizado. Prazo: ' . ($data['dataFinal'] ?? $order->dataFinal));
            }
            if (array_key_exists('dataFinal', $data) && $data['dataFinal'] !== $order->dataFinal) {
                $this->event($id, 'prazo', 'Prazo atualizado para: ' . $data['dataFinal']);
            }
            if ($after || (isset($data['clientes_id']) && $data['clientes_id'] != $order->clientes_id)) {
                $this->invalidateBudget($id);
            }
            foreach (['descricaoProduto', 'defeito', 'laudoTecnico'] as $field) {
                if (array_key_exists($field, $data) && (string) $data[$field] !== (string) $order->$field) {
                    $this->invalidateBudget($id);
                    $this->event($id, 'analise', 'Descrição, problema relatado ou diagnóstico atualizado. Confira o orçamento antes de solicitar nova aprovação.');
                    break;
                }
            }
            return true;
        });
    }

    public function deleteOrder($id, $controlStock)
    {
        return $this->transaction(function () use ($id, $controlStock) {
            $order = $this->lockOrder($id);
            if ($order->entregue_em) {
                throw new DomainException('A entrega já foi registrada. Preserve o histórico desta OS.');
            }
            if ($this->ci->db->from('os_vistorias')->join('equipamentos_os', 'equipamentos_os.idEquipamentos_os = os_vistorias.vinculo_id')->where('os_id', $id)->count_all_results()) {
                throw new DomainException('Esta OS possui vistoria de entrada e deve ser preservada no histórico.');
            }
            if ($this->ci->db->from('equipamento_recibos')->join('equipamentos_os', 'equipamentos_os.idEquipamentos_os = equipamento_recibos.vinculo_id')->where('os_id', $id)->count_all_results()) {
                throw new DomainException('Esta OS possui comprovante de movimentação física e deve ser preservada no histórico.');
            }
            $charges = $this->ci->db->get_where('cobrancas', ['os_id' => $id])->result();
            foreach ($charges as $charge) {
                if ($charge->status !== 'canceled') {
                    throw new DomainException('Cancele a cobrança antes de excluir a OS.');
                }
            }
            if ($controlStock && $order->status !== 'Cancelado') {
                foreach ($this->ci->db->order_by('produtos_id')->get_where('produtos_os', ['os_id' => $id])->result() as $item) {
                    $this->stock($item->produtos_id, $item->quantidade, '+');
                }
            }
            foreach (['cobrancas', 'servicos_os', 'produtos_os', 'anexos', 'anotacoes_os', 'equipamentos_os', 'os_checklist', 'os_aprovacoes', 'os_eventos'] as $table) {
                $this->ci->db->delete($table, ['os_id' => $id]);
            }
            $this->ci->db->delete('os', ['idOs' => $id]);
            $this->ci->db->where('os_id', $id)->where('status', 'pendente')->update('com_fila', ['status' => 'cancelado', 'erro' => 'OS excluída.']);
            if ($order->lancamento) {
                $this->ci->db->delete('lancamentos', ['idLancamentos' => $order->lancamento]);
            }
        });
    }

    public function checklist($id, $description = null, $itemId = null, $done = false)
    {
        return $this->transaction(function () use ($id, $description, $itemId, $done) {
            $this->editable($this->lockOrder($id));
            if ($itemId) {
                $item = $this->ci->db->get_where('os_checklist', ['id' => (int) $itemId, 'os_id' => (int) $id])->row();
                if (! $item) {
                    throw new DomainException('Item não encontrado.');
                }
                $this->ci->db->where('id', $item->id)->update('os_checklist', ['concluido' => (int) $done]);
                $description = $item->descricao . ($done ? ': concluído' : ': reaberto');
            } else {
                $description = trim((string) $description);
                if ($description === '' || mb_strlen($description) > 180) {
                    throw new DomainException('Descreva o item em até 180 caracteres.');
                }
                $this->ci->db->insert('os_checklist', ['os_id' => $id, 'descricao' => $description, 'created_at' => date('Y-m-d H:i:s')]);
            }
            $this->event($id, 'checklist', $description);
        });
    }

    private function budget($id, $includeAnalysis = true)
    {
        $this->ci->load->model('os_model');
        $totals = $this->ci->os_model->valorTotalOS($id);
        $order = $this->ci->db->get_where('os', ['idOs' => $id])->row();
        $snapshot = [
            'produtos' => $this->ci->db->select('p.descricao, i.quantidade, i.preco')->from('produtos_os i')->join('produtos p', 'p.idProdutos = i.produtos_id')->where('i.os_id', $id)->order_by('i.idProdutos_os')->get()->result_array(),
            'servicos' => $this->ci->db->select('s.nome, i.quantidade, i.preco')->from('servicos_os i')->join('servicos s', 's.idServicos = i.servicos_id')->where('i.os_id', $id)->order_by('i.idServicos_os')->get()->result_array(),
            'valor' => round($order->tipo_desconto ? $order->valor_desconto : $totals['totalProdutos'] + $totals['totalServico'], 2),
        ];
        if ($includeAnalysis) {
            $snapshot['analise'] = ['descricao' => $order->descricaoProduto, 'problema' => $order->defeito, 'diagnostico' => $order->laudoTecnico];
        }
        return json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function publishBudget($id, $snapshot, $notify = true)
    {
        $value = json_decode($snapshot, true)['valor'];
        if ($value <= 0) {
            throw new DomainException('Adicione produtos ou serviços com valor antes de solicitar aprovação.');
        }
        $fingerprint = hash('sha256', $snapshot);
        $latest = $this->ci->db->where('os_id', $id)->order_by('id', 'desc')->get('os_aprovacoes')->row();
        if ($latest && $latest->status === 'pendente' && hash_equals($latest->fingerprint, $fingerprint)) {
            return (int) $latest->id;
        }
        $this->invalidateBudget($id);
        $this->ci->db->insert('os_aprovacoes', ['os_id' => $id, 'status' => 'pendente', 'valor' => $value, 'snapshot' => $snapshot, 'fingerprint' => $fingerprint, 'created_at' => date('Y-m-d H:i:s')]);
        $approval = (int) $this->ci->db->insert_id();
        $this->event($id, 'orcamento', 'Orçamento disponível para aprovação na área do cliente. Valor: R$ ' . number_format($value, 2, ',', '.'), $notify ? ['aprovacao' => $approval] : []);
        return $approval;
    }

    public function requestApproval($id)
    {
        return $this->transaction(function () use ($id) {
            $this->editable($this->lockOrder($id));
            return $this->publishBudget($id, $this->budget($id));
        });
    }

    public function budgetWhatsAppPreview($id)
    {
        $order = $this->ci->db->get_where('os', ['idOs' => (int) $id])->row();
        if (! $order) {
            throw new DomainException('OS não encontrada.');
        }
        $snapshot = $this->budget((int) $id);
        $value = json_decode($snapshot, true)['valor'];
        $latest = $this->ci->db->where('os_id', (int) $id)->order_by('id', 'desc')->get('os_aprovacoes')->row();
        $same = $latest && hash_equals($latest->fingerprint, hash('sha256', $snapshot));
        $approval = $same && $latest->status === 'pendente' ? $latest : null;
        $this->ci->load->library('comunicacao_service');
        $preview = $this->ci->comunicacao_service->budgetWhatsApp($order, $same ? $latest : null);
        if ($order->entregue_em || $order->faturado || in_array($order->status, ['Cancelado', 'Faturado'], true)) {
            $preview['reason'] = 'Esta OS está encerrada para envio de orçamento.';
        } elseif ($value <= 0) {
            $preview['reason'] = 'Adicione serviços ou produtos com valor antes de enviar o orçamento.';
        } elseif ($same && in_array($latest->status, ['aprovado', 'rejeitado'], true)) {
            $preview['reason'] = 'O cliente já respondeu a este orçamento. Revise o atendimento antes de solicitar outra aprovação.';
        }
        $preview['value'] = $value;
        $preview['snapshot'] = json_decode($snapshot, true);
        $preview['approval'] = $approval ? (int) $approval->id : null;
        $preview['fingerprint'] = hash('sha256', json_encode([(int) $id, $snapshot, $preview['destination'], $preview['text'], $preview['rendered'], $preview['approval'], $preview['existing']['id'] ?? null, $preview['reason'], $preview['window']], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        unset($preview['rendered']);
        return $preview;
    }

    public function sendBudgetWhatsApp($id, $fingerprint, $confirmed)
    {
        return $this->transaction(function () use ($id, $fingerprint, $confirmed) {
            $order = $this->lockOrder($id);
            $this->editable($order);
            $preview = $this->budgetWhatsAppPreview($id);
            if (! is_string($fingerprint) || ! hash_equals($preview['fingerprint'], $fingerprint)) {
                throw new DomainException('O orçamento, o destinatário ou a mensagem mudou. Atualize a prévia e confira antes de enviar.');
            }
            if ($preview['reason'] !== '') {
                throw new DomainException($preview['reason']);
            }
            if (! $confirmed) {
                throw new DomainException('Confirme o destinatário e a mensagem antes de enviar.');
            }
            if ($preview['existing']) {
                if (in_array($preview['existing']['status'], ['falhou', 'cancelado'], true)) {
                    $this->ci->comunicacao_service->retryBudgetWhatsApp($preview['existing']['id']);
                    $this->event($id, 'comunicacao', 'Nova tentativa WhatsApp autorizada para a mensagem #' . $preview['existing']['id'] . '.');
                    return $this->budgetWhatsAppPreview($id)['existing'];
                }
                return $preview['existing'];
            }
            $approval = $this->publishBudget($id, $this->budget($id), false);
            $message = $this->ci->comunicacao_service->queueBudgetWhatsApp($order, $approval, $preview['destination'], $preview['text']);
            $this->event($id, 'comunicacao', 'Orçamento incluído na fila WhatsApp. Solicitação de aprovação #' . $approval . '.');
            return $message;
        });
    }

    public function answerApproval($id, $approvalId, $customerId, $answer)
    {
        return $this->transaction(function () use ($id, $approvalId, $customerId, $answer) {
            $order = $this->lockOrder($id);
            $this->editable($order);
            if ((int) $order->clientes_id !== (int) $customerId || ! in_array($answer, ['aprovado', 'rejeitado'], true)) {
                throw new DomainException('Solicitação inválida.');
            }
            $approval = $this->ci->db->get_where('os_aprovacoes', ['id' => $approvalId, 'os_id' => $id, 'status' => 'pendente'])->row();
            $hasAnalysis = $approval && array_key_exists('analise', json_decode($approval->snapshot, true));
            if (! $approval || ! hash_equals($approval->fingerprint, hash('sha256', $this->budget($id, $hasAnalysis)))) {
                throw new DomainException('O orçamento mudou. Solicite uma nova versão antes de responder.');
            }
            $this->ci->db->where('id', $approval->id)->update('os_aprovacoes', ['status' => $answer, 'cliente_id' => $customerId, 'respondido_em' => date('Y-m-d H:i:s')]);
            $this->event($id, 'orcamento', 'Cliente ' . ($answer === 'aprovado' ? 'aprovou' : 'rejeitou') . ' o orçamento de R$ ' . number_format($approval->valor, 2, ',', '.') . '.');
        });
    }
}
