<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<ul class="nav navbar-pills navbar-pills-flat nav-tabs nav-stacked mailbox_tab" role="tablist">
  <li class="">
      <a href="javascript:void(0);" onclick="loadMailboxCompose(this);">
           <i class="fa fa-pencil-square"></i> <?php echo _l('lm_mailbox_compose_li'); ?>
      </a>
    </li>
    <li class="<?php echo isset($table_page) && $table_page == 'Inbox' ? 'active' : (!isset($table_page) ? 'active' : '') ?>">
      <a href="javascript:void(0);" data-tab="<?php echo _l('lm_mailbox_inbox_li'); ?>" onclick="loadMailboxTable(this,'inbound','get');">
           <i class="fa fa-inbox"></i> <?php echo _l('lm_mailbox_inbox_li'); ?>
      </a>
    </li>
     <li class="<?php echo isset($table_page) && $table_page == 'Sent' ? 'active' : '' ?>">
      <a href="javascript:void(0);" data-tab="<?php echo _l('lm_mailbox_sent_li'); ?>" onclick="loadMailboxTable(this,'outbound','sending');">
           <i class="fa fa-envelope"></i> <?php echo _l('lm_mailbox_sent_li'); ?>
      </a>
    </li>
    <li class="<?php echo isset($table_page) && $table_page == 'Draft' ? 'active' : '' ?>">
      <a href="javascript:void(0);" data-tab="<?php echo _l('lm_mailbox_draft_li'); ?>" onclick="loadMailboxTable(this,'outbound','draft');">
            <i class="fa fa-file"></i> <?php echo _l('lm_mailbox_draft_li'); ?>
      </a>
    </li>
    <li class="<?php echo isset($table_page) && $table_page == 'Trash' ? 'active' : '' ?>">
      <a href="javascript:void(0);" data-tab="<?php echo _l('lm_mailbox_trash_li'); ?>" onclick="loadMailboxTable(this,'','trash');">
            <i class="fa fa-trash"></i> <?php echo _l('lm_mailbox_trash_li'); ?>
      </a>
    </li>
    <li>
      <a href="javascript:void(0);" data-tab="<?php echo _l('lm_mailbox_setting_li'); ?>" onclick="loadMailboxSetting(this);">
            <i class="fa fa-gear"></i> <?php echo _l('lm_mailbox_setting_li'); ?>
      </a>
    </li>
</ul>
