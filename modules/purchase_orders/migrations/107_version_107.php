<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_107 extends App_module_migration
{
    public function up()
    {
        require(__DIR__ . '/../install.php');
    }

    public function down()
    {
    }
}