<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_equipment_checklists extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_column('equipamentos', ['checklist_tipo' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true]]);
        $this->db->query('CREATE TABLE check_modelos (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, tipo VARCHAR(20) NOT NULL UNIQUE, nome VARCHAR(100) NOT NULL, versao INT NOT NULL DEFAULT 1, ativo TINYINT NOT NULL DEFAULT 1, itens MEDIUMTEXT NOT NULL, atualizado_por INT NULL, updated_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $this->db->query('CREATE TABLE os_check_grupos (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, os_id INT NOT NULL, alvo INT NOT NULL DEFAULT 0, equipamento_nome VARCHAR(180) NOT NULL, tipo VARCHAR(20) NOT NULL, modelo_nome VARCHAR(100) NOT NULL, modelo_versao INT NOT NULL, criado_por INT NULL, created_at DATETIME NOT NULL, UNIQUE KEY os_alvo (os_id, alvo)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $this->db->query('CREATE TABLE os_check_itens (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, grupo_id INT NOT NULL, fase VARCHAR(15) NOT NULL, descricao VARCHAR(180) NOT NULL, escopo VARCHAR(20) NOT NULL, resultado VARCHAR(20) NOT NULL DEFAULT "pendente", observacoes TEXT NULL, autorizacao VARCHAR(500) NULL, atualizado_por INT NULL, updated_at DATETIME NULL, versao INT NOT NULL DEFAULT 1, INDEX (grupo_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $this->db->query('CREATE TABLE os_check_fotos (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, item_id INT NOT NULL, imagem MEDIUMBLOB NOT NULL, criado_por INT NULL, created_at DATETIME NOT NULL, INDEX (item_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $this->db->query('CREATE TABLE os_check_registros (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, item_id INT NOT NULL, usuario_id INT NULL, dados TEXT NOT NULL, created_at DATETIME NOT NULL, INDEX (item_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $commonAnalysis = ['Inspecionar ventoinhas e temperaturas', 'Verificar saúde e espaço dos discos', 'Testar memória e estabilidade', 'Verificar sinais de malware, dentro do acesso autorizado', 'Conferir portas e conexões'];
        foreach (['notebook' => ['Notebook', ['Testar bateria e carregamento', 'Testar teclado, touchpad e tela']], 'desktop' => ['Desktop', ['Verificar fonte de alimentação e cabos', 'Conferir cooler e water cooler, quando existente']], 'outros' => ['Outros equipamentos', ['Conferir funcionamento e conexões do equipamento']]] as $type => [$name, $specific]) {
            $items = [];
            foreach ($type === 'outros' ? $specific : array_merge($commonAnalysis, $specific) as $description) {
                $items[] = ['fase' => 'analise', 'descricao' => $description, 'escopo' => 'verificacao'];
            }
            $interventions = $type === 'outros' ? [] : ['Limpeza interna', 'Troca de pasta térmica'];
            if ($type === 'desktop') {
                $interventions[] = 'Limpeza e manutenção do water cooler, se aplicável';
            }
            foreach ($interventions as $description) {
                $items[] = ['fase' => 'execucao', 'descricao' => $description, 'escopo' => 'autorizacao'];
            }
            foreach (['Conferir montagem e conexões após o serviço', 'Testar funcionamento e estabilidade após o serviço', 'Conferir temperaturas após o serviço'] as $description) {
                $items[] = ['fase' => 'execucao', 'descricao' => $description, 'escopo' => 'verificacao'];
            }
            $this->db->insert('check_modelos', ['tipo' => $type, 'nome' => $name, 'itens' => json_encode($items, JSON_UNESCAPED_UNICODE), 'updated_at' => date('Y-m-d H:i:s')]);
        }
        $this->db->data_cache = [];
    }

    public function down()
    {
        $this->db->data_cache = [];
        if ($this->db->table_exists('os_check_grupos') && $this->db->count_all('os_check_grupos')) {
            throw new RuntimeException('Existem checklists de atendimento. Preserve o histórico antes de reverter esta migração.');
        }
        foreach (['os_check_registros', 'os_check_fotos', 'os_check_itens', 'os_check_grupos', 'check_modelos'] as $table) {
            $this->dbforge->drop_table($table, true);
        }
        if ($this->db->field_exists('checklist_tipo', 'equipamentos')) {
            $this->dbforge->drop_column('equipamentos', 'checklist_tipo');
        }
    }
}
