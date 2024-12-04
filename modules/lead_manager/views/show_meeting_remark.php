<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-header">
 <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
 <h4 class="modal-title">
  Remarks
</h4>
</div>
<div class="modal-body">
  <div class="row">
   <div class="col-md-12">
     <div class="top-lead-menu">
      <div class="horizontal-scrollable-tabs preview-tabs-top">
       <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
       <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
       <div class="horizontal-tabs">
        <ul class="nav-tabs-horizontal nav nav-tabs<?php if(!isset($zoom_meeting_remarks)){echo ' lead-new';} ?>" role="tablist">
         <?php if(isset($zoom_meeting_remarks)){ ?>
           <li role="presentation" class="active">
            <a href="#lead_activity" aria-controls="lead_activity" role="tab" data-toggle="tab">
              <?php echo _l('Remark'); ?>
            </a>
          </li>
        <?php } ?>
      </ul>
    </div>
  </div>
</div>
<!-- Tab panes -->
<div class="tab-content mtop20">
  <!-- from leads modal -->
  <?php if(isset($zoom_meeting_remarks)){ ?>
    <div role="tabpanel" class="tab-pane active" id="lead_activity">
     <div class="panel_s no-shadow">
      <div class="activity-feed">
       <?php foreach($zoom_meeting_remarks as $log){ ?>
         <div class="feed-item">
          <div class="row">
            <div class="col-lg-3">
              <div class="date">
                <span class="text-has-action" data-toggle="tooltip" data-title="Meeting Remark">
                  Remark
                </span>
              </div>
              <div class="date">
                <span class="text-has-action" data-toggle="tooltip" data-title="<?php echo _dt($log->date); ?>">
                  <?php echo time_ago($log->date); ?>
                </span>
              </div>

            </div>
            <div class="col-lg-9">
             <div class="lm_media_div">
              <?php
              if(!empty($log->remark)){
                echo $log->remark;
              }else{
                echo '<p>'.$log->remark.'</p>';
              }
              ?>
            </div>
          </div>
        </div>
      </div>
    <?php } ?>
  </div>
  <div class="clearfix"></div>
</div>
</div>
<?php } ?>
</div>
</div>
</div>
</div>