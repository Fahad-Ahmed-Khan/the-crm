<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="col-md-12 no-padding">
   <div class="panel_s">
      <div class="panel-body">
         <div class="horizontal-scrollable-tabs preview-tabs-top">
            <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
            <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
            <div class="horizontal-tabs">
               <ul class="nav nav-tabs nav-tabs-horizontal mbot15" role="tablist">
                  <li role="presentation" class="active">
                     <a href="#tab_expense" aria-controls="tab_expense" role="tab" data-toggle="tab">
                     <?php echo _l('acc_bill_detail'); ?>
                     </a>
                  </li>

                 <?php if($expense->approved == 1 && $expense->voided == 0){ ?>
                      <li role="presentation" class="">
                         <a href="#list_expense" aria-controls="list_expense" role="tab" data-toggle="tab">
                         <?php echo _l('payment').' '; ?>
                         </a>
                      </li>
                <?php } ?>
                  <li role="presentation" class="tab-separator toggle_view">
                     <a href="#" onclick="small_table_full_view(); return false;" data-placement="left" data-toggle="tooltip" data-title="<?php echo _l('toggle_full_view'); ?>">
                     <i class="fa fa-expand"></i></a>
                  </li>
               </ul>
            </div>
         </div>
         <div class="row mtop10">
            <div class="col-md-6" id="expenseHeadings">
               <?php echo bill_status_html($expense->id); ?>
            </div>
            <div class="col-md-6 _buttons text-right">
               <div class="visible-xs">
                  <div class="mtop10"></div>
               </div>
               <div class="pull-right">
                  <?php if(has_permission('accounting_bills','','edit') && $expense->approved == 0){ ?>
                  <a class="btn btn-default btn-with-tooltip" href="<?php echo admin_url('accounting/bill/'.$expense->id); ?>" data-toggle="tooltip" data-placement="bottom" title="<?php echo _l('edit'); ?>"><i class="fa fa-pencil-square"></i></a>
                  <?php } ?>
                  <?php if(has_permission('accounting_bills','','delete') && $expense->approved == 0){ ?>
                  <a class="btn btn-danger btn-with-tooltip _delete" href="<?php echo admin_url('accounting/delete_bill/'.$expense->id); ?>" data-toggle="tooltip" data-placement="bottom" title="<?php echo _l('expense_delete'); ?>"><i class="fa fa-remove"></i></a>
                  <?php } ?>

                  <?php if($expense->approved == 1 && $expense->status != 2 && $expense->voided == 0){ ?>
                  <a class="btn btn-success btn-with-tooltip" href="<?php echo admin_url('accounting/pay_bill?bill='.$expense->id); ?>" data-toggle="tooltip" data-placement="bottom" title="<?php echo _l('pay_bill'); ?>"><i class="fa fa-plus"></i><?php echo ' '._l('pay_bill'); ?></a>
                  <?php } ?>
               </div>
            </div>
         </div>
         <div class="clearfix"></div>
         <hr class="hr-panel-heading hr-10" />
         <div class="tab-content">
            <div role="tabpanel" class="tab-pane ptop10 active" id="tab_expense" data-empty-note="<?php echo empty($expense->note); ?>" data-empty-name="<?php echo empty($expense->expense_name); ?>">
               <div class="row">
                <?php
                if($expense->recurring > 0 || $expense->recurring_from != NULL) {
                  echo '<div class="col-md-12">';

                  $recurring_expense = $expense;
                  $show_recurring_expense_info = true;

                  if($expense->recurring_from != NULL){
                    $recurring_expense = $this->expenses_model->get($expense->recurring_from);
                         // Maybe recurring expense not longer recurring?
                    if($recurring_expense->recurring == 0) {
                      $show_recurring_expense_info = false;
                    } else {
                      $next_recurring_date_compare = $recurring_expense->last_recurring_date;
                    }
                  } else {
                    $next_recurring_date_compare = $recurring_expense->date;
                    if($recurring_expense->last_recurring_date){
                      $next_recurring_date_compare = $recurring_expense->last_recurring_date;
                    }
                  }
                  if($show_recurring_expense_info){
                   $next_date = date('Y-m-d', strtotime('+' . $recurring_expense->repeat_every . ' ' . strtoupper($recurring_expense->recurring_type),strtotime($next_recurring_date_compare)));
                 }
                 ?>
                 <?php if($expense->recurring_from == null && $recurring_expense->cycles > 0 && $recurring_expense->cycles == $recurring_expense->total_cycles) { ?>
                  <div class="alert alert-info mbot15">
                   <?php echo _l('recurring_has_ended', _l('expense_lowercase')); ?>
                 </div>
               <?php } else  if($show_recurring_expense_info){ ?>
                <span class="label label-default padding-5">
                  <?php echo _l('cycles_remaining'); ?>:
                  <b>
                   <?php
                     echo $recurring_expense->cycles == 0 ? _l('cycles_infinity') : $recurring_expense->cycles - $recurring_expense->total_cycles;
                   ?>
                 </b>
               </span>
               <?php if($recurring_expense->cycles == 0 || $recurring_expense->cycles != $recurring_expense->total_cycles){
                echo '<span class="label label-default padding-5 mleft5"><i class="fa fa-question-circle fa-fw" data-toggle="tooltip" data-title="'._l('recurring_recreate_hour_notice',_l('expense')).'"></i> ' . _l('next_expense_date','<b>'._d($next_date).'</b>') .'</span>';
              }
            }
            if($expense->recurring_from != NULL){ ?>
              <?php echo '<p class="text-muted no-mbot'.($show_recurring_expense_info ? ' mtop15': '').'">'._l('expense_recurring_from','<a href="'.admin_url('expenses/list_expenses/'.$expense->recurring_from).'" onclick="init_expense('.$expense->recurring_from.');return false;">'.$recurring_expense->category_name.(!empty($recurring_expense->expense_name) ? ' ('.$recurring_expense->expense_name.')' : '').'</a></p>'); ?>
            <?php } ?>
          </div>
          <div class="clearfix"></div>
          <hr class="hr-panel-heading" />
        <?php } ?>
               <div class="col-md-6">
                  <p>

                    <div id="amountWrapper">
                      <span class="bold font-medium"><?php echo _l('acc_vendor').': '; ?></span>
                      <a href="<?php echo admin_url('accounting/vendor/'.$expense->vendor); ?>"><?php echo acc_get_vendor_name($expense->vendor); ?></a>
                    </div>

                   

                           <p>
                            <span class="bold"><?php echo _l('bill_date'); ?></span> <span class="text-muted"><?php echo _d($expense->date); ?></span><br>
                            <span class="bold"><?php echo _l('acc_due_date').': '; ?></span> <span class="text-muted"><?php echo _d($expense->due_date); ?></span>
                           </p>

                           
                    
                  </p>
              
                 
                  <?php if($expense->note != ''){ ?>
                  <p class="bold mbot5"><?php echo _l('expense_note'); ?></p>
                  <p class="text-muted mbot15"><?php echo $expense->note; ?></p>
                  <?php } ?>
                  <?php if($expense->reason_for_void != ''){ ?>
                  <p class="bold mbot5"><?php echo _l('reason_for_void'); ?></p>
                  <p class="text-muted mbot15"><?php echo $expense->reason_for_void; ?></p>
                  <?php } ?>
               </div>
               <div class="col-md-6" id="expenseReceipt">
                  <h4 class="bold mbot25"><?php echo _l('acc_attachment'); ?></h4>
                  <?php if(empty($expense->attachment)) { ?>
                  <?php echo form_open('admin/accounting/add_bill_attachment/'.$expense->id,array('class'=>'mtop10 dropzone dropzone-expense-preview dropzone-manual','id'=>'expense-receipt-upload')); ?>
                  <div id="dropzoneDragArea" class="dz-default dz-message">
                     <span><?php echo _l('acc_attachment'); ?></span>
                  </div>
                  <?php echo form_close(); ?>
                  <?php }  else { ?>
                  <div class="row">
                     <div class="col-md-10">
                        <i class="<?php echo get_mime_class($expense->filetype); ?>"></i> <a href="<?php echo admin_url('accounting/download_file/bill/'.$expense->id); ?>"><?php echo $expense->attachment; ?></a>
                     </div>
                     <?php if($expense->attachment_added_from == get_staff_user_id() || is_admin()){ ?>
                     <div class="col-md-2 text-right">
                        <a href="<?php echo admin_url('accounting/delete_bill_attachment/'.$expense->id.'/1'); ?>" class="text-danger _delete"><i class="fa fa fa-times"></i></a>
                     </div>
                    <?php } ?>
                  </div>
                  <?php } ?>
               </div>
               <div class="col-md-12">
                    <table class="table items items-preview invoice-items-preview" data-type="invoice">
                        <thead>
                            <tr>
                                <th align="left" width="70%"><?php echo _l('debit_account'); ?></th>
                                <th width="30%" align="right"><?php echo _l('amount'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($expense->debit_account as $debit_account){ ?>
                                <tr>
                                    <td align="left"><?php echo get_account_name_by_id($debit_account['account']); ?></td>
                                    <td align="right"><?php echo app_format_money($debit_account['amount'], $currency->name); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
               </div>
               <div class="col-md-12">
                    <table class="table items items-preview invoice-items-preview" data-type="invoice">
                        <thead>
                            <tr>
                                <th align="left" width="70%"><?php echo _l('credit_account'); ?></th>
                                <th width="30%" align="right"><?php echo _l('amount'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($expense->credit_account as $credit_account){ ?>
                                <tr>
                                    <td align="left"><?php echo get_account_name_by_id($credit_account['account']); ?></td>
                                    <td align="right"><?php echo app_format_money($credit_account['amount'], $currency->name); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
               </div>
                <?php
                    $total = $expense->amount;
                    $bil_amount_left = bill_amount_left($expense->id);
                    $amount_paid = $total - $bil_amount_left;
                ?>
               <div class="col-md-5 col-md-offset-7">
                    <table class="table text-right">
                        <tbody>
                            <tr>
                               <td><span class="bold"><?php echo _l('total'); ?></span></td>
                               <td class="total"><?php echo app_format_money($expense->amount, $currency->name); ?></td>
                            </tr>
                            <tr>
                               <td><span class="bold"><?php echo _l('amount_paid'); ?></span></td>
                               <td class="total"><?php echo app_format_money($amount_paid, $currency->name); ?></td>
                            </tr>
                            <tr>
                                <td><span class="text-danger bold"><?php echo _l('invoice_amount_due'); ?></span></td>
                                <td><span class="text-danger"><?php echo app_format_money($bil_amount_left ,$currency->name); ?></span></td>
                            </tr>
                        </tbody>
                    </table>
               </div>
            </div>

            
         </div>
         <div role="tabpanel" class="tab-pane ptop10" id="list_expense"  >
           
                <div class="table-responsive">
                  <table class="table dt-table table-hover table-striped no-mtop">
                      <thead>
                          <tr>
                              <th><span class="bold"><?php echo _l('pay_bill'); ?> #</span></th>
                              <th><span class="bold"><?php echo _l('ref_number'); ?></span></th>
                              <th><span class="bold"><?php echo _l('acc_date'); ?></span></th>
                              <th><span class="bold"><?php echo _l('acc_amount'); ?></span></th>
                              <th><span class="bold"><?php echo _l('options'); ?></span></th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php $this->load->model('accounting/accounting_model');

                          foreach($list_pay_bill as $row){ 
                            $pay_bill = $this->accounting_model->get_pay_bill($row['pay_bill']);
                            ?>
                          <tr class="payment">
                              <td><?php echo $pay_bill->pay_number; ?></td>
                              <td><?php echo $pay_bill->reference_no; ?></td>
                              <td><?php echo _d($pay_bill->date); ?></td>
                              <td><?php echo app_format_money($pay_bill->amount, $currency->name); ?></td>
                              <td>
                              <a href="<?php echo admin_url('accounting/pay_bill/'.$pay_bill->id); ?>" class="btn btn-default btn-icon"><i class="fa fa-pencil-square"></i></a>

                              <a href="<?php echo admin_url('accounting/delete_pay_bill/'.$row['bill_id'].'/'.$pay_bill->id); ?>" class="btn btn-default btn-icon _delete"><i class="fa fa-remove"></i></a>
                              </td>
                          </tr>
                          <?php } ?>
                      </tbody>
                  </table>
              </div>

            </div>
        
      </div>
   </div>
</div>
</div>
<script>
   init_btn_with_tooltips();
   init_selectpicker();
   init_datepicker();
   init_tabs_scrollable();

   if($('#dropzoneDragArea').length > 0){
     if(typeof(expensePreviewDropzone) != 'undefined'){
       expensePreviewDropzone.destroy();
     }
     expensePreviewDropzone = new Dropzone("#expense-receipt-upload", appCreateDropzoneOptions({
       clickable: '#dropzoneDragArea',
       maxFiles: 1,
       success:function(file,response){
         init_bills(<?php echo $expense->id; ?>);
       }
     }));
   }
</script>
