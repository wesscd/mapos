<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Comunicacao_worker extends CI_Controller
{
    public function process()
    {
        if (! $this->input->is_cli_request()) {
            show_404();
        }
        $this->load->library('comunicacao_service');
        $this->comunicacao_service->process();
    }
}
