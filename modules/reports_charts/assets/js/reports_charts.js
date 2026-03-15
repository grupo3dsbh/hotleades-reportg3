/**
 * Reports Charts Module
 * Provides interactive chart visualizations for the invoices report page.
 *
 * Dependencies: Chart.js 4.x, jQuery, Bootstrap 3 modal
 */
(function ($) {
  'use strict';

  /* ------------------------------------------------------------------ */
  /* Constants & helpers                                                  */
  /* ------------------------------------------------------------------ */

  var CFG = window.ReportsChartsConfig || {};

  var STATUS = {
    PAID:    2,
    UNPAID:  1,
    PARTIAL: 3
  };

  var COLORS = {
    paid:    'rgba( 40, 167,  69, 0.85)',
    unpaid:  'rgba(220,  53,  69, 0.85)',
    partial: 'rgba(255, 193,   7, 0.85)',
    total:   'rgba( 23, 162, 184, 0.85)',
    paidBorder:    'rgba( 40, 167,  69, 1)',
    unpaidBorder:  'rgba(220,  53,  69, 1)',
    partialBorder: 'rgba(255, 193,   7, 1)',
    totalBorder:   'rgba( 23, 162, 184, 1)',
  };

  /** Parse "$1,200.00" → 1200.00 */
  function parseMoney(str) {
    if (!str) return 0;
    return parseFloat(str.replace(/[^0-9.\-]/g, '')) || 0;
  }

  /** Format number → "$1,200.00" */
  function formatMoney(val) {
    var sym = (CFG.currency || '$');
    return sym + parseFloat(val).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  /** Extract invoice/client id from href  e.g. /admin/invoices/list_invoices/141 → 141 */
  function extractId(href) {
    var m = href.match(/\/(\d+)\s*$/);
    return m ? m[1] : null;
  }

  /** Detect status from HTML string */
  function detectStatus(html) {
    if (/invoice-status-2/.test(html)) return STATUS.PAID;
    if (/invoice-status-3/.test(html)) return STATUS.PARTIAL;
    return STATUS.UNPAID;
  }

  /** Parse raw aaData rows into structured objects */
  function parseRow(row) {
    var invHtml    = row[0];
    var clientHtml = row[1];
    var year       = row[2];
    var dateFrom   = row[3];
    var dateTo     = row[4];
    var subtotal   = parseMoney(row[5]);
    var total      = parseMoney(row[6]);
    var amountOpen = parseMoney(row[12]);
    var statusHtml = row[13];

    // Extract links
    var $invEl    = $(invHtml.trim());
    var $clientEl = $(clientHtml.trim());

    var invHref    = $invEl.attr('href') || '';
    var invNum     = $.trim($invEl.text());
    var clientHref = $clientEl.attr('href') || '';
    var clientName = $.trim($clientEl.text());

    var status = detectStatus(statusHtml);
    var paid   = (status === STATUS.PAID)    ? total : (status === STATUS.PARTIAL ? total - amountOpen : 0);

    return {
      invoiceId:   extractId(invHref),
      invoiceNum:  invNum,
      invoiceHref: invHref,
      clientId:    extractId(clientHref),
      clientName:  clientName,
      clientHref:  clientHref,
      year:        year,
      dateFrom:    dateFrom,
      dateTo:      dateTo,
      subtotal:    subtotal,
      total:       total,
      amountOpen:  amountOpen,
      paid:        paid,
      status:      status,
      statusHtml:  statusHtml,
    };
  }

  /** Group parsed rows by YYYY-MM */
  function groupByMonth(rows) {
    var groups = {};
    rows.forEach(function (r) {
      var key = r.dateFrom ? r.dateFrom.substring(0, 7) : 'Unknown';
      if (!groups[key]) {
        groups[key] = { label: key, invoices: [], total: 0, paid: 0, unpaid: 0, partial: 0 };
      }
      var g = groups[key];
      g.invoices.push(r);
      g.total += r.total;
      if (r.status === STATUS.PAID)    { g.paid    += r.total; }
      else if (r.status === STATUS.PARTIAL) { g.partial += r.total; g.unpaid += r.amountOpen; }
      else                             { g.unpaid  += r.total; }
    });
    return groups;
  }

  /* ------------------------------------------------------------------ */
  /* Module State                                                         */
  /* ------------------------------------------------------------------ */

  var state = {
    period:       'this_month',
    dateFrom:     '',
    dateTo:       '',
    chartType:    'bar',
    rawRows:      [],
    parsedRows:   [],
    groupedData:  {},
    barChart:     null,
    pieChart:     null,
  };

  /* ------------------------------------------------------------------ */
  /* Data fetching                                                        */
  /* ------------------------------------------------------------------ */

  function buildRequestData(period, dateFrom, dateTo) {
    var data = {
      draw:             1,
      start:            0,
      length:           9999,       // fetch all records
      'search[value]':  '',
      'search[regex]':  false,
      report_months:    period,
      report_from:      dateFrom || '',
      report_to:        dateTo   || '',
    };
    // Build column definitions expected by the server
    for (var i = 0; i <= 13; i++) {
      data['columns[' + i + '][data]']            = i;
      data['columns[' + i + '][name]']            = '';
      data['columns[' + i + '][searchable]']      = true;
      data['columns[' + i + '][orderable]']       = true;
      data['columns[' + i + '][search][value]']   = '';
      data['columns[' + i + '][search][regex]']   = false;
    }
    data['order[0][column]'] = 3;
    data['order[0][dir]']    = 'asc';
    // CSRF
    data[CFG.csrfName] = CFG.csrfHash;
    return data;
  }

  function fetchData(callback) {
    showLoading(true);
    hideEmpty();

    $.ajax({
      url:      CFG.ajaxUrl,
      type:     'POST',
      dataType: 'json',
      data:     buildRequestData(state.period, state.dateFrom, state.dateTo),
      success:  function (resp) {
        showLoading(false);
        // Refresh CSRF token if provided in response headers or elsewhere
        if (resp && resp.aaData) {
          state.rawRows    = resp.aaData;
          state.parsedRows = resp.aaData.map(parseRow);
          state.groupedData = groupByMonth(state.parsedRows);
          updateSummaryCards(resp.sums, state.parsedRows);
          renderCurrentChart();
          if (typeof callback === 'function') callback(null, resp);
        } else {
          showEmpty();
        }
      },
      error: function (xhr, status, err) {
        showLoading(false);
        showEmpty();
        console.error('[ReportsCharts] fetch error', err);
      }
    });
  }

  /* ------------------------------------------------------------------ */
  /* Summary Cards                                                        */
  /* ------------------------------------------------------------------ */

  function updateSummaryCards(sums, rows) {
    var totalVal   = 0, paidVal = 0, pendingVal = 0, partialVal = 0;
    var totalCount = rows.length;
    var paidCount  = 0, pendingCount = 0, partialCount = 0;

    rows.forEach(function (r) {
      totalVal += r.total;
      if (r.status === STATUS.PAID)    { paidVal    += r.total;  paidCount++;    }
      else if (r.status === STATUS.PARTIAL) { partialVal += r.total; partialCount++; pendingVal += r.amountOpen; }
      else                             { pendingVal += r.total;  pendingCount++; }
    });

    $('#sum-total').text(formatMoney(totalVal));
    $('#sum-total-count').text(totalCount + ' ' + (CFG.labels.invoices || 'invoices'));
    $('#sum-paid').text(formatMoney(paidVal));
    $('#sum-paid-count').text(paidCount + ' ' + (CFG.labels.invoices || 'invoices'));
    $('#sum-pending').text(formatMoney(pendingVal));
    $('#sum-pending-count').text(pendingCount + ' ' + (CFG.labels.invoices || 'invoices'));
    $('#sum-partial').text(formatMoney(partialVal));
    $('#sum-partial-count').text(partialCount + ' ' + (CFG.labels.invoices || 'invoices'));
  }

  /* ------------------------------------------------------------------ */
  /* Chart rendering                                                      */
  /* ------------------------------------------------------------------ */

  function renderCurrentChart() {
    var keys = Object.keys(state.groupedData).sort();
    if (!keys.length) { showEmpty(); return; }

    switch (state.chartType) {
      case 'bar':     renderBarChart(keys, false); break;
      case 'stacked': renderBarChart(keys, true);  break;
      case 'pie':     renderPieChart(keys);         break;
      case 'gantt':   renderGanttChart();           break;
    }
  }

  /* -- Bar / Stacked Bar -------------------------------------------- */
  function renderBarChart(keys, stacked) {
    showWrap('bar');

    var labels   = keys;
    var paid     = keys.map(function (k) { return state.groupedData[k].paid.toFixed(2); });
    var unpaid   = keys.map(function (k) { return state.groupedData[k].unpaid.toFixed(2); });
    var partial  = keys.map(function (k) { return state.groupedData[k].partial.toFixed(2); });
    var totals   = keys.map(function (k) { return state.groupedData[k].total.toFixed(2); });

    if (state.barChart) { state.barChart.destroy(); }

    var datasets = [
      {
        label:           CFG.labels.paid || 'Paid',
        data:            paid,
        backgroundColor: COLORS.paid,
        borderColor:     COLORS.paidBorder,
        borderWidth:     1,
        stack:           'stack1',
      },
      {
        label:           CFG.labels.not_paid || 'Not Paid',
        data:            unpaid,
        backgroundColor: COLORS.unpaid,
        borderColor:     COLORS.unpaidBorder,
        borderWidth:     1,
        stack:           'stack1',
      },
      {
        label:           CFG.labels.partially_paid || 'Partial',
        data:            partial,
        backgroundColor: COLORS.partial,
        borderColor:     COLORS.partialBorder,
        borderWidth:     1,
        stack:           'stack1',
      },
    ];

    if (!stacked) {
      // Side-by-side: add total as extra bar and remove stack
      datasets.forEach(function (d) { delete d.stack; });
      datasets.unshift({
        label:           CFG.labels.total || 'Total',
        data:            totals,
        backgroundColor: COLORS.total,
        borderColor:     COLORS.totalBorder,
        borderWidth:     1,
      });
    }

    var ctx = document.getElementById('chart-bar').getContext('2d');
    state.barChart = new Chart(ctx, {
      type: 'bar',
      data: { labels: labels, datasets: datasets },
      options: {
        responsive:          true,
        maintainAspectRatio: true,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend:  { position: 'top' },
          tooltip: {
            callbacks: {
              label: function (ctx) {
                return ' ' + ctx.dataset.label + ': ' + formatMoney(ctx.parsed.y);
              }
            }
          }
        },
        scales: {
          x: { stacked: stacked },
          y: {
            stacked: stacked,
            ticks:   {
              callback: function (val) { return formatMoney(val); }
            }
          }
        },
        onClick: function (evt, elements) {
          if (!elements || !elements.length) return;
          var idx = elements[0].index;
          var key = keys[idx];
          openInvoicesModal(key, state.groupedData[key]);
        }
      }
    });
  }

  /* -- Pie ----------------------------------------------------------- */
  function renderPieChart(keys) {
    showWrap('pie');

    var totalPaid    = 0, totalUnpaid = 0, totalPartial = 0;
    keys.forEach(function (k) {
      totalPaid    += state.groupedData[k].paid;
      totalUnpaid  += state.groupedData[k].unpaid;
      totalPartial += state.groupedData[k].partial;
    });

    if (state.pieChart) { state.pieChart.destroy(); }

    var ctx = document.getElementById('chart-pie').getContext('2d');
    state.pieChart = new Chart(ctx, {
      type: 'pie',
      data: {
        labels: [
          CFG.labels.paid    || 'Paid',
          CFG.labels.not_paid || 'Not Paid',
          CFG.labels.partially_paid || 'Partial'
        ],
        datasets: [{
          data:            [totalPaid.toFixed(2), totalUnpaid.toFixed(2), totalPartial.toFixed(2)],
          backgroundColor: [COLORS.paid, COLORS.unpaid, COLORS.partial],
          borderColor:     [COLORS.paidBorder, COLORS.unpaidBorder, COLORS.partialBorder],
          borderWidth:     2,
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'bottom' },
          tooltip: {
            callbacks: {
              label: function (ctx) {
                var total = ctx.dataset.data.reduce(function (a, b) { return parseFloat(a) + parseFloat(b); }, 0);
                var pct   = total > 0 ? ((parseFloat(ctx.parsed) / total) * 100).toFixed(1) : 0;
                return ' ' + ctx.label + ': ' + formatMoney(ctx.parsed) + ' (' + pct + '%)';
              }
            }
          }
        },
        onClick: function (evt, elements) {
          if (!elements || !elements.length) return;
          var labelIdx = elements[0].index;
          var statusFilter = [STATUS.PAID, STATUS.UNPAID, STATUS.PARTIAL][labelIdx];
          openInvoicesModalFiltered(null, statusFilter);
        }
      }
    });
  }

  /* -- Gantt (timeline) --------------------------------------------- */
  function renderGanttChart() {
    showWrap('gantt');
    var container = document.getElementById('gantt-container');
    container.innerHTML = '';

    var rows = state.parsedRows.slice().sort(function (a, b) {
      return a.dateFrom < b.dateFrom ? -1 : a.dateFrom > b.dateFrom ? 1 : 0;
    });

    if (!rows.length) { showEmpty(); return; }

    // Find min/max dates for scaling
    var minDate = new Date(rows[0].dateFrom);
    var maxDate = new Date(rows[rows.length - 1].dateTo || rows[rows.length - 1].dateFrom);
    var span    = Math.max((maxDate - minDate) / 86400000, 1); // days

    var html = '<table class="table table-condensed gantt-table"><thead><tr>' +
      '<th style="width:160px">' + (CFG.labels.invoices || 'Invoice') + '</th>' +
      '<th>Cliente</th>' +
      '<th>Total</th>' +
      '<th>Timeline</th>' +
      '</tr></thead><tbody>';

    rows.forEach(function (r) {
      var start  = new Date(r.dateFrom);
      var end    = new Date(r.dateTo || r.dateFrom);
      var left   = Math.max(0, ((start - minDate) / 86400000 / span) * 100);
      var width  = Math.max(2, ((end   - start)   / 86400000 / span) * 100);

      var statusClass = r.status === STATUS.PAID ? 'gantt-paid'
                      : r.status === STATUS.PARTIAL ? 'gantt-partial'
                      : 'gantt-unpaid';

      html += '<tr class="gantt-row" data-inv="' + r.invoiceId + '">' +
        '<td><a href="' + r.invoiceHref + '" target="_blank">' + r.invoiceNum + '</a></td>' +
        '<td>' + r.clientName + '</td>' +
        '<td>' + formatMoney(r.total) + '</td>' +
        '<td class="gantt-track-cell">' +
          '<div class="gantt-track">' +
            '<div class="gantt-bar ' + statusClass + '" style="left:' + left.toFixed(2) + '%;width:' + width.toFixed(2) + '%;" ' +
              'title="' + r.invoiceNum + ' | ' + r.dateFrom + ' → ' + r.dateTo + ' | ' + formatMoney(r.total) + '">' +
            '</div>' +
          '</div>' +
        '</td>' +
      '</tr>';
    });

    html += '</tbody></table>';
    container.innerHTML = html;
  }

  /* ------------------------------------------------------------------ */
  /* Modal: Invoice List                                                  */
  /* ------------------------------------------------------------------ */

  function openInvoicesModal(monthKey, group) {
    var title = (CFG.labels.invoices || 'Invoices') + ' — ' + monthKey;
    $('#modal-invoices-title').text(title);
    populateModalTable(group.invoices);
    updateModalSummary(group.invoices);
    $('#modal-invoices-list').modal('show');
  }

  function openInvoicesModalFiltered(monthKey, statusFilter) {
    var rows = state.parsedRows.filter(function (r) {
      var matchMonth  = !monthKey     || r.dateFrom.substring(0, 7) === monthKey;
      var matchStatus = !statusFilter || r.status === statusFilter;
      return matchMonth && matchStatus;
    });

    var title = CFG.labels.invoices || 'Invoices';
    if (monthKey) title += ' — ' + monthKey;
    $('#modal-invoices-title').text(title);
    populateModalTable(rows);
    updateModalSummary(rows);
    $('#modal-invoices-list').modal('show');
  }

  function updateModalSummary(rows) {
    var total = 0, paid = 0, unpaid = 0, partial = 0;
    rows.forEach(function (r) {
      total += r.total;
      if (r.status === STATUS.PAID)         { paid    += r.total;  }
      else if (r.status === STATUS.PARTIAL) { partial += r.total;  unpaid += r.amountOpen; }
      else                                  { unpaid  += r.total;  }
    });
    $('#modal-sum-total').text(formatMoney(total));
    $('#modal-sum-paid').text(formatMoney(paid));
    $('#modal-sum-unpaid').text(formatMoney(unpaid));
    $('#modal-sum-partial').text(formatMoney(partial));
  }

  function populateModalTable(rows) {
    var tbody = $('#modal-invoices-tbody');
    tbody.empty();

    if (!rows || !rows.length) {
      tbody.html('<tr><td colspan="8" class="text-center text-muted">' + (CFG.labels.no_data || 'No data found') + '</td></tr>');
      return;
    }

    rows.forEach(function (r) {
      var statusLabel = r.statusHtml ? $(r.statusHtml).text().trim() : '';
      var statusClass = r.status === STATUS.PAID    ? 'success'
                      : r.status === STATUS.PARTIAL ? 'warning'
                      : 'danger';

      var row = '<tr>' +
        '<td><strong>' + r.invoiceNum + '</strong></td>' +
        '<td>' +
          '<a href="' + r.clientHref + '" target="_blank">' +
            r.clientName +
          '</a>' +
        '</td>' +
        '<td>' + r.dateFrom + '</td>' +
        '<td>' + r.dateTo + '</td>' +
        '<td class="text-right">' + formatMoney(r.total) + '</td>' +
        '<td class="text-right">' + formatMoney(r.amountOpen) + '</td>' +
        '<td class="text-center"><span class="label label-' + statusClass + '">' + statusLabel + '</span></td>' +
        '<td class="text-center">' +
          '<a href="' + r.invoiceHref + '" target="_blank" class="btn btn-xs btn-default" title="Visualizar">' +
            '<i class="fa fa-external-link"></i>' +
          '</a>' +
        '</td>' +
      '</tr>';

      tbody.append(row);
    });
  }

  /* ------------------------------------------------------------------ */
  /* UI helpers                                                           */
  /* ------------------------------------------------------------------ */

  function showLoading(show) {
    if (show) {
      $('#chart-loading').show();
      $('#chart-bar-wrap, #chart-pie-wrap, #chart-gantt-wrap').css('opacity', 0.3);
    } else {
      $('#chart-loading').hide();
      $('#chart-bar-wrap, #chart-pie-wrap, #chart-gantt-wrap').css('opacity', 1);
    }
  }

  function showEmpty() {
    $('#chart-empty').show();
    $('#chart-bar-wrap, #chart-pie-wrap, #chart-gantt-wrap').hide();
  }

  function hideEmpty() {
    $('#chart-empty').hide();
  }

  function showWrap(type) {
    hideEmpty();
    $('#chart-bar-wrap').toggle(type === 'bar' || type === 'stacked');
    $('#chart-pie-wrap').toggle(type === 'pie');
    $('#chart-gantt-wrap').toggle(type === 'gantt');
  }

  /* ------------------------------------------------------------------ */
  /* Event bindings                                                       */
  /* ------------------------------------------------------------------ */

  function bindEvents() {

    // Period buttons
    $(document).on('click', '.btn-period', function () {
      var period = $(this).data('period');
      $('.btn-period').removeClass('active');
      $(this).addClass('active');

      if (period === 'custom') {
        $('#custom-period-inputs').css('display', 'inline-flex');
        return; // wait for user to click Apply
      }

      $('#custom-period-inputs').css('display', 'none');
      state.period   = period;
      state.dateFrom = '';
      state.dateTo   = '';
      fetchData();
    });

    // Apply custom date range
    $(document).on('click', '#btn-apply-custom', function () {
      state.period   = 'custom';
      state.dateFrom = $('#chart_date_from').val();
      state.dateTo   = $('#chart_date_to').val();
      fetchData();
    });

    // Chart type buttons
    $(document).on('click', '.btn-chart-type', function () {
      var type = $(this).data('type');
      $('.btn-chart-type').removeClass('active');
      $(this).addClass('active');
      state.chartType = type;
      renderCurrentChart();
    });
  }

  /* ------------------------------------------------------------------ */
  /* Init                                                                 */
  /* ------------------------------------------------------------------ */

  function init() {
    if (!$('#reports-charts-module').length) return; // not on this page
    bindEvents();
    fetchData();
  }

  // Wait for DOM + Chart.js
  $(document).ready(function () {
    // Chart.js might load async from CDN
    if (typeof Chart !== 'undefined') {
      init();
    } else {
      var checkInterval = setInterval(function () {
        if (typeof Chart !== 'undefined') {
          clearInterval(checkInterval);
          init();
        }
      }, 100);
    }
  });

})(jQuery);
