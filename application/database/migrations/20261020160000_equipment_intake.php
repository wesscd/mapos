<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_equipment_intake extends CI_Migration
{
    public function up()
    {
        $this->db->query('CREATE TABLE equipamento_fotos (id INT AUTO_INCREMENT PRIMARY KEY, equipamento_id INT NOT NULL, imagem MEDIUMBLOB NOT NULL, created_at DATETIME NOT NULL, usuario_id INT NULL, INDEX (equipamento_id), FOREIGN KEY (equipamento_id) REFERENCES equipamentos(idEquipamentos)) ENGINE=InnoDB');
        $this->db->query('CREATE TABLE equipamento_recibos (id INT AUTO_INCREMENT PRIMARY KEY, vinculo_id INT NOT NULL, tipo VARCHAR(10) NOT NULL, dados MEDIUMTEXT NOT NULL, created_at DATETIME NOT NULL, usuario_id INT NULL, UNIQUE KEY recibo_tipo (vinculo_id, tipo), FOREIGN KEY (vinculo_id) REFERENCES equipamentos_os(idEquipamentos_os)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    public function down()
    {
        $this->dbforge->drop_table('equipamento_recibos', true);
        $this->dbforge->drop_table('equipamento_fotos', true);
    }
}
