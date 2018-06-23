<?php

namespace dd_encounters;

use dd_encounters\models\Creature;
use dd_encounters\models\Player;
use mp_general\base\BaseFunctions;
use mp_general\base\SSV_Global;

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
    }

    public static function showPlayersList()
    {
        ?>
        <div class="wrap">
            <?php
            if (BaseFunctions::isValidPOST(null)) {
                if ($_POST['action'] === 'delete-selected' && !isset($_POST['_inline_edit'])) {
                    Player::deleteByIds(BaseFunctions::sanitize($_POST['ids'], 'int'));
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
                if (!isset($_POST['action'])) {
                    SSV_Global::addError('No action provided.');
                } else {
                    $action      = BaseFunctions::sanitize($_POST['action'], 'text');
                    $postHandled = apply_filters('dd_encounters_creatures_list_post', $action);
                    if (!$postHandled) {
                        switch (BaseFunctions::sanitize($_POST['action'], 'text')) {
                            case 'delete-selected':
                                Creature::deleteByIds(BaseFunctions::sanitize($_POST['ids'], 'int'));
                                break;
                            case 'import':
                                $data            = array_map('str_getcsv', file($_FILES['import']['tmp_name']));
                                $keys            = array_shift($data);
                                $notAddedRecords = 0;
                                foreach ($data as $row) {
                                    $row = array_combine($keys, $row);
                                    if (empty($row['name']) || empty($row['hp'])) {
                                        ++$notAddedRecords;
                                        continue;
                                    }
                                    $name               = BaseFunctions::sanitize($row['name'], 'text');
                                    $hp                 = BaseFunctions::sanitize($row['hp'], 'text');
                                    $initiativeModifier = BaseFunctions::sanitize($row['init'] ?? $row['$initiativeModifier'] ?? 0, 'int');
                                    $url                = isset($row['url']) ? BaseFunctions::sanitize($row['url'], 'text') : '';
                                    if (Creature::findByName($name) !== null) {
                                        SSV_Global::addError('"' . $name . '" already exists.');
                                        continue;
                                    }
                                    $hp = str_replace([' + ', 'd'], ['+', 'D'], $hp);
                                    if (strpos($hp, '+') === false) {
                                        $hp .= '+0';
                                    }
                                    Creature::create($name, $hp, $initiativeModifier, $url);
                                }
                                if ($notAddedRecords > 0) {
                                    SSV_Global::addError($notAddedRecords . ' rows could not be added because they don\'t have a name/hp field.');
                                }
                                break;
                            default:
                                SSV_Global::addError('Unknown action.');
                                break;
                        }
                    }
                }
            }
            $orderBy = BaseFunctions::sanitize(isset($_GET['orderby']) ? $_GET['orderby'] : 'f_name', 'text');
            $order   = BaseFunctions::sanitize(isset($_GET['order']) ? $_GET['order'] : 'asc', 'text');
            $addNew  = '<a href="javascript:void(0)" class="page-title-action" onclick="creatureManager.addNew(\'the-list\', \'\')">Add New</a>';
            ?>
            <h1 class="wp-heading-inline"><span>Creatures</span><?= current_user_can('manage_shared_base_fields') ? $addNew : '' ?></h1>
            <?php mp_ssv_show_table(Creature::class, $orderBy, $order, current_user_can('edit_players')); ?>
            <h1>Import</h1>
            <h2>From CSV</h2>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import">
                <input type="file" name="import">
                <button type="submit">Import</button>
            </form>
            <?php do_action('dd_encounters_creatures_list') ?>
        </div>
        <?php
    }
}

add_action('network_admin_menu', [Options::class, 'setupNetworkMenu']);
add_action('admin_menu', [Options::class, 'setupSiteSpecificMenu']);
// DD_Encounters::CLEAN_INSTALL();
