<?php
defined('BASEPATH') or exit('No direct script access allowed');
$statuses              = $this->ci->lead_manager_model->get_zoom_statuses();
$this->ci->db->query("SET sql_mode = ''");
$aColumns = [
    'id',
    'name',
    'email',
    'staff_name',
    'meeting_date',
    'status',
    'remark',
    'is_client',
];

$sIndexColumn = 'id';
$sTable       = db_prefix().'lead_manager_zoom_meeting';

$where  = [];
$filter = [];
$join = [];

if($this->ci->input->post('is_client_no')){
    array_push($filter, 'AND is_client=0');
}
if($this->ci->input->post('is_client_yes')){
    if($this->ci->input->post('is_client_no')){
        array_push($filter, 'AND is_client=0 OR is_client=1');
    }else{
        array_push($filter, 'AND is_client=1');
    }
}
if($this->ci->input->post('status_waiting')){
    if($this->ci->input->post('status_end')){
        array_push($filter, 'AND status=0 OR status=1');
    }else{
        array_push($filter, 'AND status=1');
    }
}
if($this->ci->input->post('status_end')){
    array_push($filter, 'AND status=0');
}
if ($this->ci->input->post('period_from')) {
    array_push($where, "AND meeting_date >= '" . to_sql_date($this->ci->input->post('period_from')) . "'");
}
if ($this->ci->input->post('period_to')) {
    array_push($where, "AND meeting_date <= '" . to_sql_date($this->ci->input->post('period_to')) . "'+ INTERVAL 1 DAY");
}

if (count($filter) > 0) {
    array_push($where, 'AND (' . prepare_dt_filter($filter) . ')');
}
if (has_permission('lead_manager', '', 'view_own')) {
    array_push($where, 'AND '. db_prefix().'lead_manager_zoom_meeting.staff_id =' . get_staff_user_id());
}
$result =   data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    db_prefix().'lead_manager_zoom_meeting.email',
    db_prefix().'lead_manager_zoom_meeting.meeting_id',
    
]);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    $row[] = $aRow['id']; 
    $nameRow = $aRow['name'];
    $nameRow .= '<div class="row-options">';
    $nameRow .= '<a href="javascript:void(0);"  onclick="zoomMeetingDetails(' . $aRow['id'] . ');" ><i class="fa fa-eye"></i></a>';
    $nameRow .= ' | <a href="' . admin_url('lead_manager/zoom_meeting/delete_zoom_meeting/' . $aRow['id']) . '" title="' . _l('delete') . '" class="_delete text-danger"><i class="fa fa-trash" aria-hidden="true"></i></a>';
    $nameRow .= ' | <a href="javascript:void(0);"  onclick="zoomMeetingDetailsUpdate(' . $aRow['id'] . ');" ><i class="fa fa-edit"></i></a>';
    $nameRow .= '</div>';

    $row[] = $nameRow;
    $row[] = $aRow['email'];
    $row[] = $aRow['staff_name'];
    $row[] = $aRow['is_client'] ? '<span class="label label-primary inline-block">'._l('lm_client').'</span>' : '<span class="label label-warning inline-block">'._l('lm_lead').'</span>'; 
    $row[] = _dt($aRow['meeting_date']);
    if($aRow['status'] !=null){
        $status = get_zoom_status_by_id($aRow['status'] == 'waiting' ? 1 : 0 );
        if($aRow['status'] == 'end'){
            $outputStatus = '<span class="label label-danger inline-block">' . $status['name'] . '</span>';
        }else{
            $outputStatus = '<span class="inline-block lead-status-'.$aRow['status'].'" style="color:' . $status['color'] . ';border:1px solid ' . $status['color'] . '">'  . $status['name'];
            $locked=false;
            if (!$locked) {
                $outputStatus .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
                $outputStatus .= '<a href="#" style="font-size:14px;vertical-align:middle;" class="dropdown-toggle text-dark" id="tableLeadsStatus-' . $aRow['id'] . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                $outputStatus .= '<span data-toggle="tooltip" title="' . _l('ticket_single_change_status') . '"><i class="fa fa-caret-down" aria-hidden="true"></i></span>';
                $outputStatus .= '</a>';

                $outputStatus .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableLeadsStatus-' . $aRow['id'] . '">';
                foreach ($statuses as $leadChangeStatus) {
                    $leadChangeStatus['id'] = $leadChangeStatus['id'] == 0 ? 0 : 1;
                    $leadChangeStatus['name'] = $leadChangeStatus['id'] == 0 ? 'End' : 'Waiting';
                    $aRowStatus = $aRow['status'] == 'waiting' ? 1 : 0;
                    if ($aRowStatus != $leadChangeStatus['id']) {
                        $outputStatus .= '<li>
                        <a href="javascript:void(0);" onclick="update_meeting_status(' . $leadChangeStatus['id'] . ',' . $aRow['id'] . '); return false;">
                        ' . $leadChangeStatus['name'] . '
                        </a>
                        </li>';
                    }
                }
                $outputStatus .= '</ul>';
                $outputStatus .= '</div>';
            }
            $outputStatus .= '</span>';
        }
    }
    $row[] = $outputStatus;
    $remarkadd_fields='<a href="javascript:void(0);"><i class="fa fa-file-text-o" aria-hidden="true" onclick="saveMeetingRemark('.$aRow['id'].',2);"></i></a>&nbsp;&nbsp;<a href="javascript:void(0);"><i class="fa fa-eye" aria-hidden="true" onclick="showMeetingRemark('.$aRow['id'].',2);"></i></a>';
    $row[] = $remarkadd_fields;
    $row = hooks()->apply_filters('supply_chain_table_row_data', $row, $aRow);
    $output['aaData'][] = $row;
}