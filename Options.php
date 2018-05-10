<?php

namespace dd_encounters;

use dd_encounters\models\Player;
use mp_general\base\BaseFunctions;
use mp_general\forms\models\SharedField;

if (!defined('ABSPATH')) {
    exit;
}

abstract class Options
{
    public static function setupNetworkMenu()
    {
        add_menu_page('Players', 'Players', 'edit_players', 'dd_encounters', [self::class, 'showPlayersList'], 'dashicons-feedback');
    }

    public static function setupSiteSpecificMenu()
    {
        // add_menu_page('Players', 'Players', 'edit_players', 'dd_encounters', '', 'dashicons-feedback');
        // add_submenu_page('dd_encounters', 'All Forms', 'All Forms', 'edit_posts', 'ssv_forms', [self::class, 'showFormsPage']);
        // add_submenu_page('dd_encounters', 'Add New', 'Add New', 'edit_posts', 'ssv_forms_add_new_form', [self::class, 'showFormPageEdit']);
        // add_submenu_page('dd_encounters', 'Manage Fields', 'Manage Fields', 'edit_posts', 'ssv_forms_fields_manager', [self::class, 'showCombinedFieldsPage']);
    }

    public static function showPlayersList()
    {
        if (BaseFunctions::isValidPOST(null)) {
            if ($_POST['action'] === 'delete-selected' && !isset($_POST['_inline_edit'])) {
                SharedField::deleteByIds(BaseFunctions::sanitize($_POST['ids'], 'int'));
            } else {
                $_SESSION['SSV']['errors'][] = 'Unknown action.';
            }
        }
        $orderBy = BaseFunctions::sanitize(isset($_GET['orderby']) ? $_GET['orderby'] : 'f_name', 'text');
        $order   = BaseFunctions::sanitize(isset($_GET['order']) ? $_GET['order'] : 'asc', 'text');
        $addNew  = '<a href="javascript:void(0)" class="page-title-action" onclick="fieldsManager.addNew(\'the-list\', \'\')">Add New</a>';
        ?>
        <h1 class="wp-heading-inline"><span>Shared Form Fields</span><?= current_user_can('manage_shared_base_fields') ? $addNew : '' ?></h1>
        <p>These fields will be available for all sites.</p>
        <?php
        mp_ssv_show_table(Player::class, $orderBy, $order, current_user_can('manage_shared_base_fields'));
    }
}

add_action('network_admin_menu', [Options::class, 'setupNetworkMenu']);
add_action('admin_menu', [Options::class, 'setupSiteSpecificMenu']);
