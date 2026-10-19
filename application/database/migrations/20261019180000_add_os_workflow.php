<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_os_workflow extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_column('os', ['concluida_em' => ['type' => 'DATETIME', 'null' => true]]);
        $this->dbforge->add_column('produtos_os', ['custo_unitario' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true]]);
        $common = [
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'os_id' => ['type' => 'INT'],
            'created_at' => ['type' => 'DATETIME'],
        ];
        $tables = [
            'os_eventos' => [
                'usuario_id' => ['type' => 'INT', 'null' => true],
                'tipo' => ['type' => 'VARCHAR', 'constraint' => 30],
                'descricao' => ['type' => 'TEXT'],
            ],
            'os_checklist' => [
                'descricao' => ['type' => 'VARCHAR', 'constraint' => 180],
                'concluido' => ['type' => 'TINYINT', 'default' => 0],
            ],
            'os_aprovacoes' => [
                'status' => ['type' => 'VARCHAR', 'constraint' => 20],
                'valor' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
                'snapshot' => ['type' => 'TEXT'],
                'fingerprint' => ['type' => 'VARCHAR', 'constraint' => 64],
                'respondido_em' => ['type' => 'DATETIME', 'null' => true],
                'cliente_id' => ['type' => 'INT', 'null' => true],
            ],
        ];
        foreach ($tables as $name => $fields) {
            $this->dbforge->add_field(array_merge($common, $fields));
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('os_id');
            $this->dbforge->create_table($name, true, ['ENGINE' => 'InnoDB']);
        }
    }

    public function down()
    {
        $this->dbforge->drop_column('produtos_os', 'custo_unitario');
        $this->dbforge->drop_column('os', 'concluida_em');
        foreach (['os_aprovacoes', 'os_checklist', 'os_eventos'] as $table) {
            $this->dbforge->drop_table($table, true);
        }
    }
}
