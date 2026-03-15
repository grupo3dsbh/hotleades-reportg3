/**
 * Reports Charts Module — reports_charts.js
 *
 * Dependencies: Chart.js 4.x (loaded via init file hook), jQuery, Bootstrap 3
 * The global window.RCConfig object is set by the PHP view.
 */
(function ($) {
    'use strict';

    /* ------------------------------------------------------------------
     * Constants
     * ------------------------------------------------------------------ */

    var STATUS_UNPAID  = 1;
    var STATUS_PAID    = 2;
    var STATUS_PARTIAL = 3;

    var COLORS = {
        paid:          'rgba( 40, 167,  69, 0.82)',
        unpaid:        'rgba(220,  53,  69, 0.82)',
        partial:       'rgba(255, 193,   7, 0.85)',
        total:         'rgba( 23, 162, 184, 0.82)',
        paidBorder:    'rgba( 40, 167,  69, 1)',
        unpaidBorder:  'rgba(220,  53,  69, 1)',
        partialBorder: 'rgba(255, 193,   7, 1)',
        totalBorder:   'rgba( 23, 162, 184, 1)',
    };

    var CFG = window.RCConfig || {};

    /* ------------------------------------------------------------------
     * Helpers
     * ------------------------------------------------------------------ */

    function parseMoney(str) {
        if (!str) return 0;
        return parseFloat(String(str).replace(/[^0-9.\-]/g, '')) || 0;
    }

    function formatMoney(val) {
        var n = parseFloat(val) || 0;
        return '$' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function extractHref($el) {
        return $el.attr('href') || '';
    }

    function extractText($el) {
        return $.trim($el.text());
    }

    function detectStatus(html) {
        if (/invoice-status-2/.test(html)) return STATUS_PAID;
        if (/invoice-status-3/.test(html)) return STATUS_PARTIAL;
        return STATUS_UNPAID;
    }

    function parseRow(row) {
        var $inv    = $($.trim(row[0]));
        var $client = $($.trim(row[1]));
        var status  = detectStatus(row[13] || '');
        var total   = parseMoney(row[6]);
        var open    = parseMoney(row[12]);
        var paid    = status === STATUS_PAID    ? total
                    : status === STATUS_PARTIAL ? (total - open)
                    : 0;

        return {
            invHref:    extractHref($inv),
            invNum:     extractText($inv),
            clientHref: extractHref($client),
            clientName: extractText($client),
            dateFrom:   row[3] || '',
            dateTo:     row[4] || '',
            total:      total,
            open:       open,
            paid:       paid,
            status:     status,
            statusHtml: row[13] || '',
        };
    }

    function groupByMonth(rows) {
        var groups = {};
        rows.forEach(function (r) {
            var key = r.dateFrom ? r.dateFrom.substring(0, 7) : '—';
            if (!groups[key]) {
                groups[key] = { label: key, invoices: [], total: 0, paid: 0, unpaid: 0, partial: 0 };
            }
            var g = groups[key];
            g.invoices.push(r);
            g.total += r.total;
            if (r.status === STATUS_PAID)         { g.paid    += r.total; }
            else if (r.status === STATUS_PARTIAL) { g.partial += r.total; g.unpaid += r.open; }
            else                                  { g.unpaid  += r.total; }
        });
        return groups;
    }

    /* ------------------------------------------------------------------
     * State
     * ------------------------------------------------------------------ */

    var state = {
        period:   'this_month',
        dateFrom: '',
        dateTo:   '',
        type:     'bar',
        rows:     [],
        groups:   {},
        barChart: null,
        pieChart: null,
    };

    /* ------------------------------------------------------------------
     * Data fetching
     * ------------------------------------------------------------------ */

    function buildPostData() {
        var d = {
            draw:            1,
            start:           0,
            length:          9999,
            'search[value]': '',
            'search[regex]': false,
            report_months:   state.period === 'custom' ? '' : state.period,
            report_from:     state.dateFrom,
            report_to:       state.dateTo,
        };
        for (var i = 0; i <= 13; i++) {
            d['columns[' + i + '][data]']            = i;
            d['columns[' + i + '][name]']            = '';
            d['columns[' + i + '][searchable]']      = true;
            d['columns[' + i + '][orderable]']       = true;
            d['columns[' + i + '][search][value]']   = '';
            d['columns[' + i + '][search][regex]']   = false;
        }
        d['order[0][column]'] = 3;
        d['order[0][dir]']    = 'asc';
        return d;
    }

    function fetchData() {
        setLoading(true);
        hideEmpty();

        $.ajax({
            url:      CFG.ajaxUrl,
            type:     'POST',
            dataType: 'json',
            data:     buildPostData(),
            success:  function (res) {
                setLoading(false);
                if (!res || !res.aaData || !res.aaData.length) {
                    showEmpty();
                    resetCards();
                    return;
                }
                state.rows   = res.aaData.map(parseRow);
                state.groups = groupByMonth(state.rows);
                updateCards();
                renderChart();
            },
            error: function () {
                setLoading(false);
                showEmpty();
            }
        });
    }

    /* ------------------------------------------------------------------
     * Summary cards
     * ------------------------------------------------------------------ */

    function resetCards() {
        $('#rc-sum-total, #rc-sum-paid, #rc-sum-pending, #rc-sum-partial').text('—');
        $('#rc-sum-total-count, #rc-sum-paid-count, #rc-sum-pending-count, #rc-sum-partial-count').text('');
    }

    function updateCards() {
        var total = 0, paid = 0, unpaid = 0, partial = 0;
        var nTotal = state.rows.length, nPaid = 0, nUnpaid = 0, nPartial = 0;

        state.rows.forEach(function (r) {
            total += r.total;
            if (r.status === STATUS_PAID)         { paid    += r.total; nPaid++;    }
            else if (r.status === STATUS_PARTIAL) { partial += r.total; nPartial++; unpaid += r.open; }
            else                                  { unpaid  += r.total; nUnpaid++;  }
        });

        var inv = CFG.labels.invoices || 'invoices';
        $('#rc-sum-total').text(formatMoney(total));
        $('#rc-sum-total-count').text(nTotal + ' ' + inv);
        $('#rc-sum-paid').text(formatMoney(paid));
        $('#rc-sum-paid-count').text(nPaid + ' ' + inv);
        $('#rc-sum-pending').text(formatMoney(unpaid));
        $('#rc-sum-pending-count').text(nUnpaid + ' ' + inv);
        $('#rc-sum-partial').text(formatMoney(partial));
        $('#rc-sum-partial-count').text(nPartial + ' ' + inv);
    }

    /* ------------------------------------------------------------------
     * Chart rendering dispatcher
     * ------------------------------------------------------------------ */

    function renderChart() {
        var keys = Object.keys(state.groups).sort();
        if (!keys.length) { showEmpty(); return; }

        switch (state.type) {
            case 'bar':     renderBar(keys, false); break;
            case 'stacked': renderBar(keys, true);  break;
            case 'pie':     renderPie();             break;
            case 'gantt':   renderGantt();           break;
        }
    }

    /* --- Bar / Stacked -------------------------------------------- */

    function renderBar(keys, stacked) {
        showWrap('bar');

        var lPaid    = CFG.labels.paid          || 'Paid';
        var lUnpaid  = CFG.labels.not_paid      || 'Not Paid';
        var lPartial = CFG.labels.partially_paid || 'Partial';
        var lTotal   = CFG.labels.total          || 'Total';

        var dataPaid    = keys.map(function (k) { return +state.groups[k].paid.toFixed(2); });
        var dataUnpaid  = keys.map(function (k) { return +state.groups[k].unpaid.toFixed(2); });
        var dataPartial = keys.map(function (k) { return +state.groups[k].partial.toFixed(2); });
        var dataTotal   = keys.map(function (k) { return +state.groups[k].total.toFixed(2); });

        var datasets = [
            { label: lPaid,    data: dataPaid,    backgroundColor: COLORS.paid,    borderColor: COLORS.paidBorder,    borderWidth: 1, stack: 's' },
            { label: lUnpaid,  data: dataUnpaid,  backgroundColor: COLORS.unpaid,  borderColor: COLORS.unpaidBorder,  borderWidth: 1, stack: 's' },
            { label: lPartial, data: dataPartial, backgroundColor: COLORS.partial, borderColor: COLORS.partialBorder, borderWidth: 1, stack: 's' },
        ];

        if (!stacked) {
            datasets.forEach(function (d) { delete d.stack; });
            datasets.unshift({ label: lTotal, data: dataTotal, backgroundColor: COLORS.total, borderColor: COLORS.totalBorder, borderWidth: 1 });
        }

        if (state.barChart) { state.barChart.destroy(); }

        var ctx = document.getElementById('rc-chart-bar').getContext('2d');
        state.barChart = new Chart(ctx, {
            type: 'bar',
            data: { labels: keys, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top' },
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
                    y: { stacked: stacked, ticks: { callback: function (v) { return formatMoney(v); } } }
                },
                onClick: function (evt, els) {
                    if (!els || !els.length) return;
                    var key = keys[els[0].index];
                    openModal(key, state.groups[key].invoices, key);
                }
            }
        });
    }

    /* --- Pie ------------------------------------------------------ */

    function renderPie() {
        showWrap('pie');

        var tPaid = 0, tUnpaid = 0, tPartial = 0;
        Object.keys(state.groups).forEach(function (k) {
            tPaid    += state.groups[k].paid;
            tUnpaid  += state.groups[k].unpaid;
            tPartial += state.groups[k].partial;
        });

        if (state.pieChart) { state.pieChart.destroy(); }

        var ctx = document.getElementById('rc-chart-pie').getContext('2d');
        state.pieChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: [CFG.labels.paid || 'Paid', CFG.labels.not_paid || 'Not Paid', CFG.labels.partially_paid || 'Partial'],
                datasets: [{
                    data:            [+tPaid.toFixed(2), +tUnpaid.toFixed(2), +tPartial.toFixed(2)],
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
                                var sum = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                var pct = sum > 0 ? ((ctx.parsed / sum) * 100).toFixed(1) : 0;
                                return ' ' + ctx.label + ': ' + formatMoney(ctx.parsed) + ' (' + pct + '%)';
                            }
                        }
                    }
                },
                onClick: function (evt, els) {
                    if (!els || !els.length) return;
                    var statusMap = [STATUS_PAID, STATUS_UNPAID, STATUS_PARTIAL];
                    var filter    = statusMap[els[0].index];
                    var filtered  = state.rows.filter(function (r) { return r.status === filter; });
                    var labels    = [CFG.labels.paid, CFG.labels.not_paid, CFG.labels.partially_paid];
                    openModal(null, filtered, labels[els[0].index]);
                }
            }
        });
    }

    /* --- Gantt ---------------------------------------------------- */

    function renderGantt() {
        showWrap('gantt');

        var rows = state.rows.slice().sort(function (a, b) {
            return a.dateFrom < b.dateFrom ? -1 : a.dateFrom > b.dateFrom ? 1 : 0;
        });

        if (!rows.length) { showEmpty(); return; }

        var minD  = new Date(rows[0].dateFrom);
        var maxD  = new Date(rows[rows.length - 1].dateTo || rows[rows.length - 1].dateFrom);
        var span  = Math.max((maxD - minD) / 864e5, 1);

        var html = '<table class="table table-condensed rc-gantt-table">'
            + '<thead><tr>'
            + '<th style="width:130px">' + (CFG.labels.invoices || 'Invoice') + '</th>'
            + '<th style="width:160px">Cliente</th>'
            + '<th style="width:100px">Total</th>'
            + '<th>Timeline</th>'
            + '</tr></thead><tbody>';

        rows.forEach(function (r) {
            var s     = new Date(r.dateFrom);
            var e     = new Date(r.dateTo || r.dateFrom);
            var left  = Math.max(0, ((s - minD) / 864e5 / span) * 100);
            var width = Math.max(2, ((e - s) / 864e5 / span) * 100);
            var cls   = r.status === STATUS_PAID    ? 'rc-gantt-paid'
                      : r.status === STATUS_PARTIAL ? 'rc-gantt-partial'
                      : 'rc-gantt-unpaid';

            html += '<tr>'
                + '<td><a href="' + r.invHref + '" target="_blank">' + r.invNum + '</a></td>'
                + '<td>' + r.clientName + '</td>'
                + '<td>' + formatMoney(r.total) + '</td>'
                + '<td style="padding:6px 8px;">'
                +   '<div class="rc-gantt-track">'
                +     '<div class="rc-gantt-bar ' + cls + '" style="left:' + left.toFixed(2) + '%;width:' + width.toFixed(2) + '%;"'
                +       ' title="' + r.invNum + ' | ' + r.dateFrom + ' → ' + r.dateTo + ' | ' + formatMoney(r.total) + '">'
                +     '</div>'
                +   '</div>'
                + '</td>'
                + '</tr>';
        });

        html += '</tbody></table>';
        document.getElementById('rc-gantt-container').innerHTML = html;
    }

    /* ------------------------------------------------------------------
     * Modal
     * ------------------------------------------------------------------ */

    function openModal(monthKey, rows, title) {
        $('#rc-modal-title').text((CFG.labels.invoices || 'Invoices') + (title ? ' — ' + title : ''));
        fillModalSums(rows);
        fillModalTable(rows);
        $('#rc-modal-invoices').modal('show');
    }

    function fillModalSums(rows) {
        var total = 0, paid = 0, unpaid = 0, partial = 0;
        rows.forEach(function (r) {
            total += r.total;
            if (r.status === STATUS_PAID)         { paid    += r.total; }
            else if (r.status === STATUS_PARTIAL) { partial += r.total; unpaid += r.open; }
            else                                  { unpaid  += r.total; }
        });
        $('#rc-ms-total').text(formatMoney(total));
        $('#rc-ms-paid').text(formatMoney(paid));
        $('#rc-ms-unpaid').text(formatMoney(unpaid));
        $('#rc-ms-partial').text(formatMoney(partial));
    }

    function fillModalTable(rows) {
        var $tbody = $('#rc-modal-tbody');
        $tbody.empty();

        if (!rows || !rows.length) {
            $tbody.html('<tr><td colspan="8" class="text-center text-muted">'
                + (CFG.labels.no_data || 'No data') + '</td></tr>');
            return;
        }

        rows.forEach(function (r) {
            var statusText  = r.statusHtml ? $(r.statusHtml).text().trim() : '';
            var statusClass = r.status === STATUS_PAID    ? 'success'
                            : r.status === STATUS_PARTIAL ? 'warning'
                            : 'danger';

            $tbody.append(
                '<tr>'
                + '<td><strong>' + r.invNum + '</strong></td>'
                + '<td><a href="' + r.clientHref + '" target="_blank">' + r.clientName + '</a></td>'
                + '<td>' + r.dateFrom + '</td>'
                + '<td>' + r.dateTo + '</td>'
                + '<td class="text-right">' + formatMoney(r.total) + '</td>'
                + '<td class="text-right">' + formatMoney(r.open) + '</td>'
                + '<td class="text-center"><span class="label label-' + statusClass + '">' + statusText + '</span></td>'
                + '<td class="text-center">'
                +   '<a href="' + r.invHref + '" target="_blank" class="btn btn-xs btn-default" title="' + (CFG.labels.view || 'View') + '">'
                +     '<i class="fa fa-external-link"></i>'
                +   '</a>'
                + '</td>'
                + '</tr>'
            );
        });
    }

    /* ------------------------------------------------------------------
     * UI helpers
     * ------------------------------------------------------------------ */

    function setLoading(on) {
        $('#rc-loading').toggle(on);
        $('#rc-wrap-bar, #rc-wrap-pie, #rc-wrap-gantt').css('opacity', on ? 0.3 : 1);
    }

    function showEmpty() {
        $('#rc-empty').show();
        $('#rc-wrap-bar, #rc-wrap-pie, #rc-wrap-gantt').hide();
    }

    function hideEmpty() { $('#rc-empty').hide(); }

    function showWrap(type) {
        hideEmpty();
        $('#rc-wrap-bar').toggle(type === 'bar' || type === 'stacked');
        $('#rc-wrap-pie').toggle(type === 'pie');
        $('#rc-wrap-gantt').toggle(type === 'gantt');
    }

    /* ------------------------------------------------------------------
     * Event bindings
     * ------------------------------------------------------------------ */

    function bindEvents() {
        // Period buttons
        $(document).on('click', '.rc-btn-period', function () {
            var period = $(this).data('period');
            $('.rc-btn-period').removeClass('active');
            $(this).addClass('active');

            if (period === 'custom') {
                $('#rc-custom-range').show();
                return;
            }
            $('#rc-custom-range').hide();
            state.period   = period;
            state.dateFrom = '';
            state.dateTo   = '';
            fetchData();
        });

        // Apply custom range
        $(document).on('click', '#rc-btn-apply', function () {
            state.period   = 'custom';
            state.dateFrom = $('#rc_date_from').val();
            state.dateTo   = $('#rc_date_to').val();
            fetchData();
        });

        // Chart type buttons
        $(document).on('click', '.rc-btn-type', function () {
            $('.rc-btn-type').removeClass('active');
            $(this).addClass('active');
            state.type = $(this).data('type');
            if (state.rows.length) renderChart();
        });
    }

    /* ------------------------------------------------------------------
     * Init
     * ------------------------------------------------------------------ */

    function init() {
        if (!$('#rc-wrap-bar').length) return;
        bindEvents();
        fetchData();
    }

    $(document).ready(function () {
        if (typeof Chart !== 'undefined') {
            init();
        } else {
            // Chart.js may still be loading from CDN
            var t = setInterval(function () {
                if (typeof Chart !== 'undefined') { clearInterval(t); init(); }
            }, 100);
        }
    });

}(jQuery));
