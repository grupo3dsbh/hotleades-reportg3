<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Reports_charts extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $data['title'] = 'Reports Charts';
        $this->load->view('reports_charts/charts', $data);
    }
}
