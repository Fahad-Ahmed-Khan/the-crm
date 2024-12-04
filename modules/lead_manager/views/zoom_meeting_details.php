<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-header">
 <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
 <h4 class="modal-title">
Meeting Details
</h4> 
</div>
<style type="">
  .cu_zoom td{    width: 20%;     font-size: 14px;     padding-bottom: 10px !important;}
  .cu_zoom th{       font-weight: 500;
    font-size: 13px;
    width: 15%;}

.cu_zoom {
    max-width: 90%;
    margin: 0 auto;
    margin-top: 0 !important;
}

.cu_zoom {
    max-width: 86%;
    margin: 0 auto;
    margin-top: 0 !important;
}
</style>
<div class="modal-body">
  <div class="row">
     <div class="col-md-12">
   <table class="table meeting_details_table table-striped cu_zoom" >
    <tbody>

      <tr>
        <th>Customer Name :</th>
        <td><?php echo (isset($meeting_details))?$meeting_details->name:'NA'; ?></td>

        <th>Customer Email:</th>
        <td><?php echo (isset($meeting_details))?$meeting_details->email:'NA'; ?></td>
      </tr>


       <tr>
        <th>Staff Name:</th>
        <td><?php echo (isset($meeting_details))?$meeting_details->staff_name:'NA'; ?></td>

        <th>Staff Email :</th>
        <td><?php echo (isset($meeting_details))?$meeting_details->staff_email:'NA'; ?></td>
      </tr>

       <tr>
        <th>Start Time :</th>
        <td> <?php echo (isset($meeting_details))?$meeting_details->meeting_date:'NA'; ?></td>

        <th>Created Date :</th>
        <td><?php echo (isset($meeting_details))?$meeting_details->created_at:'NA'; ?></td>
      </tr>

       <tr>
        <th>Duration:</th>
        <td><?php echo (isset($meeting_details))?$meeting_details->meeting_duration .' minutes':'NA'; ?></td>

        <th>Meeting Type:</th>
        <td><?php echo (isset($meeting_details))?'Scheduled Meeting':'NA'; ?></td>
      </tr>

       <tr>
        <th>Topic:</th>
        <td><?php echo (isset($meeting_details))?$meeting_details->meeting_agenda:'NA'; ?></td>
        <th>Description/Agenda:</th>
        <td> <?php echo (isset($meeting_details))?$meeting_details->meeting_description:'NA'; ?></td>
       
      </tr>

       <tr>
         <th>Status:</th>
        <td> <?php
          $status=get_zoom_status_by_id($meeting_details->status);
           echo $status['name'] ?></td>
        <th>Time Zone:</th>
        <td><?php echo (isset($meeting_details))?$meeting_details->timezone:'NA'; ?></td>

       
      </tr>

       <tr>
         <th>Country:</th>
        <td><?php echo (isset($meeting_details))?get_country_name($meeting_details->country):'NA'; ?></td>
        <th>Join Url:</th>
        <td><a href="<?=$meeting_details->join_url  ?>"><?=$meeting_details->join_url  ?></a></td>
      </tr>

       <tr>
        <th>Remark :</th>
        <td colspan="4"><?php echo get_latest_zoom_meeting_remark($meeting_details->id); ?></td>
      </tr>

      <tr class="text-center">
        <td colspan="4"><a href="<?php echo $meeting_details->start_url; ?>" target="_blank"><button class="btn btn-success">Start Meeting</button></a> </td>
      </tr>

    </tbody>
  </table>
  </div>  
  </div>
</div>
<div class="modal-footer">
  <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
</div>

