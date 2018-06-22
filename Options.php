<?php

namespace dd_encounters;

use dd_encounters\models\Creature;
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
        // add_menu_page('Creatures', 'Creatures', 'edit_creatures', 'dd_creatures', '', 'dashicons-feedback');
        add_submenu_page('edit.php?post_type=encounter', 'Creatures', 'Creatures', 'edit_creatures', 'dd_creatures', [self::class, 'showCreaturesList']);
        add_submenu_page('edit.php?post_type=encounter', 'Players', 'Players', 'edit_players', 'dd_players', [self::class, 'showPlayersList']);
        // remove_submenu_page('edit.php?post_type=encounter', 'edit-tags.php?taxonomy=encounter_creatures&amp;post_type=encounter');
    }

    public static function showPlayersList()
    {
        ?>
        <div class="wrap">
            <?php
            if (BaseFunctions::isValidPOST(null)) {
                if ($_POST['action'] === 'delete-selected' && !isset($_POST['_inline_edit'])) {
                    SharedField::deleteByIds(BaseFunctions::sanitize($_POST['ids'], 'int'));
                } else {
                    $_SESSION['SSV']['errors'][] = 'Unknown action.';
                }
            }
            $orderBy = BaseFunctions::sanitize(isset($_GET['orderby']) ? $_GET['orderby'] : 'f_name', 'text');
            $order   = BaseFunctions::sanitize(isset($_GET['order']) ? $_GET['order'] : 'asc', 'text');
            $addNew  = '<a href="javascript:void(0)" class="page-title-action" onclick="playerManager.addNew(\'the-list\', \'\')">Add New</a>';
            ?>
            <h1 class="wp-heading-inline"><span>Players</span><?= current_user_can('manage_shared_base_fields') ? $addNew : '' ?></h1>
            <?php mp_ssv_show_table(Player::class, $orderBy, $order, current_user_can('edit_creatures')); ?>
        </div>
        <?php
    }

    public static function showCreaturesList()
    {
        ?>
        <div class="wrap">
            <?php
            if (BaseFunctions::isValidPOST(null)) {
                if ($_POST['action'] === 'delete-selected' && !isset($_POST['_inline_edit'])) {
                    SharedField::deleteByIds(BaseFunctions::sanitize($_POST['ids'], 'int'));
                } else {
                    $_SESSION['SSV']['errors'][] = 'Unknown action.';
                }
            }
            $orderBy = BaseFunctions::sanitize(isset($_GET['orderby']) ? $_GET['orderby'] : 'f_name', 'text');
            $order   = BaseFunctions::sanitize(isset($_GET['order']) ? $_GET['order'] : 'asc', 'text');
            $addNew  = '<a href="javascript:void(0)" class="page-title-action" onclick="creatureManager.addNew(\'the-list\', \'\')">Add New</a>';
            ?>
            <h1 class="wp-heading-inline"><span>Creatures</span><?= current_user_can('manage_shared_base_fields') ? $addNew : '' ?></h1>
            <?php mp_ssv_show_table(Creature::class, $orderBy, $order, current_user_can('edit_players')); ?>
        </div>
        <?php
    }
}

add_action('network_admin_menu', [Options::class, 'setupNetworkMenu']);
add_action('admin_menu', [Options::class, 'setupSiteSpecificMenu']);
