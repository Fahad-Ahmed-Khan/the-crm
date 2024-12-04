<?php
defined('BASEPATH') or exit('No direct script access allowed');
$has_permission_delete = has_permission('leads', '', 'delete');
$years = $this->ci->lead_manager_model->get_leads_years();
$months = [1,2,3,4,5,6,7,8,9,10,11,12];
$aColumns = [
    db_prefix() . 'lead_manager_mailbox.id as eid',
    'fromName',
    'to_email',
    'subject',
    'mail_date',
    'is_read'
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'lead_manager_mailbox';
$join = [
    'LEFT JOIN ' . db_prefix() . 'lead_manager_mailbox_attachments ON ' . db_prefix() . 'lead_manager_mailbox_attachments.staff_id = ' . db_prefix() . 'lead_manager_mailbox.staffid AND '.db_prefix().'lead_manager_mailbox_attachments.mailbox_id='.db_prefix().'lead_manager_mailbox.id' 
];

$where  = ['AND '.db_prefix() . 'lead_manager_mailbox.staffid='.get_staff_user_id()];
$filter = false;


if ($this->ci->input->post('direction')) {
    array_push($where, "AND direction = '" . $this->ci->input->post('direction') . "'");
}
if ($this->ci->input->post('status')) {
    array_push($where, "AND status = '" . $this->ci->input->post('status') . "'");
}


$additionalColumns = ['is_attachment','is_favourite','is_bookmark',db_prefix().'lead_manager_mailbox_attachments.file_name as file','email_size','from_email','status', db_prefix().'lead_manager_mailbox_attachments.mailbox_id'];
$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, $additionalColumns, 'group by eid');
$output  = $result['output']; 
$rResult = $result['rResult'];
//print_r($rResult); die;
foreach ($rResult as $aRow) {
    $row = [];
    $is_favourite = '<span><a href="javascript:void(0);" data-id="'.$aRow['eid'].'" data-action="star" data-table=".table-lm-mailbox" onclick="lm_mb_single_inbox(this); return false;"><i class="fa fa-star-o" aria-hidden="true"></i></a></span>';
    $is_bookmark = '<span><a href="javascript:void(0);" data-id="'.$aRow['eid'].'" data-action="bookmark" data-table=".table-lm-mailbox" onclick="lm_mb_single_inbox(this); return false;"><i class="fa fa-bookmark-o" aria-hidden="true"></i></a></span>';
    $row_options = 'row-options';
    if($aRow['is_favourite']){
        $is_favourite = '<span><a href="javascript:void(0);" data-id="'.$aRow['eid'].'" data-action="unstar" data-table=".table-lm-mailbox" onclick="lm_mb_single_inbox(this); return false;"><i class="fa fa-star text-warning" aria-hidden="true"></i></a></span>';
        $row_options='';
    }if($aRow['is_bookmark']){
        $is_bookmark = '<span><a href="javascript:void(0);" data-id="'.$aRow['eid'].'" data-action="unbookmark" data-table=".table-lm-mailbox" onclick="lm_mb_single_inbox(this); return false;"><i class="fa fa-bookmark text-muted" aria-hidden="true"></i></a></span>';
        $row_options='';
    }
    $row[] = '<div class="checkbox main_icon_check"><input type="checkbox" value="' . $aRow['eid'] . '"><label></label></div>';
    $fromName = $aRow['fromName'] ?? $aRow['from_email'];
    $row[] = '<a href="javascript:void(0);" onclick="viewMailBoxMail('.$aRow['eid'].')">'.$fromName.'</a>'.'<div class="'.$row_options.'">'.$is_favourite.' | '.$is_bookmark.'<span> | <a href="javascript:void(0);" data-id="'.$aRow['eid'].'" data-action="delete" data-table=".table-lm-mailbox" onclick="lm_mb_single_inbox(this); return false;"><i class="fa fa-trash-o text-danger" aria-hidden="true"></i></a></span></div>';
    $row[] = '<a href="javascript:void(0);" onclick="viewMailBoxMail('.$aRow['eid'].')">'.$aRow['to_email'].'</a>';
    // $row[] = $aRow['to_email'];
    $subject = $aRow['subject'];
    if($aRow['is_attachment']){
        $subject .= ' <a href="'.admin_url('lead_manager/download_attachemnts/'.$aRow['eid']). '" target="_blank"><i class="fa fa-paperclip"></i></a>';
    }
    $subject .= ' <p class="text-muted text-left">'.formatSizeUnits($aRow['email_size']).'</p>';
    $row[] = $subject;
    $row[] = _dt($aRow['mail_date']);
    $row[] = $aRow['is_read'];
    if ($aRow['is_read'] == 0 && $aRow['status']=='get') {
        $row['DT_RowClass'] = 'alert-warning';
    }

    if (isset($row['DT_RowClass'])) {
        $row['DT_RowClass'] .= ' has-row-options';
    } else {
        $row['DT_RowClass'] = 'has-row-options';
    }
    $output['aaData'][] = $row;
}
