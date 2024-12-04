<script>
   (function($) {
    "use strict";

      appValidateForm($('#import_form'),{file_csv:{required:true,extension: "xlsx|xls|csv"},source:'required',status:'required', bank_account: 'required'});
      // function 

      if('<?php echo new_html_entity_decode($active_language) ?>' == 'vietnamese')
      {
        $( "#dowload_file_sample" ).append( '<a href="'+ site_url+'modules/accounting/uploads/file_sample/Sample_import_banking_file_vi.xlsx" class="btn btn-primary" ><?php echo _l('download_sample') ?></a><hr>' );

      }else{
        $( "#dowload_file_sample" ).append( '<a href="'+ site_url+'modules/accounting/uploads/file_sample/Sample_import_banking_file_en.xlsx" class="btn btn-primary" ><?php echo _l('download_sample') ?></a><hr>' );
      }

  })(jQuery);

function uploadfilecsv(){
  "use strict";

    if(($("#file_csv").val() != '') && ($("#file_csv").val().split('.').pop() == 'xlsx' || $("#file_csv").val().split('.').pop() == 'xls' || $("#file_csv").val().split('.').pop() == 'csv')){

    if($('select[name="bank_account"]').val() == ''){
      alert_float('warning', "<?php echo _l('please_select_a_bank_account') ?>");
      
      return false;
    }
    var formData = new FormData();
    formData.append("file_csv", $('#file_csv')[0].files[0]);
    if(<?php echo  acc_check_csrf_protection(); ?>){
        formData.append(csrfData.token_name, csrfData.hash);
    }

    formData.append("leads_import", $('input[name="leads_import"]').val());
    formData.append("bank_account", $('select[name="bank_account"]').val());

    //show box loading
    var html = '';
      html += '<div class="Box">';
      html += '<span>';
      html += '<span></span>';
      html += '</span>';
      html += '</div>';
      $('#box-loading').html(html);
      $('button[id="uploadfile"]').attr( "disabled", "disabled" );

    $.ajax({ 
      url: admin_url + 'accounting/import_file_xlsx_posted_bank_transactions', 
      method: 'post', 
      data: formData, 
      contentType: false, 
      processData: false
      
    }).done(function(response) {
      response = JSON.parse(response);

      //hide boxloading
      $('#box-loading').html('');
      $('button[id="uploadfile"]').removeAttr('disabled');

      $("#file_csv").val(null);
      $("#file_csv").change();
       $(".panel-body").find("#file_upload_response").html();

      if($(".panel-body").find("#file_upload_response").html() != ''){
        $(".panel-body").find("#file_upload_response").empty();
      };


        $( "#file_upload_response" ).append( "<h4><?php echo _l("_Result") ?></h4><h5><?php echo _l('import_line_number') ?> :"+response.total_rows+" </h5>" );
   

     
        $( "#file_upload_response" ).append( "<h5><?php echo _l('import_line_number_success') ?> :"+response.total_row_success+" </h5>" );



        $( "#file_upload_response" ).append( "<h5><?php echo _l('import_line_number_failed') ?> :"+response.total_row_false+" </h5>" );


      if((response.total_row_false > 0) || (response.total_rows_data_error > 0))
      {
        $( "#file_upload_response" ).append( '<a href="'+site_url +response.filename+'" class="btn btn-warning"  ><?php echo _l('download_file_error') ?></a>' );
      }
      if(response.total_rows < 1){
        alert_float('warning', response.message);
      }
    });
    return false;
    }else if($("#file_csv").val() != ''){
      alert_float('warning', "<?php echo _l('_please_select_a_file') ?>");
    }
}
</script>