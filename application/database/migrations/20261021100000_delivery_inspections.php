<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_delivery_inspections extends CI_Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE os_vistorias ADD tipo VARCHAR(10) NOT NULL DEFAULT 'entrada', ADD conferencia VARCHAR(20) NULL, ADD ressalvas TEXT NULL, DROP INDEX vinculo_id, ADD UNIQUE KEY vinculo_tipo (vinculo_id, tipo)");
    }

    public function down()
    {
        if ($this->db->where('tipo', 'saida')->count_all_results('os_vistorias')) {
            throw new RuntimeException('Existem vistorias de saída. Preserve o histórico antes de reverter esta migração.');
        }
        $this->db->query('ALTER TABLE os_vistorias DROP INDEX vinculo_tipo, ADD UNIQUE KEY vinculo_id (vinculo_id), DROP COLUMN tipo, DROP COLUMN conferencia, DROP COLUMN ressalvas');
    }
}
