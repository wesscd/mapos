<?php

defined('BASEPATH') or exit('No direct script access allowed');

/** Read the selected brand once per request, including unauthenticated client pages. */
function mapos_baraotech_theme()
{
    static $enabled;
    if ($enabled === null) {
        $ci = &get_instance();
        $ci->load->database();
        $row = $ci->db->select('valor')->get_where('configuracoes', ['config' => 'app_theme'], 1)->row();
        $enabled = $row && $row->valor === 'baraotech';
    }
    return $enabled;
}
