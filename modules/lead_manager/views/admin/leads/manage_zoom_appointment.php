<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
   <div class="content">
      <div class="row">
         <div class="col-md-12">
            <div class="_filters _hidden_inputs hidden">
               <?php
               echo form_hidden('is_client_no');
               echo form_hidden('is_client_yes');
               echo form_hidden('status_end');
               echo form_hidden('status_waiting'); 
               echo form_hidden('period_from');
               echo form_hidden('period_to');
               ?>
            </div>
            <div class="panel_s">
               <div class="panel-body">
                  <div class="_buttons">
                     <div class="row">
                        <div class="col-lg-2 pull-left">
                         <h4 class="no-margin"> &nbsp;&nbsp; <?php echo _l('lead_manager_zoom_meetings') ?></h4>
                      </div>
                      <div class="col-lg-8">
                        <div class="row">
                           <div class="col-md-4"><?php echo render_date_input('date_f','','',['placeholder' => _l('From')]); ?></div>
                           <div class="col-md-4"><?php echo render_date_input('date_t','','',['placeholder' => _l('To')]); ?></div>
                        </div>
                     </div>
                      <div class="col-lg-2"><div class="btn-group pull-right btn-with-tooltip-group _filter_data" data-toggle="tooltip" data-title="Filter by">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                           <i class="fa fa-filter" aria-hidden="true"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-left" style="width:300px;">
                           <li class="active"><a href="#" data-cview="all" onclick="dt_custom_view('','.table-lead-managerd',''); return false;">All</a>
                           </li>
                           <div class="clearfix"></div>
                           <li class="divider"></li>
                           <li class="dropdown-submenu pull-left source">
                              <a href="#" tabindex="-1"><?php echo _l('lm_is_client'); ?></a>
                              <ul class="dropdown-menu dropdown-menu-left">
                                 <li>
                                    <a href="#" data-cview="is_client_no" onclick="dt_custom_view('no','.table-zoom-appointment','is_client_no'); return false;"><?php echo _l('Lead'); ?></a>
                                 </li>
                                 <li>
                                    <a href="#" data-cview="is_client_yes" onclick="dt_custom_view('yes','.table-zoom-appointment','is_client_yes'); return false;"><?php echo _l('Client'); ?></a>
                                 </li>
                              </ul>
                           </li>
                           <div class="clearfix"></div>
                           <li class="divider"></li>
                           <li class="dropdown-submenu pull-left source">
                              <a href="#" tabindex="-1"><?php echo _l('lm_status'); ?></a>
                              <ul class="dropdown-menu dropdown-menu-left">
                                 <li>
                                    <a href="#" data-cview="status_0" onclick="dt_custom_view('0','.table-zoom-appointment','status_0'); return false;"><?php echo _l('End'); ?></a>
                                 </li>
                                 <li>
                                    <a href="#" data-cview="status_1" onclick="dt_custom_view('1','.table-zoom-appointment','status_1'); return false;"><?php echo _l('Waiting'); ?></a>
                                 </li>
                              </ul>
                           </li>
                        </ul>
                     </div>
                  </div>
               </div>
               <div class="clearfix"></div>
            </div>
            <hr class="hr-panel-heading" />
            <div class="tab-content">
               <div class="row" id="lead_manager-table">
                  <div class="clearfix"></div>
                  <div class="col-md-12">
                   <?php
                   $table_data = array(
                     _l('#'),
                     _l('lm_customer_name'),
                     _l('lm_customer_email'),
                     _l('lm_staff_name'),
                     _l('lm_is_client'),
                     _l('lm_zoom_meeting'),
                     _l('lm_status'),
                     _l('lm_remark'),

                  );
                   render_datatable($table_data,'zoom-appointment');
                   ?>
                </div>
             </div>

          </div>
       </div>
    </div>
 </div>
</div> 
</div>
</div>
<div class="modal fade" id="lead-manager-meeting-details" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
 <div class="modal-dialog modal-lg">
  <div class="modal-content data">

  </div>
</div>
</div>
<div class="modal fade" id="lead-manager-meeting-remark" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
 <div class="modal-dialog">
  <div class="modal-content data">

  </div>
</div>
</div>
<div class="modal fade" id="lead-manager-meeting-show_remark" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
 <div class="modal-dialog">
  <div class="modal-content data">

  </div>
</div>
</div>
<script id="hidden-columns-table-lead-manager" type="text/json">
   <?php echo get_staff_meta(get_staff_user_id(), 'hidden-columns-table-lead-manager'); ?>
</script>
<?php init_tail(); ?>
<script>
   var openLeadID = '<?php echo $leadid; ?>';
   var Table = '';
   var date_t_value = (new Date()).toISOString().split('T')[0];
   $(function(){
     var serverParams = {};
     $.each($('._hidden_inputs._filters input'),function(){
        serverParams[$(this).attr('name')] = '[name="'+$(this).attr('name')+'"]';
     });
     initDataTable('.table-zoom-appointment', admin_url+'lead_manager/zoom_appointment_table', [0], [0], serverParams, [0, 'desc']);
     Table = $('.table-zoom-appointment').DataTable().columns([0]).visible(false);
  });

   function update_meeting_status(status_id, lead_id) {
      var table_leads = $('table.table-zoom-appointment');
      var data = {};
      data.status = status_id;
      data.id = lead_id;
      $.post(admin_url + 'lead_manager/zoom_meeting/update_meeting_status', data).done(function (response) {
       table_leads.DataTable().ajax.reload(null, false);
    });
   } 
   $('#date_t').on('change',function(){
        date_t_value = $('#date_t').val()
    });
   $('#date_t').on('change',function(){
        $('input[name="period_from"]').val($('#date_f').val());
        $('input[name="period_to"]').val(date_t_value);
        $('.table-zoom-appointment').DataTable().ajax.reload(); 
    });
</script>
</body>
</html>
