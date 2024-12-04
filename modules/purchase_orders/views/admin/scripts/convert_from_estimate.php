<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="estimate_to_purchase_order pull-right">
    <?php if (empty($estimate->purchase_orderid) && empty($estimate->invoiceid) && empty($estimate->delivery_noteid)) { ?>
        <?php if ((int)get_option('purchase_order_allow_creating_from_estimate') && staff_can('create', 'purchase_orders') && !empty($estimate->clientid)) { ?>
            <div class="btn-group pull-right mleft5" data-toggle="tooltip" data-title="<?= _l('estimate_convert_to_purchase_order_full'); ?>">
                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <?php echo _l('estimate_convert_to_purchase_order'); ?> <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                    <li><a href="<?php echo admin_url('purchase_orders/convert_from_estimate/' . $estimate->id); ?>"><?php echo _l('convert_and_save_as_confirmed'); ?></a>
                    </li>
                    <li class="divider"></li>

                    <li><a href="<?php echo admin_url('purchase_orders/convert_from_estimate/' . $estimate->id . '?save_as_new=true'); ?>"><?php echo _l('convert'); ?></a>
                    </li>

                </ul>
            </div>
        <?php } ?>
        <?php } else if (!empty($estimate->purchase_orderid)) {
        $formated_poid = format_purchase_order_number($estimate->purchase_orderid);
        if (!empty($formated_poid)) { ?>
            <a href="<?php echo admin_url('purchase_orders/list_purchase_orders/' . $estimate->purchase_orderid); ?>" class="btn btn-primary mleft10  pull-right"><?php echo $formated_poid; ?></a>
    <?php }
    } ?>
</div>

<script>
    document.querySelector('.pull-right._buttons').appendChild(document.querySelector('.estimate_to_purchase_order'));
    // Remove convert to invoice button
    <?php if (!empty($estimate->purchase_orderid) && !empty($formated_poid)) : ?>
        var buttons = document.querySelectorAll('.pull-right._buttons button');
        if (buttons.length)
            buttons.forEach(function(button) {
                // Check if the button's text content contains the desired text
                if (button.textContent.toLocaleLowerCase().trim().includes("<?= _l('estimate_convert_to_invoice'); ?>"
                        .trim().toLocaleLowerCase())) {
                    button.remove();
                }
            });
    <?php endif; ?>
</script>