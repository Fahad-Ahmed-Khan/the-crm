<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
    .theme-cards {
        margin: 10px 0 10px 0;
    }

    .theme-cards .card {
        height: 100%;
        border: 1px solid #e5e5e5;
        border-radius: 5px;
    }

    .theme-cards .img-wrapper {
        /*padding-top: 56.25%; !* 16:9 aspect ratio (height / width) *!*/
        position: relative;
        overflow: hidden;
    }

    .theme-cards .img-container {
        height: 200px; /* Set your desired fixed height here */
        overflow: hidden;
    }

    .theme-cards .img-wrapper img {
        width: 100%;
        height: auto;
        object-fit: contain;
        display: block;
        margin: 0 auto; /* Center the image horizontally */
    }


    .theme-cards .card-body {
        padding: 15px;
    }

    .theme-cards .card-title {
        font-size: 18px;
        margin-bottom: 10px;
    }
</style>
<div id="wrapper">
    <div class="content">
        <h2><?php echo _l('styleflow') . ' ' . _l('styleflow_invoice_templates'); ?></h2>
        <div class="row">
            <div class="panel_s">
                <div class="col-md-12">
                    <div class="panel-body panel-table-full">
                        <?php
                        $invoiceTemplates = styleflow_supported_invoice_templates();

                        for ($i = 0; $i < count($invoiceTemplates); $i++) {
                            if ($i % 4 === 0) {
                                echo '<div class="row mt-2 mb-2">';
                            }
                            ?>
                            <div class="col-md-3 theme-cards">
                                <div class="card">
                                    <div class="img-wrapper">
                                        <img class="card-img-top img-fluid" src="<?php echo $invoiceTemplates[$i]['thumbnail']; ?>"
                                             alt="Theme Image">
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo $invoiceTemplates[$i]['title']; ?></h5>
                                        <?php
                                        if (get_option('styleflow_selected_invoice_template') == $invoiceTemplates[$i]['id']) {
                                            ?>
                                            <a href="#"
                                               class="btn btn-success"><?php echo _l('styleflow_activated'); ?></a>
                                            <a target="_blank" href="<?php echo $invoiceTemplates[$i]['thumbnail']; ?>"
                                               class="btn btn-info">
                                                <i class="fa-regular fa-eye fa-lg"></i>
                                            </a>
                                            <?php
                                        } else {
                                            ?>
                                            <a href="<?php echo admin_url('styleflow/activate_invoice_template/' . $invoiceTemplates[$i]['id']) ?>"
                                               class="btn btn-primary"><?php echo _l('styleflow_activate'); ?></a>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <?php
                            if (($i + 1) % 4 === 0 || ($i + 1) === count($invoiceTemplates)) {
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>