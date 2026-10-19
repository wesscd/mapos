<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_communication extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_column('os', ['entregue_em' => ['type' => 'DATETIME', 'null' => true]]);
        $this->dbforge->add_field([
            'id' => ['type' => 'INT'],
            'config' => ['type' => 'TEXT'],
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->create_table('com_config', true, ['ENGINE' => 'InnoDB']);
        $this->db->insert('com_config', ['id' => 1, 'config' => json_encode([
            'whatsapp' => false, 'email' => false, 'inicio' => 8, 'fim' => 18,
            'intervalo_dias' => 3, 'limite_lembretes' => 2,
        ])]);
        $this->dbforge->add_field([
            'cliente_id' => ['type' => 'INT'],
            'canal' => ['type' => 'VARCHAR', 'constraint' => 10],
            'evidencia' => ['type' => 'VARCHAR', 'constraint' => 250],
            'autor' => ['type' => 'VARCHAR', 'constraint' => 50],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->dbforge->add_key('cliente_id', true);
        $this->dbforge->create_table('com_preferencias', true, ['ENGINE' => 'InnoDB']);
        $this->dbforge->add_field([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'evento' => ['type' => 'VARCHAR', 'constraint' => 30],
            'canal' => ['type' => 'VARCHAR', 'constraint' => 10],
            'ativo' => ['type' => 'TINYINT', 'default' => 1],
            'texto' => ['type' => 'TEXT'],
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->create_table('com_modelos', true, ['ENGINE' => 'InnoDB']);
        $this->db->query('ALTER TABLE com_modelos ADD UNIQUE KEY evento_canal (evento, canal)');
        $texts = [
            'abertura' => 'Olá, {cliente}! Recebemos sua OS #{os}. Equipamento: {equipamento}. Prazo previsto: {prazo}. Acompanhe: {link}',
            'orcamento' => 'Olá, {cliente}! O orçamento da OS #{os} está disponível. Acesse sua área do cliente para conferir e aprovar a versão atual: {link}',
            'status' => 'Olá, {cliente}! Sua OS #{os} está na etapa: {status}. Acompanhe: {link}',
            'prazo' => 'Olá, {cliente}! O prazo previsto da OS #{os} foi atualizado para {prazo}. Acompanhe: {link}',
            'pronto' => 'Olá, {cliente}! O serviço da OS #{os} foi finalizado. Entre em contato para combinar a retirada. Acompanhe: {link}',
            'entrega' => 'Olá, {cliente}! Registramos a entrega da OS #{os}. Consulte o atendimento e as condições de garantia: {link}',
            'lembrete_orcamento' => 'Olá, {cliente}! O orçamento da OS #{os} aguarda sua resposta. Confira a versão atual em: {link}',
            'lembrete_retirada' => 'Olá, {cliente}! A OS #{os} está finalizada e aguarda retirada. Entre em contato para combinar. {link}',
        ];
        foreach ($texts as $event => $text) {
            foreach (['whatsapp', 'email'] as $channel) {
                $this->db->insert('com_modelos', ['evento' => $event, 'canal' => $channel, 'texto' => $text]);
            }
        }
        $this->dbforge->add_field([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'chave' => ['type' => 'VARCHAR', 'constraint' => 64],
            'dedup' => ['type' => 'VARCHAR', 'constraint' => 64],
            'os_id' => ['type' => 'INT', 'null' => true],
            'cliente_id' => ['type' => 'INT', 'null' => true],
            'evento' => ['type' => 'VARCHAR', 'constraint' => 30],
            'canal' => ['type' => 'VARCHAR', 'constraint' => 10],
            'destino' => ['type' => 'VARCHAR', 'constraint' => 254],
            'texto' => ['type' => 'TEXT'],
            'contexto' => ['type' => 'TEXT'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 15, 'default' => 'pendente'],
            'tentativas' => ['type' => 'INT', 'default' => 0],
            'teste' => ['type' => 'TINYINT', 'default' => 0],
            'erro' => ['type' => 'VARCHAR', 'constraint' => 250, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
            'agendado_em' => ['type' => 'DATETIME'],
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key(['status', 'agendado_em']);
        $this->dbforge->add_key('os_id');
        $this->dbforge->create_table('com_fila', true, ['ENGINE' => 'InnoDB']);
        $this->db->query('ALTER TABLE com_fila ADD UNIQUE KEY dedup (dedup), ADD UNIQUE KEY chave (chave)');
        $this->dbforge->add_field([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'fila_id' => ['type' => 'INT'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 15],
            'descricao' => ['type' => 'VARCHAR', 'constraint' => 250],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('fila_id');
        $this->dbforge->create_table('com_tentativas', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        foreach (['com_tentativas', 'com_fila', 'com_modelos', 'com_preferencias', 'com_config'] as $table) {
            $this->dbforge->drop_table($table, true);
        }
        $this->dbforge->drop_column('os', 'entregue_em');
    }
}
