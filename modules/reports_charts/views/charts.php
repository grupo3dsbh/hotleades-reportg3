<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<!-- Reports Charts Module assets -->
<link rel="stylesheet" href="<?php echo module_dir_url('reports_charts', 'assets/css/reports_charts.css'); ?>">

<div id="wrapper">
  <div class="content">

    <!-- Page Header -->
    <div class="row">
      <div class="col-md-12">
        <div class="page-title-box">
          <h4 class="page-title">
            <i class="fa fa-bar-chart mright5"></i>
            <?php echo _l('reports_charts_page_title'); ?>
          </h4>
        </div>
      </div>
    </div>

    <!-- Toolbar: Period Selector + Chart Type -->
    <div class="row mtop10">
      <div class="col-md-7 col-sm-12">
        <div class="btn-group" id="rc-period-group" role="group">
          <button type="button" class="btn btn-default rc-btn-period active" data-period="this_month">
            <?php echo _l('this_month'); ?>
          </button>
          <button type="button" class="btn btn-default rc-btn-period" data-period="last_month">
            <?php echo _l('last_month'); ?>
          </button>
          <button type="button" class="btn btn-default rc-btn-period" data-period="this_year">
            <?php echo _l('this_year'); ?>
          </button>
          <button type="button" class="btn btn-default rc-btn-period" data-period="last_year">
            <?php echo _l('last_year'); ?>
          </button>
          <button type="button" class="btn btn-default rc-btn-period" data-period="custom" id="rc-btn-custom">
            <?php echo _l('custom'); ?>
          </button>
        </div>

        <!-- Custom range (hidden by default) -->
        <div id="rc-custom-range" style="display:none; margin-top:6px;">
          <div class="input-group" style="max-width:340px;">
            <input type="text" id="rc_date_from" class="form-control datepicker"
                   placeholder="<?php echo _l('date_from'); ?>" autocomplete="off">
            <span class="input-group-addon">—</span>
            <input type="text" id="rc_date_to" class="form-control datepicker"
                   placeholder="<?php echo _l('date_to'); ?>" autocomplete="off">
            <span class="input-group-btn">
              <button class="btn btn-primary" type="button" id="rc-btn-apply">
                <?php echo _l('apply'); ?>
              </button>
            </span>
          </div>
        </div>
      </div>

      <div class="col-md-5 col-sm-12 text-right" style="padding-top:2px;">
        <div class="btn-group" id="rc-type-group" role="group">
          <button type="button" class="btn btn-default rc-btn-type active" data-type="bar"
                  title="<?php echo _l('rc_bar_chart'); ?>">
            <i class="fa fa-bar-chart"></i> <?php echo _l('rc_bar_chart'); ?>
          </button>
          <button type="button" class="btn btn-default rc-btn-type" data-type="stacked"
                  title="<?php echo _l('rc_stacked_chart'); ?>">
            <i class="fa fa-tasks"></i> <?php echo _l('rc_stacked_chart'); ?>
          </button>
          <button type="button" class="btn btn-default rc-btn-type" data-type="pie"
                  title="<?php echo _l('rc_pie_chart'); ?>">
            <i class="fa fa-pie-chart"></i> <?php echo _l('rc_pie_chart'); ?>
          </button>
          <button type="button" class="btn btn-default rc-btn-type" data-type="gantt"
                  title="Gantt">
            <i class="fa fa-align-left"></i> Gantt
          </button>
        </div>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mtop15" id="rc-summary-cards">
      <div class="col-md-3 col-sm-6">
        <div class="rc-card rc-card-total">
          <div class="rc-card-icon"><i class="fa fa-file-text-o"></i></div>
          <div class="rc-card-info">
            <div class="rc-card-label"><?php echo _l('rc_total_invoiced'); ?></div>
            <div class="rc-card-value" id="rc-sum-total">—</div>
            <div class="rc-card-count" id="rc-sum-total-count"></div>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="rc-card rc-card-paid">
          <div class="rc-card-icon"><i class="fa fa-check-circle"></i></div>
          <div class="rc-card-info">
            <div class="rc-card-label"><?php echo _l('rc_paid'); ?></div>
            <div class="rc-card-value" id="rc-sum-paid">—</div>
            <div class="rc-card-count" id="rc-sum-paid-count"></div>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="rc-card rc-card-pending">
          <div class="rc-card-icon"><i class="fa fa-clock-o"></i></div>
          <div class="rc-card-info">
            <div class="rc-card-label"><?php echo _l('rc_not_paid'); ?></div>
            <div class="rc-card-value" id="rc-sum-pending">—</div>
            <div class="rc-card-count" id="rc-sum-pending-count"></div>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="rc-card rc-card-partial">
          <div class="rc-card-icon"><i class="fa fa-adjust"></i></div>
          <div class="rc-card-info">
            <div class="rc-card-label"><?php echo _l('rc_partially_paid'); ?></div>
            <div class="rc-card-value" id="rc-sum-partial">—</div>
            <div class="rc-card-count" id="rc-sum-partial-count"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Chart Panel -->
    <div class="row mtop10">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body" style="position:relative; min-height:300px;">

            <!-- Loading overlay -->
            <div id="rc-loading" style="display:none; position:absolute; top:50%; left:50%;
                 transform:translate(-50%,-50%); z-index:10; color:#337ab7;">
              <i class="fa fa-spinner fa-spin fa-2x"></i>
            </div>

            <!-- Empty state -->
            <div id="rc-empty" style="display:none; text-align:center; padding:60px 0;">
              <i class="fa fa-bar-chart fa-3x text-muted"></i>
              <p class="text-muted mtop10"><?php echo _l('no_data_found'); ?></p>
            </div>

            <!-- Bar / Stacked bar -->
            <div id="rc-wrap-bar">
              <canvas id="rc-chart-bar" height="110"></canvas>
            </div>

            <!-- Pie -->
            <div id="rc-wrap-pie" style="display:none;">
              <div class="row">
                <div class="col-md-6 col-md-offset-3">
                  <canvas id="rc-chart-pie"></canvas>
                </div>
              </div>
            </div>

            <!-- Gantt -->
            <div id="rc-wrap-gantt" style="display:none;">
              <div id="rc-gantt-container" class="table-responsive"></div>
            </div>

          </div>
        </div>
      </div>
    </div>

  </div><!-- /.content -->
</div><!-- /#wrapper -->

<!-- ================================================================
     Modal: Invoice List
     ================================================================ -->
<div class="modal fade" id="rc-modal-invoices" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title">
          <i class="fa fa-list-ul mright5"></i>
          <span id="rc-modal-title"><?php echo _l('invoices'); ?></span>
        </h4>
      </div>

      <div class="modal-body no-padding">

        <!-- Totals inside modal -->
        <div id="rc-modal-sums" style="display:flex; flex-wrap:wrap; gap:20px;
             padding:12px 18px; background:#f8f9fa; border-bottom:1px solid #e9ecef;">
          <div>
            <span style="font-size:11px;text-transform:uppercase;color:#777;font-weight:600;">
              <?php echo _l('rc_total_invoiced'); ?>:
            </span>
            <strong id="rc-ms-total">—</strong>
          </div>
          <div>
            <span style="font-size:11px;text-transform:uppercase;color:#28a745;font-weight:600;">
              <?php echo _l('rc_paid'); ?>:
            </span>
            <strong id="rc-ms-paid" class="text-success">—</strong>
          </div>
          <div>
            <span style="font-size:11px;text-transform:uppercase;color:#dc3545;font-weight:600;">
              <?php echo _l('rc_not_paid'); ?>:
            </span>
            <strong id="rc-ms-unpaid" class="text-danger">—</strong>
          </div>
          <div>
            <span style="font-size:11px;text-transform:uppercase;color:#e0a800;font-weight:600;">
              <?php echo _l('rc_partially_paid'); ?>:
            </span>
            <strong id="rc-ms-partial" style="color:#e0a800;">—</strong>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover table-condensed" id="rc-modal-table">
            <thead>
              <tr>
                <th><?php echo _l('invoice_number'); ?></th>
                <th><?php echo _l('client_name'); ?></th>
                <th><?php echo _l('invoice_date'); ?></th>
                <th><?php echo _l('invoice_due_date'); ?></th>
                <th class="text-right"><?php echo _l('invoice_total'); ?></th>
                <th class="text-right"><?php echo _l('invoice_amount_due'); ?></th>
                <th class="text-center"><?php echo _l('invoice_status'); ?></th>
                <th class="text-center"><?php echo _l('options'); ?></th>
              </tr>
            </thead>
            <tbody id="rc-modal-tbody">
              <tr>
                <td colspan="8" class="text-center">
                  <i class="fa fa-spinner fa-spin"></i>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </div><!-- /.modal-body -->

      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">
          <?php echo _l('close'); ?>
        </button>
      </div>

    </div>
  </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<!-- Reports Charts Module JS -->
<script src="<?php echo module_dir_url('reports_charts', 'assets/js/reports_charts.js'); ?>"></script>

<!-- Pass PHP config to the JS module -->
<script>
window.RCConfig = {
  ajaxUrl:  '<?php echo admin_url('reports/invoices_report'); ?>',
  labels: {
    paid:          '<?php echo _l('rc_paid'); ?>',
    not_paid:      '<?php echo _l('rc_not_paid'); ?>',
    partially_paid:'<?php echo _l('rc_partially_paid'); ?>',
    total:         '<?php echo _l('rc_total_invoiced'); ?>',
    invoices:      '<?php echo _l('invoices'); ?>',
    no_data:       '<?php echo _l('no_data_found'); ?>',
    view:          '<?php echo _l('view'); ?>',
  }
};
</script>

<?php init_tail(); ?>
</body>
</html>
