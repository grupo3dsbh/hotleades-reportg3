<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Reports Charts Module -->
<div id="reports-charts-module" class="reports-charts-wrap">

  <!-- Toolbar: Period Selector + Chart Type -->
  <div class="row mtop15 mbottom10">
    <div class="col-md-8 col-sm-12">
      <div class="btn-group chart-period-group" role="group">
        <button type="button" class="btn btn-default btn-period active" data-period="this_month"><?php echo _l('this_month'); ?></button>
        <button type="button" class="btn btn-default btn-period" data-period="last_month"><?php echo _l('last_month'); ?></button>
        <button type="button" class="btn btn-default btn-period" data-period="this_year"><?php echo _l('this_year'); ?></button>
        <button type="button" class="btn btn-default btn-period" data-period="last_year"><?php echo _l('last_year'); ?></button>
        <button type="button" class="btn btn-default btn-period" data-period="custom" id="btn-custom-period"><?php echo _l('custom'); ?></button>
      </div>
      <!-- Custom date range (hidden by default) -->
      <div id="custom-period-inputs" class="inline-flex mleft10" style="display:none!important;">
        <input type="text" id="chart_date_from" class="form-control datepicker input-sm" placeholder="<?php echo _l('date_from'); ?>" style="width:130px;display:inline-block;">
        <span class="mleft5 mright5">—</span>
        <input type="text" id="chart_date_to" class="form-control datepicker input-sm" placeholder="<?php echo _l('date_to'); ?>" style="width:130px;display:inline-block;">
        <button type="button" class="btn btn-primary btn-sm mleft5" id="btn-apply-custom"><?php echo _l('apply'); ?></button>
      </div>
    </div>
    <div class="col-md-4 col-sm-12 text-right">
      <div class="btn-group chart-type-group" role="group" title="<?php echo _l('chart_type'); ?>">
        <button type="button" class="btn btn-default btn-chart-type active" data-type="bar" title="<?php echo _l('bar_chart'); ?>">
          <i class="fa fa-bar-chart"></i>
        </button>
        <button type="button" class="btn btn-default btn-chart-type" data-type="stacked" title="<?php echo _l('stacked_bar'); ?>">
          <i class="fa fa-tasks"></i>
        </button>
        <button type="button" class="btn btn-default btn-chart-type" data-type="pie" title="<?php echo _l('pie_chart'); ?>">
          <i class="fa fa-pie-chart"></i>
        </button>
        <button type="button" class="btn btn-default btn-chart-type" data-type="gantt" title="Gantt">
          <i class="fa fa-align-left"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="row summary-cards mtop10 mbottom15" id="summary-cards">
    <div class="col-md-3 col-sm-6">
      <div class="summary-card card-total">
        <div class="card-icon"><i class="fa fa-file-text-o"></i></div>
        <div class="card-body">
          <div class="card-label"><?php echo _l('total_invoiced'); ?></div>
          <div class="card-value" id="sum-total">—</div>
          <div class="card-count" id="sum-total-count"></div>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="summary-card card-paid">
        <div class="card-icon"><i class="fa fa-check-circle"></i></div>
        <div class="card-body">
          <div class="card-label"><?php echo _l('paid'); ?></div>
          <div class="card-value" id="sum-paid">—</div>
          <div class="card-count" id="sum-paid-count"></div>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="summary-card card-pending">
        <div class="card-icon"><i class="fa fa-clock-o"></i></div>
        <div class="card-body">
          <div class="card-label"><?php echo _l('not_paid'); ?></div>
          <div class="card-value" id="sum-pending">—</div>
          <div class="card-count" id="sum-pending-count"></div>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="summary-card card-partial">
        <div class="card-icon"><i class="fa fa-adjust"></i></div>
        <div class="card-body">
          <div class="card-label"><?php echo _l('partially_paid'); ?></div>
          <div class="card-value" id="sum-partial">—</div>
          <div class="card-count" id="sum-partial-count"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Chart Container -->
  <div class="row">
    <div class="col-md-12">
      <div class="panel_s chart-panel">
        <div class="panel-body">
          <div id="chart-loading" class="chart-loading-overlay" style="display:none;">
            <i class="fa fa-spinner fa-spin fa-2x"></i>
          </div>
          <div id="chart-empty" class="chart-empty-state" style="display:none;">
            <i class="fa fa-bar-chart fa-3x text-muted"></i>
            <p class="text-muted mtop10"><?php echo _l('no_data_found'); ?></p>
          </div>
          <!-- Bar / Stacked Bar -->
          <div id="chart-bar-wrap" class="chart-wrap">
            <canvas id="chart-bar" height="110"></canvas>
          </div>
          <!-- Pie -->
          <div id="chart-pie-wrap" class="chart-wrap" style="display:none;">
            <div class="row">
              <div class="col-md-6 col-md-offset-3">
                <canvas id="chart-pie"></canvas>
              </div>
            </div>
          </div>
          <!-- Gantt (timeline) -->
          <div id="chart-gantt-wrap" class="chart-wrap" style="display:none;">
            <div id="gantt-container"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div><!-- /reports-charts-module -->

<!-- ============================================================
     Modal: Invoice List for selected period / month
     ============================================================ -->
<div class="modal fade" id="modal-invoices-list" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title">
          <i class="fa fa-list-ul mright5"></i>
          <span id="modal-invoices-title"><?php echo _l('invoices'); ?></span>
        </h4>
      </div>
      <div class="modal-body no-padding">
        <!-- Summary row inside modal -->
        <div class="modal-summary-row" id="modal-summary-row">
          <div class="modal-sum-item">
            <span class="modal-sum-label"><?php echo _l('total'); ?>:</span>
            <span class="modal-sum-value" id="modal-sum-total">—</span>
          </div>
          <div class="modal-sum-item">
            <span class="modal-sum-label text-success"><?php echo _l('paid'); ?>:</span>
            <span class="modal-sum-value text-success" id="modal-sum-paid">—</span>
          </div>
          <div class="modal-sum-item">
            <span class="modal-sum-label text-danger"><?php echo _l('not_paid'); ?>:</span>
            <span class="modal-sum-value text-danger" id="modal-sum-unpaid">—</span>
          </div>
          <div class="modal-sum-item">
            <span class="modal-sum-label text-warning"><?php echo _l('partially_paid'); ?>:</span>
            <span class="modal-sum-value text-warning" id="modal-sum-partial">—</span>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover table-striped" id="modal-invoices-table">
            <thead>
              <tr>
                <th><?php echo _l('invoice_number'); ?></th>
                <th><?php echo _l('client_name'); ?></th>
                <th><?php echo _l('date'); ?></th>
                <th><?php echo _l('due_date'); ?></th>
                <th class="text-right"><?php echo _l('total'); ?></th>
                <th class="text-right"><?php echo _l('amount_open'); ?></th>
                <th class="text-center"><?php echo _l('status'); ?></th>
                <th class="text-center"><?php echo _l('options'); ?></th>
              </tr>
            </thead>
            <tbody id="modal-invoices-tbody">
              <tr>
                <td colspan="8" class="text-center">
                  <i class="fa fa-spinner fa-spin"></i> <?php echo _l('loading'); ?>...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
      </div>
    </div>
  </div>
</div>

<!-- Chart.js + dependencies -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
  // Pass PHP vars to JS
  window.ReportsChartsConfig = {
    ajaxUrl: '<?php echo admin_url("reports/invoices_report"); ?>',
    csrfName: '<?php echo $this->security->get_csrf_token_name(); ?>',
    csrfHash: '<?php echo $this->security->get_csrf_hash(); ?>',
    adminUrl: '<?php echo admin_url(); ?>',
    baseUrl: '<?php echo base_url(); ?>',
    currency: '<?php echo get_base_currency() ? get_base_currency()->symbol : "$"; ?>',
    labels: {
      paid: '<?php echo _l("paid"); ?>',
      not_paid: '<?php echo _l("not_paid"); ?>',
      partially_paid: '<?php echo _l("partially_paid"); ?>',
      total: '<?php echo _l("total"); ?>',
      invoices: '<?php echo _l("invoices"); ?>',
      no_data: '<?php echo _l("no_data_found"); ?>',
    }
  };
</script>
