<?php

/**
 * Get items table for preview
 * @param  object  $transaction   invoice
 * @param  string  $type          template type
 * @param  string  $for           where the items will be shown, html or pdf
 * @param  boolean $admin_preview is the preview for admin area
 * @return object
 */
function styleflow_get_items_table_data($transaction, $type, $for = 'html', $admin_preview = false)
{
    $className = ucfirst($type).'_items_table';
    $folderName = strtolower($type).'_template';

    include_once(APP_MODULES_PATH . 'styleflow/views/invoice_templates/'.$folderName.'/'.$className.'.php');

    $class = new $className($transaction, 'invoice', $for, $admin_preview);

    if (!$class instanceof App_items_table_template) {
        show_error(get_class($class) . ' must be instance of "App_items_template"');
    }

    return $class;
}

if (get_option('styleflow_selected_invoice_template') !== 'default') {
    hooks()->add_filter('invoice_pdf_class_path', 'styleflow_custom_invoice_template');
}
function styleflow_custom_invoice_template($path, ...$params)
{

    $selectedTemplate = get_option('styleflow_selected_invoice_template');
    $className = ucfirst($selectedTemplate).'_pdf';
    $folderName = strtolower($selectedTemplate).'_template';

    return APP_MODULES_PATH . '/styleflow/views/invoice_templates/'.$folderName.'/'.$className.'.php';
}

function styleflow_supported_invoice_templates()
{
    return [
        [
            'id' => 'default',
            'title' => 'Default Perfex Template',
            'thumbnail' => substr(module_dir_url('styleflow/uploads/examples/default-example-v1.0.0.png'), 0, -1)
        ],
        [
            'id' => 'vblue',
            'title' => 'VBlue Template',
            'thumbnail' => substr(module_dir_url('styleflow/uploads/examples/vblue-example-v1.0.0.png'), 0, -1)
        ],
        [
            'id' => 'vred',
            'title' => 'VRed Template',
            'thumbnail' => substr(module_dir_url('styleflow/uploads/examples/vred-example-v1.0.0.png'), 0, -1)
        ],
        [
            'id' => 'vyellow',
            'title' => 'VYellow Template',
            'thumbnail' => substr(module_dir_url('styleflow/uploads/examples/vyellow-example-v1.0.0.png'), 0, -1)
        ],
        [
            'id' => 'vgreen',
            'title' => 'VGreen Template',
            'thumbnail' => substr(module_dir_url('styleflow/uploads/examples/vgreen-example-v1.0.0.png'), 0, -1)
        ],
        [
            'id' => 'vorange',
            'title' => 'VOrange Template',
            'thumbnail' => substr(module_dir_url('styleflow/uploads/examples/vorange-example-v1.0.0.png'), 0, -1)
        ],
        [
            'id' => 'sysblue',
            'title' => 'SYSBlue Template',
            'thumbnail' => substr(module_dir_url('styleflow/uploads/examples/sysblue-example-v1.0.0.png'), 0, -1)
        ],
        [
            'id' => 'sysred',
            'title' => 'SYSRed Template',
            'thumbnail' => substr(module_dir_url('styleflow/uploads/examples/sysred-example-v1.0.0.png'), 0, -1)
        ],
        [
            'id' => 'sysyellow',
            'title' => 'SYSYellow Template',
            'thumbnail' => substr(module_dir_url('styleflow/uploads/examples/sysyellow-example-v1.0.0.png'), 0, -1)
        ],
        [
            'id' => 'sysgreen',
            'title' => 'SYSGreen Template',
            'thumbnail' => substr(module_dir_url('styleflow/uploads/examples/sysgreen-example-v1.0.0.png'), 0, -1)
        ],
        [
            'id' => 'sysorange',
            'title' => 'SYSOrange Template',
            'thumbnail' => substr(module_dir_url('styleflow/uploads/examples/sysorange-example-v1.0.0.png'), 0, -1)
        ],
        [
            'id' => 'xblue',
            'title' => 'XBlue Template',
            'thumbnail' => substr(module_dir_url('styleflow/uploads/examples/xblue-example-v1.0.0.png'), 0, -1)
        ],
        [
            'id' => 'xgreen',
            'title' => 'XGreen Template',
            'thumbnail' => substr(module_dir_url('styleflow/uploads/examples/xgreen-example-v1.0.0.png'), 0, -1)
        ],
        [
            'id' => 'xorange',
            'title' => 'XOrange Template',
            'thumbnail' => substr(module_dir_url('styleflow/uploads/examples/xorange-example-v1.0.0.png'), 0, -1)
        ],
        [
            'id' => 'xred',
            'title' => 'XRed Template',
            'thumbnail' => substr(module_dir_url('styleflow/uploads/examples/xred-example-v1.0.0.png'), 0, -1)
        ],
        [
            'id' => 'xyellow',
            'title' => 'XYellow Template',
            'thumbnail' => substr(module_dir_url('styleflow/uploads/examples/xyellow-example-v1.0.0.png'), 0, -1)
        ]
    ];
}