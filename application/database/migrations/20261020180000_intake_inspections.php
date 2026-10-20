<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_intake_inspections extends CI_Migration
{
    public function up()
    {
        $this->db->query('CREATE TABLE os_vistorias (id INT AUTO_INCREMENT PRIMARY KEY, vinculo_id INT NOT NULL UNIQUE, estado VARCHAR(30) NOT NULL, condicoes TEXT NOT NULL, acessorios TEXT NOT NULL, responsavel VARCHAR(150) NOT NULL, created_at DATETIME NOT NULL, usuario_id INT NULL, FOREIGN KEY (vinculo_id) REFERENCES equipamentos_os(idEquipamentos_os)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $this->db->query('CREATE TABLE os_vistoria_fotos (id INT AUTO_INCREMENT PRIMARY KEY, vistoria_id INT NOT NULL, perspectiva VARCHAR(20) NOT NULL, observacao VARCHAR(500) NOT NULL, imagem MEDIUMBLOB NOT NULL, created_at DATETIME NOT NULL, usuario_id INT NULL, INDEX (vistoria_id), FOREIGN KEY (vistoria_id) REFERENCES os_vistorias(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    public function down()
    {
        $this->dbforge->drop_table('os_vistoria_fotos', true);
        $this->dbforge->drop_table('os_vistorias', true);
    }
}
