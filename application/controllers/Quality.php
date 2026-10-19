<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Quality extends CI_Controller
{
    public function run()
    {
        if (! $this->input->is_cli_request() || ENVIRONMENT !== 'testing' || $this->db->database !== 'mapos_fork_test') {
            show_404();
        }
        $this->load->library('migration');
        $this->load->library('os_workflow');
        $this->load->model('os_model');
        if (! $this->migration->latest()) {
            throw new RuntimeException('Test migrations failed.');
        }
        require FCPATH . 'tests/suite.php';
    }
}
