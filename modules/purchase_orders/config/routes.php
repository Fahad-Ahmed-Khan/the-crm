<?php

defined('BASEPATH') or exit('No direct script access allowed');

$route['purchase_orders/admin/custom_fields/(:any)'] = 'purchase_order_custom_fields/$1';
$route['purchase_orders/admin/custom_fields/(:any)/(:any)'] = 'purchase_order_custom_fields/$1/$2';
$route['purchase_orders/admin/custom_fields/(:any)/(:any)/(:any)'] = 'purchase_order_custom_fields/$1/$2/$3';
