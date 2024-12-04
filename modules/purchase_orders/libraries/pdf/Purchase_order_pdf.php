<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(LIBSPATH . 'pdf/App_pdf.php');

class Purchase_order_pdf extends App_pdf
{
    protected $purchase_order;

    private $purchase_order_number;

    public function __construct($purchase_order, $tag = '')
    {
        $this->load_language($purchase_order->clientid);

        $purchase_order                = hooks()->apply_filters('purchase_order_html_pdf_data', $purchase_order);
        $GLOBALS['purchase_order_pdf'] = $purchase_order;

        parent::__construct();

        $this->tag             = $tag;
        $this->purchase_order        = $purchase_order;
        $this->purchase_order_number = format_purchase_order_number($this->purchase_order->id);

        $this->SetTitle($this->purchase_order_number);
    }

    public function prepare()
    {
        $this->with_number_to_word($this->purchase_order->clientid);

        $this->set_view_vars([
            'status'          => $this->purchase_order->status,
            'purchase_order_number' => $this->purchase_order_number,
            'purchase_order'        => $this->purchase_order,
        ]);

        return $this->build();
    }

    protected function type()
    {
        return 'purchase_order';
    }

    protected function file_path()
    {
        return module_dir_path(PURCHASE_ORDER_MODULE_NAME, 'views/pdf/purchase_order_pdf.php');
    }

    public function getCompanySignature()
    {
        if (get_option('show_pdf_signature_purchase_order') == 1) {
            $signatureImage = get_option('signature_image');

            $signaturePath   = get_upload_path_by_type('company') . $signatureImage;
            $signatureExists = file_exists($signaturePath);

            $blankSignatureLine = hooks()->apply_filters('blank_signature_line', '_________________________');

            if ($signatureImage != '' && $signatureExists) {
                $blankSignatureLine = '';
            }

            $this->ln(13);

            if ($signatureImage != '' && $signatureExists) {
                $imageData = base64_encode(file_get_contents($signaturePath));
                $blankSignatureLine .= str_repeat('<br />', hooks()->apply_filters('pdf_signature_break_lines', 1)) . '<img src="@' . $imageData . '" / />';
            }

            return $blankSignatureLine;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function process_signature()
    {
        $dimensions       = $this->getPageDimensions();
        $companySignature = $this->getCompanySignature();

        if ($companySignature) {
            $this->MultiCell(($dimensions['wk'] / 2) - $dimensions['lm'], 0, _l('authorized_signature_text') . ' ' . $companySignature, 0, 'J', 0, 0, '', '', true, 0, true, true, 0);
        }
    }
}
