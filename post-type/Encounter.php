<?php

namespace dd_encounters\PostType;

use dd_encounters\models\CombatMonster;
use dd_encounters\models\Monster;
use dd_encounters\models\Player;
use dd_encounters\PostType\Templates\EncounterForm;
use dd_encounters\PostType\Templates\EncounterSetup;
use Exception;
use mp_general\base\BaseFunctions;

if (!defined('ABSPATH')) {
    exit;
}

abstract class Encounter
{
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

        if (BaseFunctions::isValidPOST(null)) {
            switch ($_POST['action']) {
                case 'encounterSetup':
                    require_once 'templates/standard/EncounterSetup.php';
                    EncounterSetup::process($post->ID);
                    break;
                case 'saveCombatAction':
                    require_once 'templates/standard/EncounterForm.php';
                    EncounterForm::process($post->ID);
                    break;
            }
            BaseFunctions::redirect();
            return '<h1>Processing...</h1>';
        }

        $players = Player::findByIds(get_post_meta($post->ID, 'activePlayers', true), 'p_initiative', 'DESC');
        $combatMonsters = CombatMonster::findByEncounterId($post->ID);
        $startSetup = empty($combatMonsters);
        if ($startSetup === false) {
            /** @var Player $player */
            foreach ($players as $player) {
                if ($player->getInitiative() === null) {
                    $startSetup = true;
                    break;
                }
            }
        }
        if ($startSetup) {
            require_once 'templates/standard/EncounterSetup.php';
            return EncounterSetup::show($post->ID, $content);
        } else {
            require_once 'templates/standard/EncounterForm.php';
            return EncounterForm::show($post->ID, $content);
        }
    }

    public static function setupPostType(): void
    {

        $labels = [
            'name'               => 'Encounters',
            'singular_name'      => 'Encounter',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Encounter',
            'edit_item'          => 'Edit Encounter',
            'new_item'           => 'New Encounter',
            'view_item'          => 'View Encounter',
            'search_items'       => 'Search Encounters',
            'not_found'          => 'No Encounters found',
            'not_found_in_trash' => 'No Encounters found in Trash',
            'parent_item_colon'  => 'Parent Encounter:',
            'menu_name'          => 'Encounters',
        ];

        $args = [
            'labels'              => $labels,
            'hierarchical'        => true,
            'description'         => 'Encounters filterable by category',
            'supports'            => [
                'title',
                'editor',
                'author',
                'thumbnail',
                'trackbacks',
                'custom-fields',
                'comments',
                'revisions',
                'page-attributes',
            ],
            'taxonomies'          => ['encounter_category', 'encounter_monsters'],
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-calendar-alt',
            'show_in_nav_menus'   => true,
            'publicly_queryable'  => true,
            'exclude_from_search' => false,
            'has_archive'         => true,
            'query_var'           => true,
            'can_export'          => true,
            'rewrite'             => true,
            'capability_type'     => 'post',
        ];

        register_post_type('encounter', $args);
    }

    public static function setupTaxonomy(): void
    {
        register_taxonomy(
            'encounter_category',
            'encounter',
            [
                'hierarchical' => true,
                'label'        => 'Encounter Categories',
                'query_var'    => true,
                'rewrite'      => [
                    'slug'       => 'encounter_category',
                    'with_front' => false,
                ],
            ]
        );
    }

    public static function addMetaBoxes(): void
    {
        add_meta_box('dd_encounter_players', 'Players', [Encounter::class, 'playersMetaBox'], 'encounter', 'side', 'default');
        add_meta_box('dd_encounter_monsters', 'Monsters', [Encounter::class, 'monstersMetaBox'], 'encounter', 'side', 'default');
    }

    public static function playersMetaBox(): void
    {
        global $post;
        $players          = Player::getAll();
        $activePlayersIds = get_post_meta($post->ID, 'activePlayers', true);
        if (!is_array($activePlayersIds)) {
            $activePlayersIds = array_column($players, 'id');
        }
        ?>
        <table width="100%">
            <tr>
                <th style="text-align: left;">Active</th>
                <th>Name</th>
            </tr>
            <?php foreach ($players as $player): ?>
                <tr>
                    <td>
                        <input
                                type="checkbox"
                                name="activePlayers[]"
                                value="<?= $player->getId() ?>"
                                title="<?= $player->getName() ?> is in this combat."
                            <?= checked(in_array($player->getId(), $activePlayersIds)) ?>
                        >
                    </td>
                    <td style="text-align: center;"><?= BaseFunctions::escape($player->getName(), 'html') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php
    }

    public static function monstersMetaBox(): void
    {
        global $post;
        $monsters       = Monster::getAll();
        $activeMonsters = get_post_meta($post->ID, 'monsters', true);
        if (!is_array($activeMonsters)) {
            $activeMonsters = [];
        }
        ?>
        <select id="monsterSelect" style="width: 100%;" title="Monster Selector">
            <option disabled selected="selected"></option>
            <?php foreach ($monsters as $monster): ?>
                <option value="<?= $monster->getId() ?>" <?= ($activeMonsters[$monster->getId()] ?? 0 > 0) ? 'disabled' : '' ?>><?= $monster->getName() ?></option>
            <?php endforeach; ?>
        </select>
        <script>
            jQuery(function ($) {
                $(document).ready(function () {
                    let monsterSelect = $('#monsterSelect');
                    monsterSelect.select2({
                        allowClear: true,
                        placeholder: "Add Monster",
                        tokenSeparators: [','],
                    });
                    monsterSelect.on('select2:select', function () {
                        $('#availableMonsters #monsterRow_' + $(this).val()).appendTo('#selectedMonsters');
                        let monsterCount = $('#monsterCount_' + $(this).val());
                        monsterCount.val(1);
                        monsterCount.prop('min', 1);
                        this.options[this.selectedIndex].disabled = true;
                        $(this).select2('destroy').select2();
                        $(this).val(null).trigger('change');
                    });
                    $('.removeMonster').on('click', function () {
                        $('#monsterRow_' + this.dataset.monsterId).appendTo('#availableMonsters');
                        let monsterCount = $('#monsterCount_' + this.dataset.monsterId);
                        monsterCount.prop('min', 0);
                        monsterCount.val(0);
                        $('#monsterSelect option[value="' + this.dataset.monsterId + '"]').prop('disabled', false);
                        monsterSelect.select2('destroy').select2();
                    });
                });
            });
        </script>
        <table id="availableMonsters" style="display: none;">
            <?php foreach ($monsters as $monster): ?>
                <?php if ((int)($activeMonsters[$monster->getId()] ?? 0) === 0): ?>
                    <tr id="monsterRow_<?= $monster->getId() ?>">
                        <td>
                            <input
                                    id="monsterCount_<?= $monster->getId() ?>"
                                    type="number"
                                    min="0"
                                    name="monsters[<?= $monster->getId() ?>]"
                                    value="0"
                                    style="width: 50px;"
                                    title="<?= $monster->getName() ?> is in this combat."
                                <?= checked(in_array($monster->getId(), $activeMonsters)) ?>
                            >
                        </td>
                        <td style="text-align: center;"><?= BaseFunctions::escape($monster->getName(), 'html') ?></td>
                        <td style="text-align: center;">
                            <button type="button" style="border-radius: 10px;" class="removeMonster" data-monster-id="<?= $monster->getId() ?>">X</button>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </table>
        <table id="selectedMonsters" width="100%">
            <tr>
                <th style="text-align: left;">Count</th>
                <th>Name</th>
                <th></th>
            </tr>
            <?php foreach ($monsters as $monster): ?>
                <?php if ($activeMonsters[$monster->getId()] ?? 0 > 0): ?>
                    <tr id="monsterRow_<?= $monster->getId() ?>">
                        <td>
                            <input
                                    id="monsterCount_<?= $monster->getId() ?>"
                                    type="number"
                                    min="1"
                                    name="monsters[<?= $monster->getId() ?>]"
                                    value="<?= $activeMonsters[$monster->getId()] ?>"
                                    style="width: 50px;"
                                    title="<?= $monster->getName() ?> is in this combat."
                                <?= checked(in_array($monster->getId(), $activeMonsters)) ?>
                            >
                        </td>
                        <td style="text-align: center;"><?= BaseFunctions::escape($monster->getName(), 'html') ?></td>
                        <td style="text-align: center;">
                            <button type="button" style="border-radius: 10px;" class="removeMonster" data-monster-id="<?= $monster->getId() ?>">X</button>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </table>
        <?php
    }

    public static function saveMetadata($postId): int
    {
        if (!current_user_can('edit_post', $postId)) {
            return $postId;
        }
        update_post_meta($postId, 'activePlayers', $_POST['activePlayers'] ?? []);
        update_post_meta($postId, 'monsters', array_filter($_POST['monsters'] ?? []));
        return $postId;
    }
}

add_filter('the_content', [Encounter::class, 'filterContent'], 13);
add_action('init', [Encounter::class, 'setupPostType']);
add_action('init', [Encounter::class, 'setupTaxonomy']);
add_action('add_meta_boxes', [Encounter::class, 'addMetaBoxes']);
add_action('save_post_encounter', [Encounter::class, 'saveMetadata']);

