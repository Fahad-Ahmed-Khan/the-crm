<?php

defined('BASEPATH') or exit('No direct script access allowed');

$dimensions = $pdf->getPageDimensions();

$info_right_column = '';
$info_left_column  = '';

$info_right_column .= '<span style="font-weight:bold;font-size:27px;">' . _l('purchase_order_pdf_heading') . '</span><br />';
$info_right_column .= '<b style="color:#4e4e4e;"># ' . $purchase_order_number . '</b>';

if (get_option('show_status_on_pdf_ei') == 1) {
    $info_right_column .= '<br /><span style="color:rgb(' . purchase_order_status_color_pdf($status) . ');text-transform:uppercase;">' . format_purchase_order_status($status, '', false) . '</span>';
}

// Add logo
$info_left_column .= pdf_logo_url();
// Write top left logo and right column info/text
pdf_multi_row($info_left_column, $info_right_column, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

$pdf->ln(10);

$organization_info = '<div style="color:#424242;">';
$organization_info .= format_organization_info();
$organization_info .= '</div>';

// Purchase order to
$purchase_order_info = '<b>' . _l('purchase_order_to') . '</b>';
$purchase_order_info .= '<div style="color:#424242;">';
$purchase_order_info .= format_customer_info($purchase_order, 'purchase_order', 'billing');
$purchase_order_info .= '</div>';

// ship to to
if ($purchase_order->include_shipping == 1 && $purchase_order->show_shipping_on_purchase_order == 1) {
    $purchase_order_info .= '<br /><b>' . _l('ship_to') . '</b>';
    $purchase_order_info .= '<div style="color:#424242;">';
    $purchase_order_info .= format_customer_info($purchase_order, 'purchase_order', 'shipping');
    $purchase_order_info .= '</div>';
}

$purchase_order_info .= '<br />' . _l('purchase_order_data_date') . ': ' . _d($purchase_order->date) . '<br />';

if (!empty($purchase_order->reference_no)) {
    $purchase_order_info .= _l('reference_no') . ': ' . $purchase_order->reference_no . '<br />';
}

if ($purchase_order->sale_agent && get_option('show_sale_agent_on_purchase_orders') == 1) {
    $purchase_order_info .= _l('sale_agent_string') . ': ' . get_staff_full_name($purchase_order->sale_agent) . '<br />';
}

if ($purchase_order->project_id && get_option('show_project_on_purchase_order') == 1) {
    $purchase_order_info .= _l('project') . ': ' . get_project_name_by_id($purchase_order->project_id) . '<br />';
}

foreach ($pdf_custom_fields as $field) {
    $value = get_custom_field_value($purchase_order->id, $field['id'], 'purchase_order');
    if ($value == '') {
        continue;
    }
    $purchase_order_info .= $field['name'] . ': ' . $value . '<br />';
}

$left_info  = $swap == '1' ? $purchase_order_info : $organization_info;
$right_info = $swap == '1' ? $organization_info : $purchase_order_info;

pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

// The Table
$pdf->Ln(hooks()->apply_filters('pdf_info_and_table_separator', 6));

// The items table
$items = get_items_table_data($purchase_order, 'purchase_order', 'pdf');

$tblhtml = $items->table();

$pdf->writeHTML($tblhtml, true, false, false, false, '');

$pdf->Ln(8);
$tbltotal = '';
$tbltotal .= '<table cellpadding="6" style="font-size:' . ($font_size + 4) . 'px">';
$tbltotal .= '
<tr>
    <td align="right" width="85%"><strong>' . _l('purchase_order_subtotal') . '</strong></td>
    <td align="right" width="15%">' . app_format_money($purchase_order->subtotal, $purchase_order->currency_name) . '</td>
</tr>';

if (is_sale_discount_applied($purchase_order)) {
    $tbltotal .= '
    <tr>
        <td align="right" width="85%"><strong>' . _l('purchase_order_discount');
    if (is_sale_discount($purchase_order, 'percent')) {
        $tbltotal .= ' (' . app_format_number($purchase_order->discount_percent, true) . '%)';
    }
    $tbltotal .= '</strong>';
    $tbltotal .= '</td>';
    $tbltotal .= '<td align="right" width="15%">-' . app_format_money($purchase_order->discount_total, $purchase_order->currency_name) . '</td>
    </tr>';
}

foreach ($items->taxes() as $tax) {
    $tbltotal .= '<tr>
    <td align="right" width="85%"><strong>' . $tax['taxname'] . ' (' . app_format_number($tax['taxrate']) . '%)' . '</strong></td>
    <td align="right" width="15%">' . app_format_money($tax['total_tax'], $purchase_order->currency_name) . '</td>
</tr>';
}

if ((int)$purchase_order->adjustment != 0) {
    $tbltotal .= '<tr>
    <td align="right" width="85%"><strong>' . _l('purchase_order_adjustment') . '</strong></td>
    <td align="right" width="15%">' . app_format_money($purchase_order->adjustment, $purchase_order->currency_name) . '</td>
</tr>';
}

$tbltotal .= '
<tr style="background-color:#f0f0f0;">
    <td align="right" width="85%"><strong>' . _l('purchase_order_total') . '</strong></td>
    <td align="right" width="15%">' . app_format_money($purchase_order->total, $purchase_order->currency_name) . '</td>
</tr>';

$tbltotal .= '</table>';

$pdf->writeHTML($tbltotal, true, false, false, false, '');

if (get_option('total_to_words_enabled') == 1) {
    // Set the font bold
    $pdf->SetFont($font_name, 'B', $font_size);
    $pdf->writeHTMLCell('', '', '', '', _l('num_word') . ': ' . $CI->numberword->convert($purchase_order->total, $purchase_order->currency_name), 0, 1, false, true, 'C', true);
    // Set the font again to normal like the rest of the pdf
    $pdf->SetFont($font_name, '', $font_size);
    $pdf->Ln(4);
}

if (!empty($purchase_order->clientnote)) {
    $pdf->Ln(4);
    $pdf->SetFont($font_name, 'B', $font_size);
    $pdf->Cell(0, 0, _l('purchase_order_note'), 0, 1, 'L', 0, '', 0);
    $pdf->SetFont($font_name, '', $font_size);
    $pdf->Ln(2);
    $pdf->writeHTMLCell('', '', '', '', $purchase_order->clientnote, 0, 1, false, true, 'L', true);
}

if (!empty($purchase_order->terms)) {
    $pdf->Ln(4);
    $pdf->SetFont($font_name, 'B', $font_size);
    $pdf->Cell(0, 0, _l('terms_and_conditions') . ":", 0, 1, 'L', 0, '', 0);
    $pdf->SetFont($font_name, '', $font_size);
    $pdf->Ln(2);
    $pdf->writeHTMLCell('', '', '', '', $purchase_order->terms, 0, 1, false, true, 'L', true);
}
