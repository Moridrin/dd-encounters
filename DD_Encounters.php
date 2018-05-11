<?php

namespace dd_encounters;

use dd_encounters\models\Player;
use mp_general\base\BaseFunctions;
use mp_general\base\SSV_Global;

if (!defined('ABSPATH')) {
    exit;
}

abstract class DD_Encounters
{
    const PATH = DD_ENCOUNTERS_PATH;
    const URL  = DD_ENCOUNTERS_URL;

    /**
     * @param $network_wide
     * @throws \Exception
     */
    public static function setup($network_wide = false)
    {
        if (is_multisite() && $network_wide) {
            SSV_Global::runFunctionOnAllSites([self::class, 'setupForBlog']);
        } else {
            self::setupForBlog();
        }
    }

    /**
     * @param $network_wide
     * @throws \Exception
     */
    public static function deactivate($network_wide = false)
    {
        if (is_multisite() && $network_wide) {
            SSV_Global::runFunctionOnAllSites([self::class, 'deactivate']);
        } else {
            self::cleanupBlog();
        }
    }

    /**
     * @param int|null $blogId
     * @throws \Exception
     */
    public static function setupForBlog(int $blogId = null)
    {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        global $wpdb;
        $wpdb->query(Player::getDatabaseCreateQuery($blogId));
        if ($wpdb->last_error) {
            throw new \Exception($wpdb->last_error);
        }
    }

    /**
     * @param int|null $blogId
     * @throws \Exception
     */
    public static function cleanupBlog(int $blogId = null)
    {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $tableName = Player::getDatabaseTableName($blogId);
        $wpdb->query("DROP TABLE $tableName;");
    }

    public static function filterContent($content): string
    {
        if (BaseFunctions::isValidPOST(null)) {
            BaseFunctions::var_export($_POST, 1);
        } else {
            ob_start()
            ?>
            <form method="post">
                <div class="row">
                    <div class="col s3">
                        <label>
                            Actor
                            <select>
                                <option value="sam"></option>
                            </select>
                        </label>
                    </div>
                    <div class="col s3">
                        <label>
                            Target
                            <input type="text" name="target">
                        </label>
                    </div>
                    <div class="col s3">
                        <label>
                            Action
                            <input type="text" name="action">
                        </label>
                    </div>
                    <div class="col s3">
                        <label>
                            Damage
                            <input type="text" name="damage">
                        </label>
                    </div>
                </div>
            </form>
            <?php
            return ob_get_clean();
        }
        return $content . 'test';
    }

    public static function enqueueAdminScripts()
    {
        $page = $_GET['page'] ?? null;
        switch ($page) {
            case 'dd_encounters':
                self::enquirePlayerManagerScripts();
                break;
        }
        global $post_type;
        if ($post_type === 'encounters') {
            self::enquireEncounterEditorScripts();
        }
    }

    public static function enquirePlayerManagerScripts()
    {
        wp_enqueue_script('mp-dd-player-manager', self::URL . '/js/player-manager.js', ['jquery']);
        wp_localize_script('mp-dd-player-manager', 'mp_ssv_player_manager_params', [
            'urls'       => [
                'plugins'  => plugins_url(),
                'ajax'     => admin_url('admin-ajax.php'),
                'base'     => get_home_url(),
                'basePath' => ABSPATH,
            ],
            'actions'    => [
                'save'   => 'mp_dd_encounters_save_player',
                'delete' => 'mp_dd_encounters_delete_player',
            ],
        ]);
    }

    public static function enquireEncounterEditorScripts()
    {
        wp_enqueue_script('mp-dd-encounter-editor', self::URL . '/js/encounter-editor.js', ['jquery']);
        wp_localize_script('mp-dd-encounter-editor', 'mp_ssv_encounter_editor_params', [
            'urls'    => [
                'plugins'  => plugins_url(),
                'ajax'     => admin_url('admin-ajax.php'),
                'base'     => get_home_url(),
                'basePath' => ABSPATH,
            ],
            'actions' => [
                'save'   => 'mp_dd_encounters_add_creature',
                'delete' => 'mp_dd_encounters_remove_creature',
            ],
        ]);
    }
}

add_filter('the_content', [DD_Encounters::class, 'filterContent']);
register_activation_hook(__DIR__ . DIRECTORY_SEPARATOR . 'dd-encounters.php', [DD_Encounters::class, 'setup']);
register_deactivation_hook(__DIR__ . DIRECTORY_SEPARATOR . 'dd-encounters.php', [DD_Encounters::class, 'deactivate']);
add_action('admin_enqueue_scripts', [DD_Encounters::class, 'enqueueAdminScripts']);
