<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<style>
/* ---- Summary Cards ---- */
.rc-card { display:flex; align-items:center; gap:14px; padding:14px 16px; border-radius:6px;
  background:#fff; box-shadow:0 1px 4px rgba(0,0,0,.12); border-left:5px solid #aaa;
  transition:box-shadow .2s; margin-bottom:12px; }
.rc-card:hover { box-shadow:0 3px 10px rgba(0,0,0,.18); }
.rc-card-icon { font-size:28px; opacity:.65; min-width:34px; text-align:center; }
.rc-card-label { font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#888; margin-bottom:3px; }
.rc-card-value { font-size:22px; font-weight:700; color:#333; line-height:1.2; }
.rc-card-count { font-size:11px; color:#bbb; margin-top:2px; }
.rc-card-total   { border-left-color:#17a2b8; } .rc-card-total   .rc-card-icon { color:#17a2b8; }
.rc-card-paid    { border-left-color:#28a745; } .rc-card-paid    .rc-card-icon { color:#28a745; }
.rc-card-pending { border-left-color:#dc3545; } .rc-card-pending .rc-card-icon { color:#dc3545; }
.rc-card-partial { border-left-color:#ffc107; } .rc-card-partial .rc-card-icon { color:#ffc107; }
/* ---- Button states ---- */
.rc-btn-period.active, .rc-btn-type.active { background-color:#337ab7; color:#fff; border-color:#2e6da4; }
/* ---- Gantt ---- */
.rc-gantt-track { position:relative; height:22px; background:#f0f0f0; border-radius:3px; overflow:hidden; }
.rc-gantt-bar { position:absolute; height:100%; border-radius:3px; min-width:6px; cursor:pointer; }
.rc-gantt-bar:hover { filter:brightness(1.15); }
.rc-gantt-paid    { background:rgba(40,167,69,.80); }
.rc-gantt-unpaid  { background:rgba(220,53,69,.80); }
.rc-gantt-partial { background:rgba(255,193,7,.85); }
/* ---- Modal ---- */
#rc-modal-table thead th { background:#f5f5f5; font-size:12px; text-transform:uppercase;
  letter-spacing:.4px; color:#666; border-bottom:2px solid #ddd; }
#rc-modal-table tbody tr:hover { background:#f0f7ff; }
</style>

<div id="wrapper">
  <div class="content">

    <div class="row">
      <div class="col-md-12">
        <div class="page-title-box">
          <h4 class="page-title"><i class="fa fa-bar-chart mright5"></i> Relatórios — Gráficos</h4>
        </div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="row mtop10">
      <div class="col-md-7">
        <div class="btn-group" role="group">
          <button type="button" class="btn btn-default rc-btn-period active" data-period="this_month">Este Mês</button>
          <button type="button" class="btn btn-default rc-btn-period" data-period="last_month">Mês Passado</button>
          <button type="button" class="btn btn-default rc-btn-period" data-period="this_year">Este Ano</button>
          <button type="button" class="btn btn-default rc-btn-period" data-period="last_year">Ano Passado</button>
          <button type="button" class="btn btn-default rc-btn-period" data-period="custom">Personalizado</button>
        </div>
        <div id="rc-custom-range" style="display:none; margin-top:6px;">
          <div class="input-group" style="max-width:360px;">
            <input type="text" id="rc_date_from" class="form-control datepicker" placeholder="De" autocomplete="off">
            <span class="input-group-addon">—</span>
            <input type="text" id="rc_date_to" class="form-control datepicker" placeholder="Até" autocomplete="off">
            <span class="input-group-btn">
              <button class="btn btn-primary" type="button" id="rc-btn-apply">Aplicar</button>
            </span>
          </div>
        </div>
      </div>
      <div class="col-md-5 text-right" style="padding-top:2px;">
        <div class="btn-group" role="group">
          <button type="button" class="btn btn-default rc-btn-type active" data-type="bar"><i class="fa fa-bar-chart"></i> Barras</button>
          <button type="button" class="btn btn-default rc-btn-type" data-type="stacked"><i class="fa fa-tasks"></i> Empilhado</button>
          <button type="button" class="btn btn-default rc-btn-type" data-type="pie"><i class="fa fa-pie-chart"></i> Pizza</button>
          <button type="button" class="btn btn-default rc-btn-type" data-type="gantt"><i class="fa fa-align-left"></i> Gantt</button>
        </div>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mtop15">
      <div class="col-md-3 col-sm-6">
        <div class="rc-card rc-card-total">
          <div class="rc-card-icon"><i class="fa fa-file-text-o"></i></div>
          <div><div class="rc-card-label">Total Faturado</div><div class="rc-card-value" id="rc-sum-total">—</div><div class="rc-card-count" id="rc-sum-total-count"></div></div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="rc-card rc-card-paid">
          <div class="rc-card-icon"><i class="fa fa-check-circle"></i></div>
          <div><div class="rc-card-label">Pago</div><div class="rc-card-value" id="rc-sum-paid">—</div><div class="rc-card-count" id="rc-sum-paid-count"></div></div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="rc-card rc-card-pending">
          <div class="rc-card-icon"><i class="fa fa-clock-o"></i></div>
          <div><div class="rc-card-label">Não Pago</div><div class="rc-card-value" id="rc-sum-pending">—</div><div class="rc-card-count" id="rc-sum-pending-count"></div></div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="rc-card rc-card-partial">
          <div class="rc-card-icon"><i class="fa fa-adjust"></i></div>
          <div><div class="rc-card-label">Parcialmente Pago</div><div class="rc-card-value" id="rc-sum-partial">—</div><div class="rc-card-count" id="rc-sum-partial-count"></div></div>
        </div>
      </div>
    </div>

    <!-- Chart Panel -->
    <div class="row mtop10">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body" style="position:relative; min-height:300px;">
            <div id="rc-loading" style="display:none; position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); z-index:10; color:#337ab7;">
              <i class="fa fa-spinner fa-spin fa-2x"></i>
            </div>
            <div id="rc-empty" style="display:none; text-align:center; padding:60px 0;">
              <i class="fa fa-bar-chart fa-3x text-muted"></i>
              <p class="text-muted mtop10">Nenhum dado encontrado</p>
            </div>
            <div id="rc-wrap-bar"><canvas id="rc-chart-bar" height="110"></canvas></div>
            <div id="rc-wrap-pie" style="display:none;">
              <div class="row"><div class="col-md-6 col-md-offset-3"><canvas id="rc-chart-pie"></canvas></div></div>
            </div>
            <div id="rc-wrap-gantt" style="display:none;">
              <div id="rc-gantt-container" class="table-responsive"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="rc-modal-invoices" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title"><i class="fa fa-list-ul mright5"></i><span id="rc-modal-title">Faturas</span></h4>
      </div>
      <div class="modal-body no-padding">
        <div style="display:flex; flex-wrap:wrap; gap:20px; padding:12px 18px; background:#f8f9fa; border-bottom:1px solid #e9ecef;">
          <div><span style="font-size:11px;text-transform:uppercase;color:#777;font-weight:600;">Total:</span> <strong id="rc-ms-total">—</strong></div>
          <div><span style="font-size:11px;text-transform:uppercase;color:#28a745;font-weight:600;">Pago:</span> <strong id="rc-ms-paid" class="text-success">—</strong></div>
          <div><span style="font-size:11px;text-transform:uppercase;color:#dc3545;font-weight:600;">Não Pago:</span> <strong id="rc-ms-unpaid" class="text-danger">—</strong></div>
          <div><span style="font-size:11px;text-transform:uppercase;color:#e0a800;font-weight:600;">Parcial:</span> <strong id="rc-ms-partial" style="color:#e0a800;">—</strong></div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover table-condensed" id="rc-modal-table">
            <thead>
              <tr>
                <th>Fatura</th><th>Cliente</th><th>Data</th><th>Vencimento</th>
                <th class="text-right">Total</th><th class="text-right">Em Aberto</th>
                <th class="text-center">Status</th><th class="text-center">Ação</th>
              </tr>
            </thead>
            <tbody id="rc-modal-tbody">
              <tr><td colspan="8" class="text-center"><i class="fa fa-spinner fa-spin"></i></td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<script>
window.RCConfig = {
  ajaxUrl: '<?php echo admin_url('reports/invoices_report'); ?>'
};
</script>

<script>
function reportsChartsInit() {
  if (typeof jQuery === 'undefined' || typeof Chart === 'undefined') {
    setTimeout(reportsChartsInit, 50);
    return;
  }
  (function ($) {
  'use strict';

  var STATUS_UNPAID  = 1, STATUS_PAID = 2, STATUS_PARTIAL = 3;
  var COLORS = {
    paid:'rgba(40,167,69,.82)', unpaid:'rgba(220,53,69,.82)', partial:'rgba(255,193,7,.85)', total:'rgba(23,162,184,.82)',
    paidB:'rgba(40,167,69,1)', unpaidB:'rgba(220,53,69,1)', partialB:'rgba(255,193,7,1)', totalB:'rgba(23,162,184,1)'
  };

  function parseMoney(s){ return parseFloat(String(s||'').replace(/[^0-9.\-]/g,''))||0; }
  function fmtMoney(v){ return '$'+parseFloat(v||0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,','); }
  function detectStatus(h){ return /invoice-status-2/.test(h)?STATUS_PAID:/invoice-status-3/.test(h)?STATUS_PARTIAL:STATUS_UNPAID; }

  function parseRow(row){
    var $inv=$($.trim(row[0])), $cl=$($.trim(row[1]));
    var status=detectStatus(row[13]||''), total=parseMoney(row[6]), open=parseMoney(row[12]);
    return {
      invHref:$inv.attr('href')||'', invNum:$.trim($inv.text()),
      clientHref:$cl.attr('href')||'', clientName:$.trim($cl.text()),
      dateFrom:row[3]||'', dateTo:row[4]||'', total:total, open:open,
      paid:status===STATUS_PAID?total:status===STATUS_PARTIAL?total-open:0,
      status:status, statusHtml:row[13]||''
    };
  }

  function groupByMonth(rows){
    var g={};
    rows.forEach(function(r){
      var k=r.dateFrom?r.dateFrom.substring(0,7):'—';
      if(!g[k]) g[k]={label:k,invoices:[],total:0,paid:0,unpaid:0,partial:0};
      g[k].invoices.push(r); g[k].total+=r.total;
      if(r.status===STATUS_PAID) g[k].paid+=r.total;
      else if(r.status===STATUS_PARTIAL){g[k].partial+=r.total;g[k].unpaid+=r.open;}
      else g[k].unpaid+=r.total;
    });
    return g;
  }

  var S={period:'this_month',dateFrom:'',dateTo:'',type:'bar',rows:[],groups:{},barChart:null,pieChart:null};

  function buildPost(){
    var d={draw:1,start:0,length:9999,'search[value]':'','search[regex]':false,
      report_months:S.period==='custom'?'':S.period,report_from:S.dateFrom,report_to:S.dateTo};
    for(var i=0;i<=13;i++){
      d['columns['+i+'][data]']=i;d['columns['+i+'][name]']='';
      d['columns['+i+'][searchable]']=true;d['columns['+i+'][orderable]']=true;
      d['columns['+i+'][search][value]']='';d['columns['+i+'][search][regex]']=false;
    }
    d['order[0][column]']=3;d['order[0][dir]']='asc';
    return d;
  }

  function fetchData(){
    setLoading(true); hideEmpty();
    $.ajax({
      url:window.RCConfig.ajaxUrl, type:'POST', dataType:'json', data:buildPost(),
      success:function(res){
        setLoading(false);
        if(!res||!res.aaData||!res.aaData.length){showEmpty();resetCards();return;}
        S.rows=res.aaData.map(parseRow); S.groups=groupByMonth(S.rows);
        updateCards(); renderChart();
      },
      error:function(){setLoading(false);showEmpty();}
    });
  }

  function resetCards(){ $('#rc-sum-total,#rc-sum-paid,#rc-sum-pending,#rc-sum-partial').text('—'); }

  function updateCards(){
    var total=0,paid=0,unpaid=0,partial=0,nT=S.rows.length,nP=0,nU=0,nX=0;
    S.rows.forEach(function(r){
      total+=r.total;
      if(r.status===STATUS_PAID){paid+=r.total;nP++;}
      else if(r.status===STATUS_PARTIAL){partial+=r.total;nX++;unpaid+=r.open;}
      else{unpaid+=r.total;nU++;}
    });
    $('#rc-sum-total').text(fmtMoney(total)); $('#rc-sum-total-count').text(nT+' faturas');
    $('#rc-sum-paid').text(fmtMoney(paid));   $('#rc-sum-paid-count').text(nP+' faturas');
    $('#rc-sum-pending').text(fmtMoney(unpaid));$('#rc-sum-pending-count').text(nU+' faturas');
    $('#rc-sum-partial').text(fmtMoney(partial));$('#rc-sum-partial-count').text(nX+' faturas');
  }

  function renderChart(){
    var keys=Object.keys(S.groups).sort();
    if(!keys.length){showEmpty();return;}
    if(S.type==='bar'||S.type==='stacked') renderBar(keys,S.type==='stacked');
    else if(S.type==='pie') renderPie();
    else renderGantt();
  }

  function renderBar(keys,stacked){
    showWrap('bar');
    var dPaid=keys.map(function(k){return+S.groups[k].paid.toFixed(2);});
    var dUnpaid=keys.map(function(k){return+S.groups[k].unpaid.toFixed(2);});
    var dPartial=keys.map(function(k){return+S.groups[k].partial.toFixed(2);});
    var dTotal=keys.map(function(k){return+S.groups[k].total.toFixed(2);});
    var ds=[
      {label:'Pago',data:dPaid,backgroundColor:COLORS.paid,borderColor:COLORS.paidB,borderWidth:1,stack:'s'},
      {label:'Não Pago',data:dUnpaid,backgroundColor:COLORS.unpaid,borderColor:COLORS.unpaidB,borderWidth:1,stack:'s'},
      {label:'Parcial',data:dPartial,backgroundColor:COLORS.partial,borderColor:COLORS.partialB,borderWidth:1,stack:'s'}
    ];
    if(!stacked){ds.forEach(function(d){delete d.stack;});ds.unshift({label:'Total',data:dTotal,backgroundColor:COLORS.total,borderColor:COLORS.totalB,borderWidth:1});}
    if(S.barChart) S.barChart.destroy();
    S.barChart=new Chart(document.getElementById('rc-chart-bar').getContext('2d'),{
      type:'bar',data:{labels:keys,datasets:ds},
      options:{responsive:true,maintainAspectRatio:true,
        interaction:{mode:'index',intersect:false},
        plugins:{legend:{position:'top'},tooltip:{callbacks:{label:function(c){return' '+c.dataset.label+': '+fmtMoney(c.parsed.y);}}}},
        scales:{x:{stacked:stacked},y:{stacked:stacked,ticks:{callback:function(v){return fmtMoney(v);}}}},
        onClick:function(evt,els){if(!els||!els.length)return;var k=keys[els[0].index];openModal(k,S.groups[k].invoices);}
      }
    });
  }

  function renderPie(){
    showWrap('pie');
    var tP=0,tU=0,tX=0;
    Object.keys(S.groups).forEach(function(k){tP+=S.groups[k].paid;tU+=S.groups[k].unpaid;tX+=S.groups[k].partial;});
    if(S.pieChart) S.pieChart.destroy();
    S.pieChart=new Chart(document.getElementById('rc-chart-pie').getContext('2d'),{
      type:'pie',
      data:{labels:['Pago','Não Pago','Parcialmente Pago'],
        datasets:[{data:[+tP.toFixed(2),+tU.toFixed(2),+tX.toFixed(2)],
          backgroundColor:[COLORS.paid,COLORS.unpaid,COLORS.partial],
          borderColor:[COLORS.paidB,COLORS.unpaidB,COLORS.partialB],borderWidth:2}]},
      options:{responsive:true,plugins:{legend:{position:'bottom'},
        tooltip:{callbacks:{label:function(c){var s=c.dataset.data.reduce(function(a,b){return a+b;},0);
          var p=s>0?((c.parsed/s)*100).toFixed(1):0;return' '+c.label+': '+fmtMoney(c.parsed)+' ('+p+'%)';}}}},
        onClick:function(evt,els){if(!els||!els.length)return;
          var sm=[STATUS_PAID,STATUS_UNPAID,STATUS_PARTIAL];
          var lb=['Pago','Não Pago','Parcialmente Pago'];
          var f=sm[els[0].index];
          openModal(lb[els[0].index],S.rows.filter(function(r){return r.status===f;}));}
      }
    });
  }

  function renderGantt(){
    showWrap('gantt');
    var rows=S.rows.slice().sort(function(a,b){return a.dateFrom<b.dateFrom?-1:a.dateFrom>b.dateFrom?1:0;});
    if(!rows.length){showEmpty();return;}
    var minD=new Date(rows[0].dateFrom),maxD=new Date(rows[rows.length-1].dateTo||rows[rows.length-1].dateFrom);
    var span=Math.max((maxD-minD)/864e5,1);
    var html='<table class="table table-condensed" style="table-layout:fixed;width:100%"><thead><tr>'
      +'<th style="width:130px">Fatura</th><th style="width:160px">Cliente</th><th style="width:100px">Total</th><th>Timeline</th>'
      +'</tr></thead><tbody>';
    rows.forEach(function(r){
      var s=new Date(r.dateFrom),e=new Date(r.dateTo||r.dateFrom);
      var left=Math.max(0,((s-minD)/864e5/span)*100),width=Math.max(2,((e-s)/864e5/span)*100);
      var cls=r.status===STATUS_PAID?'rc-gantt-paid':r.status===STATUS_PARTIAL?'rc-gantt-partial':'rc-gantt-unpaid';
      html+='<tr><td style="overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">'
        +'<a href="'+r.invHref+'" target="_blank">'+r.invNum+'</a></td>'
        +'<td style="overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">'+r.clientName+'</td>'
        +'<td>'+fmtMoney(r.total)+'</td>'
        +'<td style="padding:6px 8px;"><div class="rc-gantt-track">'
        +'<div class="rc-gantt-bar '+cls+'" style="left:'+left.toFixed(2)+'%;width:'+width.toFixed(2)+'%;" title="'+r.invNum+' | '+r.dateFrom+' → '+r.dateTo+'"></div>'
        +'</div></td></tr>';
    });
    html+='</tbody></table>';
    document.getElementById('rc-gantt-container').innerHTML=html;
  }

  function openModal(title,rows){
    $('#rc-modal-title').text('Faturas — '+title);
    var total=0,paid=0,unpaid=0,partial=0;
    rows.forEach(function(r){
      total+=r.total;
      if(r.status===STATUS_PAID) paid+=r.total;
      else if(r.status===STATUS_PARTIAL){partial+=r.total;unpaid+=r.open;}
      else unpaid+=r.total;
    });
    $('#rc-ms-total').text(fmtMoney(total));$('#rc-ms-paid').text(fmtMoney(paid));
    $('#rc-ms-unpaid').text(fmtMoney(unpaid));$('#rc-ms-partial').text(fmtMoney(partial));
    var $tbody=$('#rc-modal-tbody').empty();
    if(!rows.length){$tbody.html('<tr><td colspan="8" class="text-center text-muted">Nenhum dado</td></tr>');
    } else {
      rows.forEach(function(r){
        var st=r.statusHtml?$(r.statusHtml).text().trim():'';
        var sc=r.status===STATUS_PAID?'success':r.status===STATUS_PARTIAL?'warning':'danger';
        $tbody.append('<tr>'
          +'<td><strong>'+r.invNum+'</strong></td>'
          +'<td><a href="'+r.clientHref+'" target="_blank">'+r.clientName+'</a></td>'
          +'<td>'+r.dateFrom+'</td><td>'+r.dateTo+'</td>'
          +'<td class="text-right">'+fmtMoney(r.total)+'</td>'
          +'<td class="text-right">'+fmtMoney(r.open)+'</td>'
          +'<td class="text-center"><span class="label label-'+sc+'">'+st+'</span></td>'
          +'<td class="text-center"><a href="'+r.invHref+'" target="_blank" class="btn btn-xs btn-default"><i class="fa fa-external-link"></i> Ver</a></td>'
          +'</tr>');
      });
    }
    $('#rc-modal-invoices').modal('show');
  }

  function setLoading(on){$('#rc-loading').toggle(on);$('#rc-wrap-bar,#rc-wrap-pie,#rc-wrap-gantt').css('opacity',on?.3:1);}
  function showEmpty(){$('#rc-empty').show();$('#rc-wrap-bar,#rc-wrap-pie,#rc-wrap-gantt').hide();}
  function hideEmpty(){$('#rc-empty').hide();}
  function showWrap(t){hideEmpty();$('#rc-wrap-bar').toggle(t==='bar'||t==='stacked');$('#rc-wrap-pie').toggle(t==='pie');$('#rc-wrap-gantt').toggle(t==='gantt');}

  $(document).on('click','.rc-btn-period',function(){
    var p=$(this).data('period');
    $('.rc-btn-period').removeClass('active');$(this).addClass('active');
    if(p==='custom'){$('#rc-custom-range').show();return;}
    $('#rc-custom-range').hide();S.period=p;S.dateFrom='';S.dateTo='';fetchData();
  });
  $(document).on('click','#rc-btn-apply',function(){
    S.period='custom';S.dateFrom=$('#rc_date_from').val();S.dateTo=$('#rc_date_to').val();fetchData();
  });
  $(document).on('click','.rc-btn-type',function(){
    $('.rc-btn-type').removeClass('active');$(this).addClass('active');
    S.type=$(this).data('type');if(S.rows.length)renderChart();
  });

  fetchData();

  }(jQuery));
}
reportsChartsInit();
</script>

<?php init_tail(); ?>
</body>
</html>
