<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_refine_customer_messages extends CI_Migration
{
    private const PREVIOUS = [
        'abertura' => 'Olá, {cliente}! Recebemos sua OS #{os}. Equipamento: {equipamento}. Prazo previsto: {prazo}. Acompanhe: {link}',
        'orcamento' => 'Olá, {cliente}! O orçamento da OS #{os} está disponível. Acesse sua área do cliente para conferir e aprovar a versão atual: {link}',
        'status' => 'Olá, {cliente}! Sua OS #{os} está na etapa: {status}. Acompanhe: {link}',
        'prazo' => 'Olá, {cliente}! O prazo previsto da OS #{os} foi atualizado para {prazo}. Acompanhe: {link}',
        'pronto' => 'Olá, {cliente}! O serviço da OS #{os} foi finalizado. Entre em contato para combinar a retirada. Acompanhe: {link}',
        'entrega' => 'Olá, {cliente}! Registramos a entrega da OS #{os}. Consulte o atendimento e as condições de garantia: {link}',
        'lembrete_orcamento' => 'Olá, {cliente}! O orçamento da OS #{os} aguarda sua resposta. Confira a versão atual em: {link}',
        'lembrete_retirada' => 'Olá, {cliente}! A OS #{os} está finalizada e aguarda retirada. Entre em contato para combinar. {link}',
    ];

    private const REVISED = [
        'abertura' => 'Olá, {cliente}! Sua solicitação de atendimento #{os} foi registrada. Equipamento informado: {equipamento}. Acompanhe o atendimento: {link}',
        'orcamento' => 'Olá, {cliente}! O orçamento do atendimento #{os} está disponível. Confira os serviços e valores e informe se aprova ou recusa: {link}',
        'status' => 'Olá, {cliente}! Atualização do atendimento #{os}: {status}. Consulte os detalhes: {link}',
        'prazo' => 'Olá, {cliente}! A previsão de conclusão do atendimento #{os} foi atualizada para {prazo}. Consulte os detalhes: {link}',
        'pronto' => 'Olá, {cliente}! O serviço do atendimento #{os} foi concluído. Entre em contato com nossa equipe para combinar a retirada do equipamento.',
        'entrega' => 'Olá, {cliente}! A entrega do equipamento do atendimento #{os} foi registrada. Obrigado pela confiança! Consulte os detalhes do serviço: {link}',
        'lembrete_orcamento' => 'Olá, {cliente}! O orçamento do atendimento #{os} aguarda sua decisão. Confira os serviços e valores e informe se aprova ou recusa: {link}',
        'lembrete_retirada' => 'Olá, {cliente}! O equipamento do atendimento #{os} aguarda retirada. Entre em contato com nossa equipe para combinar o melhor momento.',
    ];

    public function up()
    {
        $this->replaceDefaults(self::PREVIOUS, self::REVISED);
    }

    public function down()
    {
        $this->replaceDefaults(self::REVISED, self::PREVIOUS);
    }

    private function replaceDefaults(array $before, array $after)
    {
        foreach ($after as $event => $text) {
            // Binary comparison preserves even capitalization-only customizations.
            // Queued messages are historical snapshots; only future captures use the new copy.
            $this->db->query(
                'UPDATE com_modelos SET texto = ? WHERE evento = ? AND CAST(texto AS BINARY) = CAST(? AS BINARY)',
                [$text, $event, $before[$event]]
            );
        }
    }
}
