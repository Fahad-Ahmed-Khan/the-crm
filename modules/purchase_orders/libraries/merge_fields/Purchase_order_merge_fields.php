<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Purchase_order_merge_fields extends App_merge_fields
{
    public function build()
    {
        return [
            [
                'name'      => 'Purchase Order Link',
                'key'       => '{purchase_order_link}',
                'available' => [
                    'purchase_order',
                ]
            ],
            [
                'name'      => 'Purchase Order Number',
                'key'       => '{purchase_order_number}',
                'available' => [
                    'purchase_order',
                ],
            ],
            [
                'name'      => 'Reference no.',
                'key'       => '{purchase_order_reference_no}',
                'available' => [
                    'purchase_order',
                ],
            ],
            [
                'name'      => 'Purchase Order Date',
                'key'       => '{purchase_order_date}',
                'available' => [
                    'purchase_order',
                ],
            ],
            [
                'name'      => 'Purchase Order Status',
                'key'       => '{purchase_order_status}',
                'available' => [
                    'purchase_order',
                ],
            ],
            [
                'name'      => 'Purchase Order Sale Agent',
                'key'       => '{purchase_order_sale_agent}',
                'available' => [
                    'purchase_order',
                ],
            ],
            [
                'name'      => 'Purchase Order Total',
                'key'       => '{purchase_order_total}',
                'available' => [
                    'purchase_order',
                ],
            ],
            [
                'name'      => 'Purchase Order Subtotal',
                'key'       => '{purchase_order_subtotal}',
                'available' => [
                    'purchase_order',
                ],
            ]
        ];
    }

    /**
     * Merge fields for purchase_orders
     * @param  mixed $purchase_orderid purchase_order id
     * @return array
     */
    public function format($purchase_orderid)
    {
        $fields = [];
        $this->ci->db->where('id', $purchase_orderid);
        $purchase_order = $this->ci->db->get(db_prefix() . 'purchase_orders')->row();

        if (!$purchase_order) {
            return $fields;
        }

        $currency = get_currency($purchase_order->currency);

        $fields['{purchase_order_sale_agent}']   = get_staff_full_name($purchase_order->sale_agent);
        $fields['{purchase_order_total}']        = app_format_money($purchase_order->total, $currency);
        $fields['{purchase_order_subtotal}']     = app_format_money($purchase_order->subtotal, $currency);
        $fields['{purchase_order_link}']         = site_url('purchase_orders/client/po/' . $purchase_orderid . '/' . $purchase_order->hash);
        $fields['{purchase_order_number}']       = format_purchase_order_number($purchase_orderid);
        $fields['{purchase_order_reference_no}'] = $purchase_order->reference_no;
        $fields['{purchase_order_date}']         = _d($purchase_order->date);
        $fields['{purchase_order_status}']       = format_purchase_order_status($purchase_order->status, '', false);
        $fields['{project_name}']          = get_project_name_by_id($purchase_order->project_id);
        $fields['{purchase_order_short_url}']    = get_purchase_order_shortlink($purchase_order);

        $custom_fields = get_custom_fields('purchase_order');
        foreach ($custom_fields as $field) {
            $fields['{' . $field['slug'] . '}'] = get_custom_field_value($purchase_orderid, $field['id'], 'purchase_order');
        }

        return hooks()->apply_filters('purchase_order_merge_fields', $fields, [
            'id'       => $purchase_orderid,
            'purchase_order' => $purchase_order,
        ]);
    }
}
