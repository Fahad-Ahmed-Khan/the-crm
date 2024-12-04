<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper" class="">
   <div class="content">
      <div class="_filters _hidden_inputs hidden">
         <?php
         $data = [];
         if(isset($direction) && isset($status)){
            if($direction == 'outbound' && $status == 'sending'){
               $data['table_page'] = _l('lm_mailbox_sent_li');
            }elseif (($direction == 'outbound' && $status == 'trash') || ($direction == 'inbound' && $status == 'trash')) {
               $data['table_page'] = _l('lm_mailbox_trash_li');
            }elseif (($direction == 'outbound' && $status == 'draft') || ($direction == 'inbound' && $status == 'draft')) {
               $data['table_page'] = _l('lm_mailbox_draft_li');
            }elseif ($direction == 'inbound' && $status == 'get') {
               $data['table_page'] = _l('lm_mailbox_inbox_li');
            }
            echo form_hidden('direction', $direction);
            echo form_hidden('status', $status);
         }else{
            echo form_hidden('direction','inbound');
            echo form_hidden('status','get');
         }
         ?>
      </div>
      <div class="row">
         <div class="col-md-3">
            <?php $this->load->view('admin/mailbox/tabs',$data); ?>
         </div>
         <div class="col-md-9">
            <div class="panel_s">
               <div class="panel-body">
                  <div class="clearfix"></div>
                  <div>
                     <div class="tab-content hide" id="tab-content-form">
                     </div>
                     <div class="tab-content" id="tab-content-table">
                        <?php $this->load->view('admin/mailbox/inbox',$data); ?>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>
<div class="modal fade" id="mailbox-mail-modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
  <div class="modal-dialog">
    <div class="modal-content data">

    </div>
 </div> 
</div>
<?php init_tail(); ?>
<script>
   var mailboxTable = '';
   $(function(){
    var serverParams = {};
    $.each($('._hidden_inputs._filters input'),function(){
     serverParams[$(this).attr('name')] = '[name="'+$(this).attr('name')+'"]';
  });
    mailboxTable = initDataTable('.table-lm-mailbox', admin_url+'lead_manager/mailbox_table', [0], [0], serverParams, [0, 'desc']);
    mailboxTable.column(5).visible(false);
    if($('input[name="direction"]').val() != 'outbound' && $('input[name="status"]').val() != 'sending'){
      mailboxTable.column(2).visible(false); 
    }else{
      mailboxTable.column(1).visible(false); 
    }
 });
</script>
</body>
</html>
