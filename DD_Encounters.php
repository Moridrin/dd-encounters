<?php

namespace dd_encounters;

use dd_encounters\models\CombatAction;
use dd_encounters\models\CombatCreature;
use dd_encounters\models\Creature;
use dd_encounters\models\Player;
use Exception;
use mp_general\base\BaseFunctions;
use mp_general\base\SSV_Global;

if (!defined('ABSPATH')) {
    exit;
}

abstract class DD_Encounters
{
    const PATH = DD_ENCOUNTERS_PATH;
    const URL  = DD_ENCOUNTERS_URL;

    const CREATURE_CREATE_ADMIN_REFERER = 'dd_encounters__creature_create_admin_referer';

    /**
     * @param bool $networkWide
     *
     * @throws \Exception
     */
    public static function CLEAN_INSTALL(bool $networkWide = false): void
    {
        self::deactivate($networkWide);
        self::setup($networkWide);
    }

    /**
     * @param $network_wide
     *
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
     *
     * @throws \Exception
     */
    public static function deactivate($network_wide = false)
    {
        if (is_multisite() && $network_wide) {
            SSV_Global::runFunctionOnAllSites([self::class, 'cleanupBlog']);
        } else {
            self::cleanupBlog();
        }
    }

    /**
     * @param int|null $blogId
     *
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
        $wpdb->query(Creature::getDatabaseCreateQuery($blogId));
        if ($wpdb->last_error) {
            throw new \Exception($wpdb->last_error);
        }
        $wpdb->query(CombatAction::getDatabaseCreateQuery($blogId));
        if ($wpdb->last_error) {
            throw new \Exception($wpdb->last_error);
        }
    }

    /**
     * @param int|null $blogId
     *
     * @throws \Exception
     */
    public static function cleanupBlog(int $blogId = null)
    {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $tableName = Player::getDatabaseTableName($blogId);
        $wpdb->query("DROP TABLE $tableName;");
        $tableName = Creature::getDatabaseTableName($blogId);
        $wpdb->query("DROP TABLE $tableName;");
        $tableName = CombatAction::getDatabaseTableName($blogId);
        $wpdb->query("DROP TABLE $tableName;");
    }

    /**
     * @param string $content
     *
     * @return string
     * @throws Exception
     */
    public static function filterContent(string $content): string
    {
        global $post;
        if ($post->post_type !== 'encounter') {
            return $content;
        }


        $creatureCounts = get_post_meta($post->ID, 'creatures', true);
        $combatActions  = CombatAction::findByEncounterId($post->ID);
        if (empty($combatActions)) {
            return self::setupCreatures($creatureCounts);
        }
        exit;


        uasort(
            $creatureCounts, function ($creatureA, $creatureB) {
            return $creatureB['initiative'] - $creatureA['initiative'];
        }
        );

        if (!$setup && BaseFunctions::isValidPOST(null)) {
            CombatAction::create(
                $post->ID,
                BaseFunctions::sanitize($_POST['actor'], 'string'),
                BaseFunctions::sanitize($_POST['affectedCreatures'], 'string'),
                BaseFunctions::sanitize($_POST['action'], 'string'),
                BaseFunctions::sanitize($_POST['damage'], 'int')
            );
        }
        $found = CombatAction::findByEncounterId($post->ID);
        if (empty($found)) {
            // $setup = true;
        } else {
            foreach ($found as $combatAction) {
                foreach ($combatAction->getAffectedCreatures() as $affectedCreature) {
                    $creatureCounts[$affectedCreature]['current_hp'] = $creatureCounts[$affectedCreature]['current_hp'] - $combatAction->getDamage();
                    // if ($creatures[$affectedCreature]['current_hp'] > $creatures[$affectedCreature]['hp']) {
                    //     $creatures[$affectedCreature]['current_hp'] = $creatures[$affectedCreature]['hp'];
                    // }
                    if ($creatureCounts[$affectedCreature]['current_hp'] < 0) {
                        $creatureCounts[$affectedCreature]['current_hp'] = 0;
                    }
                }
            }
        }
        // if ($setup && BaseFunctions::isValidPOST(null)) {
        //     foreach ($creatureTerms as &$creatureTerm) {
        //         $description = json_encode(
        //                 [
        //                     'initiative' => BaseFunctions::sanitize($_POST['initiative'][$creatureTerm->slug], 'int'),
        //                     'hp' => BaseFunctions::sanitize($_POST['hp'][$creatureTerm->slug], 'int'),
        //                     'current_hp' => BaseFunctions::sanitize($_POST['current_hp'][$creatureTerm->slug], 'int'),
        //                 ]
        //         );
        //         wp_update_term($creatureTerm->id, 'encounter_creatures', ['description' => $description]);
        //     }
        //     $setup = false;
        // }
        if ($setup) {
        }
        $arguments       = BaseFunctions::sanitize($_GET, 'int');
        $currentCreature = ($arguments['creature'] ?? 1);
        $nextCreature    = $currentCreature + 1;
        if ($nextCreature > count($creatureCounts)) {
            $nextCreature = 1;
        }
        $arguments['creature'] = $nextCreature;
        $nextCreatureUrl       = explode('?', $_SERVER['REQUEST_URI'], 2)[0] . '?' . http_build_query($arguments);
        ob_start();
        ?>
        <form method="post" style="height: 500px;">
            <div class="row">
                <div class="col s3">
                    <label>
                        Actor
                        <select name="actor">
                            <?php
                            $i = 1;
                            foreach ($creatureCounts as $id => $creature) {
                                ?>
                                <option value="<?= BaseFunctions::escape($id, 'attr') ?>" <?= selected($currentCreature, $i) ?>><?= BaseFunctions::escape($creature['name'], 'html') ?> (<?= BaseFunctions::escape($creature['current_hp'], 'html') ?>)</option>
                                <?php
                                ++$i;
                            }
                            ?>
                        </select>
                    </label>
                </div>
                <div class="col s3">
                    <label>
                        Target
                        <select name="affectedCreatures[]" multiple="multiple">
                            <?php foreach ($creatureCounts as $id => $creature): ?>
                                <option value="<?= BaseFunctions::escape($id, 'attr') ?>"><?= BaseFunctions::escape($creature['name'], 'html') ?> (<?= BaseFunctions::escape($creature['current_hp'], 'html') ?>)</option>
                            <?php endforeach; ?>
                        </select>
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
                        <input type="number" name="damage">
                    </label>
                </div>
            </div>
            <button type="submit" class="btn primary" style="display: none;">Submit</button>
            <a href="<?= $nextCreatureUrl ?>" class="btn">Next Creature</a>
        </form>
        <?php
        return ob_get_clean();
    }

    public static function enqueueAdminScripts()
    {
        $page = $_GET['page'] ?? null;
        switch ($page) {
            case 'dd_creatures':
                self::enquireCreatureManagerScripts();
                break;
            case 'dd_players':
                self::enquirePlayerManagerScripts();
                break;
        }
        global $post_type;
        if ($post_type === 'encounter') {
            self::enquireEncounterEditorScripts();
        }
    }

    public static function enquireCreatureManagerScripts()
    {
        wp_enqueue_script('mp-dd-creature-manager', self::URL . '/js/creature-manager.js', ['jquery']);
        wp_localize_script(
            'mp-dd-creature-manager', 'mp_ssv_creature_manager_params', [
            'urls'    => [
                'plugins'  => plugins_url(),
                'ajax'     => admin_url('admin-ajax.php'),
                'base'     => get_home_url(),
                'basePath' => ABSPATH,
            ],
            'actions' => [
                'save'   => 'mp_dd_encounters_save_creature',
                'delete' => 'mp_dd_encounters_delete_creature',
            ],
                                    ]
        );
    }

    public static function enquirePlayerManagerScripts()
    {
        wp_enqueue_script('mp-dd-player-manager', self::URL . '/js/player-manager.js', ['jquery']);
        wp_localize_script(
            'mp-dd-player-manager', 'mp_ssv_player_manager_params', [
            'urls'    => [
                'plugins'  => plugins_url(),
                'ajax'     => admin_url('admin-ajax.php'),
                'base'     => get_home_url(),
                'basePath' => ABSPATH,
            ],
            'actions' => [
                'save'   => 'mp_dd_encounters_save_player',
                'delete' => 'mp_dd_encounters_delete_player',
            ],
                                  ]
        );
    }

    public static function enquireEncounterEditorScripts()
    {
        wp_enqueue_script('mp-dd-encounter-editor', self::URL . '/js/encounter-editor.js', ['jquery']);
        wp_localize_script(
            'mp-dd-encounter-editor', 'mp_ssv_encounter_editor_params', [
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
                                    ]
        );
    }

    private static function setupCreatures(array $creatureCounts): string
    {
        global $post;
        if (BaseFunctions::isValidPOST(null)) {
            foreach (BaseFunctions::sanitize($_POST['name'], 'text') as $creatureId => $names) {
                foreach ($names as $combatCreatureId => $name) {
                    CombatCreature::create(
                        $post->ID,
                        $creatureId,
                        $name,
                        BaseFunctions::sanitize($_POST['hp'][$creatureId][$combatCreatureId], 'int'),
                        BaseFunctions::sanitize($_POST['current_hp'][$creatureId][$combatCreatureId], 'int'),
                        BaseFunctions::sanitize($_POST['initiative'][$creatureId][$combatCreatureId], 'int')
                    );
                }
            }
        }

        $creatures = Creature::findByIds(array_keys($creatureCounts));
        ob_start();
        ?>
        <form method="post">
            <input type="hidden" name="action" value="createCreatures">
            <?php
            foreach ($creatureCounts as $creatureId => $creatureCount) {
                $creature = $creatures[$creatureId];
                for ($i = 1; $i <= $creatureCount; ++$i) {
                    $generatedHp = $creature->getGeneratedHp();
                    ?>
                    <div class="row">
                        <div class="col s3">
                            <label for="creature">Creature</label>
                            <input id="creature" type="text" name="name[<?= $creatureId ?>][<?= $i ?>]" value="<?= $creature->getName() ?>_<?= $i ?>">
                        </div>
                        <div class="col s3">
                            <label for="initiative">Initiative</label>
                            <input id="initiative" type="number" class="validate" min="<?= $creature->getMinInitiative() ?>" max="<?= $creature->getMaxInitiative() ?>" name="initiative[<?= $creatureId ?>][<?= $i ?>]" value="<?= $creature->getGeneratedInitiative() ?>">
                        </div>
                        <div class="col s3">
                            <label for="hp">HP</label>
                            <input id="hp" type="number" class="validate" min="<?= $creature->getMinHp() ?>" max="<?= $creature->getMaxHp() ?>" name="hp[<?= $creatureId ?>][<?= $i ?>]" value="<?= $generatedHp ?>">
                        </div>
                        <div class="col s3">
                            <label for="current_hp">Current HP</label>
                            <input id="current_hp" type="number" class="validate" min="0" name="current_hp[<?= $creatureId ?>][<?= $i ?>]" value="<?= $generatedHp ?>">
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
            <button type="submit" class="btn">Setup Players</button>
        </form>
        <?php
        return ob_get_clean();
    }
}

add_filter('the_content', [DD_Encounters::class, 'filterContent']);
register_activation_hook(__DIR__ . DIRECTORY_SEPARATOR . 'dd-encounters.php', [DD_Encounters::class, 'setup']);
register_deactivation_hook(__DIR__ . DIRECTORY_SEPARATOR . 'dd-encounters.php', [DD_Encounters::class, 'deactivate']);
add_action('admin_enqueue_scripts', [DD_Encounters::class, 'enqueueAdminScripts']);
