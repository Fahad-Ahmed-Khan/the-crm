<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="_filters _hidden_inputs hidden">
                </div>
                <div class="panel_s mbot5">
                    <div class="panel-body">
                        <div class="_buttons">
                            <div class="row mtop0">
                                <div class="col-lg-12">
                                    <a class="btn btn-primary" href="#" data-toggle="modal" data-target="#template-modal"><?php echo _l('lm_manage_whatsapp_templates_add'); ?></a>
                                </div>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <hr class="hr-panel-heading" />
                        <div class="tab-content">
                            <div class="row">
                                <div class="col-md-12">
                                    <?php
                                    $table_data = array();
                                    $_table_data[] =   array(
                                        'name' => _l('lm_wh_temp_id'),
                                        'th_attrs' => array('class' => 'toggleable', 'id' => 'th-id')
                                    );
                                    $_table_data[] = array(
                                        'name' => _l('lm_wh_temp_name'),
                                        'th_attrs' => array('class' => 'toggleable', 'id' => 'th-name')
                                    );
                                    $_table_data[] =  array(
                                        'name' => _l('lm_wh_temp_language'),
                                        'th_attrs' => array('class' => 'toggleable', 'id' => 'th-language')
                                    );
                                    $_table_data[] = array(
                                        'name' => _l('lm_wh_temp_status'),
                                        'th_attrs' => array('class' => 'toggleable', 'id' => 'th-status')
                                    );
                                    $_table_data[] = array(
                                        'name' => _l('lm_wh_temp_action'),
                                        'th_attrs' => array('class' => 'toggleable', 'id' => 'th-action')
                                    );
                                    foreach ($_table_data as $_t) {
                                        array_push($table_data, $_t);
                                    }
                                    render_datatable(
                                        $table_data,
                                        'whatsapp_templates'
                                    ); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="template-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <?php
        echo form_open(current_url(), ['id' => 'lm-wh-template-form']); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo _l('lm_add_template_modal_header'); ?></h4>
            </div>
            <div class="modal-body">
                <?php
                echo render_input('template_id', '<a href="https://console.twilio.com/us1/develop/sms/senders/whatsapp-templates" target="_blank">'._l("lm_wh_temp_id").'</a>','','text', ['placeholder'=>_l('lm_wh_temp_id_placeholder')]);
                echo render_input('template_name', _l('lm_wh_temp_name')); ?>
                <?php if (!is_language_disabled()) { ?>
                    <div class="form-group select-placeholder">
                        <label for="template_name" class="control-label"><?php echo _l('lm_wh_temp_language') ?></label>
                        <select name="language" id="language" class="form-control selectpicker" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" data-live-search="true">
                            <?php $selected = $staff->default_language ?? ''; ?>
                            <?php foreach ($languages as $availableLanguage) {
                            ?>
                                <option value="<?php echo $availableLanguage; ?>" <?php echo ($availableLanguage == $selected) ? 'selected' : '' ?>>
                                    <?php echo ucfirst($availableLanguage); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                <?php } ?>
                <?php
                echo render_textarea('body_data', _l('lm_wh_temp_body'));
                ?>
                <?php
                echo render_select('status', [['id' => 'active', 'name' => 'Active'], ['id' => 'inactive', 'name' => 'Inactive']], ['id', 'name'], _l('lm_wh_temp_status'));
                ?>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
        <?php echo form_close(); ?>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        validate_template_form();
    })

    function validate_template_form() {
        var form = $('#lm-wh-template-form');
        var validationObject = {
            template_id: 'required',
            template_name: 'required',
            language: 'required',
            body_data: 'required',
            status: 'required'
        };
        appValidateForm(form, validationObject, wh_tem_form_handler);
    }

    function wh_tem_form_handler(form) {
        form = $(form);
        var data = form.serialize();
        $.post(form.attr('action'), data).done(function(response) {
            response = JSON.parse(response);
            if (response.status == 'danger') {
                alert_float(response.status, response.message);
            } else {
                alert_float(response.status, response.message);
                if ($.fn.DataTable.isDataTable('.table-whatsapp_templates')) {
                    form.trigger("reset")
                    $("#template-modal").modal('hide');
                    $('.table-whatsapp_templates').DataTable().ajax.reload(null, false);
                }
            }
        }).fail(function(data) {
            alert_float('danger', data.responseText);
            return false;
        });
        return false;
    }

    function delTemplate(id) {
        if (confirm_delete()) {
            $.post(admin_url + 'lead_manager/setup/del_template', {'temp_id' : id}).done(function(resp) {
                resp = JSON.parse(resp);
                $('.table-whatsapp_templates').DataTable().ajax.reload(null, false);
                alert_float(resp.status, resp.message);
            }).fail(function(resp) {
                resp = JSON.parse(resp);
                $('.table-whatsapp_templates').DataTable().ajax.reload(null, false);
                alert_float('danger', resp.message);
            });
        }
    }
</script>