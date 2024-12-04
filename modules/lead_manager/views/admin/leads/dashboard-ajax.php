<div class="row">
   <div class="col-lg-3 col-xs-12 col-md-12 total-column">
      <div class="panel_s">
         <div class="panel-body">
            <h3 class="text-muted _total"><?php echo isset($audio_calls['outgoing']) ? $audio_calls['outgoing'] : 0; ?></h3>
            <span class="text-primary">Outbound calls</span>
         </div>
      </div>
   </div>
   <div class="col-lg-3 col-xs-12 col-md-12 total-column">
      <div class="panel_s">
         <div class="panel-body">
          <h3 class="text-muted _total"><?php echo isset($audio_calls['incoming']) ? $audio_calls['incoming'] : 0; ?></h3>
          <span class="text-primary">Inbound calls</span>
       </div>
    </div>
 </div>
 <div class="col-lg-3 col-xs-12 col-md-12 total-column">
   <div class="panel_s">
      <div class="panel-body">
         <h3 class="text-muted _total"><?php echo $missed_call ? $missed_call : 0; ?></h3>
         <span class="text-danger">Missed calls</span>
      </div>
   </div>
</div>
<div class="col-lg-3 col-xs-12 col-md-12 total-column">
   <div class="panel_s">
      <div class="panel-body">
         <h3 class="text-muted _total"><?php echo $leads_converted ? $leads_converted : 0; ?></h3>
         <span class="text-success">Leads converted</span>
      </div>
   </div>
</div>
</div>
<div class="row">
   <div class="col-lg-3 col-xs-12 col-md-12 total-column">
      <div class="panel_s">
         <div class="panel-body">
            <h3 class="text-muted _total"><?php echo $sms ? $sms :0; ?></h3>
            <span class="text-success">SMS Sent</span>
         </div>
      </div>
   </div>
   <div class="col-lg-3 col-xs-12 col-md-12 total-column">
      <div class="panel_s">
         <div class="panel-body">
          <h3 class="text-muted _total"><?php echo $zoom['waiting']+$zoom['end']; ?></h3>
          <span class="text-primary">Scheduled meetings</span>
       </div>
    </div>
 </div>
 <div class="col-lg-3 col-xs-12 col-md-12 total-column">
   <div class="panel_s">
      <div class="panel-body">
         <h3 class="text-muted _total"><?php echo $zoom['waiting'] ? $zoom['waiting'] : 0; ?></h3>
         <span class="text-warning">Upcoming meetings</span>
      </div>
   </div>
</div>
<div class="col-lg-3 col-xs-12 col-md-12 total-column">
   <div class="panel_s">
      <div class="panel-body">
         <h3 class="text-muted _total"><?php echo $zoom['end'] ? $zoom['end'] : 0; ?></h3>
         <span class="text-success">Attended Meetings</span>
      </div>
   </div>
</div>
</div>
<div class="row">
   <div class="col-lg-3 col-xs-12 col-md-12 total-column">
      <div class="panel_s">
         <div class="panel-body">
            <h3 class="text-muted _total"><?php echo isset($audio_calls_duration['incoming']) ? $audio_calls_duration['incoming'] : '00:00:00'; ?></h3>
            <span class="text-primary">Inbound calls durations</span>
         </div>
      </div>
   </div>
   <div class="col-lg-3 col-xs-12 col-md-12 total-column">
      <div class="panel_s">
         <div class="panel-body">
            <h3 class="text-muted _total"><?php echo isset($audio_calls_duration['outgoing']) ? $audio_calls_duration['outgoing'] : '00:00:00'; ?></h3>
            <span class="text-primary">Outbound calls durations</span>
         </div>
      </div>
   </div> 
   <?php if(is_admin()){ ?>
      <div class="col-lg-3 col-xs-12 col-md-12 total-column">
         <div class="panel_s">
            <div class="panel-body">
               <h3 class="text-muted _total">$<?php echo $twilio['balance'] ? $twilio['balance'] : '0:00'; ?></h3>
               <span class="text-warning">Twilio Balance</span>
            </div>
         </div>
      </div>
      <div class="col-lg-3 col-xs-12 col-md-12 total-column">
         <div class="panel_s">
            <div class="panel-body">
             <h3 class="text-muted _total"><?php echo $twilio['numbers'] ? $twilio['numbers'] : '0'; ?></h3>
             <span class="text-primary">Total Twilio Numbers</span>
          </div>
       </div>
    </div>
 <?php } ?>
</div>