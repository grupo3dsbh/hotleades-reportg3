<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Reports_charts extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Main charts page.
     * URL: /admin/reports_charts
     */
    public function index()
    {
        if (!is_admin() && !staff_can('view', 'reports')) {
            access_denied('reports_charts');
        }

        $data['title'] = _l('reports_charts_page_title');

        $this->load->view('reports_charts/charts', $data);
    }

    /**
     * AJAX proxy: forward the request to the core invoices_report endpoint
     * and return the JSON response directly.
     *
     * URL: POST /admin/reports_charts/get_data
     *
     * Accepts the same parameters as /admin/reports/invoices_report and
     * simply delegates to the Reports controller so we don't duplicate logic.
     */
    public function get_data()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if (!is_admin() && !staff_can('view', 'reports')) {
            echo json_encode(['error' => 'Access denied']);
            return;
        }

        // Delegate to the core Reports controller method
        $this->load->library('Reports_datatables', [], 'reports_dt');

        // Build the same parameter set the datatable sends
        $params = $this->input->post();

        // The core reports controller outputs directly; we forward via a
        // sub-request using CI's internal routing.
        // Cleaner approach: replicate the call the core Reports controller does.
        $this->load->model('invoices_model');
        $this->load->model('currencies_model');

        // Use CI's router to call the original controller method
        // so we don't duplicate the query logic.
        ob_start();
        $this->load->module('reports'); // load core module if applicable
        $result = $this->_forward_to_core_reports();
        $output = ob_get_clean();

        if ($result !== false) {
            echo json_encode($result);
        } else {
            echo $output; // already JSON from the core controller
        }
    }

    /**
     * Forward POST data to the core /admin/reports/invoices_report handler
     * by using CodeIgniter's internal routing mechanism.
     *
     * Returns false when the core controller echoes JSON directly (common case).
     */
    private function _forward_to_core_reports()
    {
        // Load and call the core Reports controller directly
        if (file_exists(APPPATH . 'controllers/admin/Reports.php')) {
            require_once APPPATH . 'controllers/admin/Reports.php';

            $reports = new Reports();

            ob_start();
            $reports->invoices_report();
            $json = ob_get_clean();

            echo $json; // pass through
        }

        return false;
    }
}
