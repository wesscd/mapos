<?php

defined('BASEPATH') or exit('No direct script access allowed');

class MY_Security extends CI_Security
{
    public function csrf_set_cookie()
    {
        $result = parent::csrf_set_cookie();
        if (! is_cli() && config_item('csrf_protection')) {
            header('X-CSRF-Token: ' . $this->get_csrf_hash());
        }

        return $result;
    }
}
