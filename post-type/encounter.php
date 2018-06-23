<?php

use dd_encounters\models\Creature;
use dd_encounters\models\Player;
use mp_general\base\BaseFunctions;

if (!defined('ABSPATH')) {
    exit;
}


//Custom Autocomplete
add_action('wp_ajax_nopriv_get_listing_names', 'ajax_listings');
add_action('wp_ajax_get_listing_names', 'ajax_listings');

function ajax_listings()
{
    echo json_encode(['test' => 'Test', 'bla' => 'bly']);
    wp_die();
}


/**
 * This method initializes the post category functionality for Encounters
 */
function mp_dd_encounters_post_category()
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
        'taxonomies'          => ['encounter_category', 'encounter_creatures'],
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

add_action('init', 'mp_dd_encounters_post_category');

/**
 * This function registers a taxonomy for the categories.
 */
function mp_dd_encounters_category_taxonomy()
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
    // register_taxonomy(
    //     'encounter_creatures',
    //     'encounter',
    //     [
    //         'label'     => 'Creatures',
    //         'query_var' => true,
    //         'rewrite'   => [
    //             'slug'       => 'encounter_creatures',
    //             'with_front' => false,
    //         ],
    //     ]
    // );
}

add_action('init', 'mp_dd_encounters_category_taxonomy');

/**
 * This method adds the custom Meta Boxes
 */
function mp_dd_encounters_meta_boxes()
{
    add_meta_box('dd_encounter_players', 'Players', 'dd_encounter_players', 'encounter', 'side', 'default');
    add_meta_box('dd_encounter_creatures', 'Creatures', 'dd_encounter_creatures', 'encounter', 'side', 'default');
}

add_action('add_meta_boxes', 'mp_dd_encounters_meta_boxes');

function dd_encounter_players()
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

function dd_encounter_creatures()
{
    global $post;
    $creatures       = Creature::getAll();
    $activeCreatures = get_post_meta($post->ID, 'creatures', true);
    ?>
    <select id="monsterSelect" style="width: 100%;">
        <option disabled selected="selected"></option>
        <?php foreach ($creatures as $creature): ?>
            <option value="<?= $creature->getId() ?>" <?= ($activeCreatures[$creature->getId()] ?? 0 > 0) ? 'disabled' : '' ?>><?= $creature->getName() ?></option>
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
                    $('#availableCreatures #creatureRow_' + $(this).val()).appendTo('#selectedCreatures');
                    let creatureCount = $('#creatureCount_' + $(this).val());
                    creatureCount.val(1);
                    creatureCount.prop('min', 1);
                    this.options[this.selectedIndex].disabled = true;
                    $(this).select2('destroy').select2();
                    $(this).val(null).trigger('change');
                });
                $('.removeCreature').on('click', function () {
                    $('#creatureRow_' + this.dataset.creatureId).appendTo('#availableCreatures');
                    let creatureCount = $('#creatureCount_' + this.dataset.creatureId);
                    creatureCount.val(0);
                    creatureCount.prop('min', 0);
                    $('#monsterSelect option[value="' + this.dataset.creatureId + '"]').prop('disabled', false);
                    monsterSelect.select2('destroy').select2();
                });
            });
        });
    </script>
    <table id="availableCreatures" style="display: none;">
        <?php foreach ($creatures as $creature): ?>
            <?php if ((int)($activeCreatures[$creature->getId()] ?? 0) === 0): ?>
                <tr id="creatureRow_<?= $creature->getId() ?>">
                    <td>
                        <input
                                id="creatureCount_<?= $creature->getId() ?>"
                                type="number"
                                min="0"
                                name="creatures[<?= $creature->getId() ?>]"
                                value="0"
                                style="width: 50px;"
                                title="<?= $creature->getName() ?> is in this combat."
                            <?= checked(in_array($creature->getId(), $activeCreatures)) ?>
                        >
                    </td>
                    <td style="text-align: center;"><?= BaseFunctions::escape($creature->getName(), 'html') ?></td>
                    <td style="text-align: center;">
                        <button type="button" style="border-radius: 10px;" class="removeCreature" data-creature-id="<?= $creature->getId() ?>">X</button>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    </table>
    <table id="selectedCreatures" width="100%">
        <tr>
            <th style="text-align: left;">Count</th>
            <th>Name</th>
            <th></th>
        </tr>
        <?php foreach ($creatures as $creature): ?>
            <?php if ($activeCreatures[$creature->getId()] ?? 0 > 0): ?>
                <tr id="creatureRow_<?= $creature->getId() ?>">
                    <td>
                        <input
                                type="number"
                                min="1"
                                name="creatures[<?= $creature->getId() ?>]"
                                value="<?= $activeCreatures[$creature->getId()] ?>"
                                style="width: 50px;"
                                title="<?= $creature->getName() ?> is in this combat."
                            <?= checked(in_array($creature->getId(), $activeCreatures)) ?>
                        >
                    </td>
                    <td style="text-align: center;"><?= BaseFunctions::escape($creature->getName(), 'html') ?></td>
                    <td style="text-align: center;">
                        <button type="button" style="border-radius: 10px;" class="removeCreature" data-creature-id="<?= $creature->getId() ?>">X</button>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    </table>
    <?php
}

function mp_dd_encounter_save_meta($postId): int
{
    if (!current_user_can('edit_post', $postId)) {
        return $postId;
    }
    update_post_meta($postId, 'activePlayers', $_POST['activePlayers'] ?? []);
    update_post_meta($postId, 'creatures', array_filter($_POST['creatures']) ?? []);
    return $postId;
}

add_action('save_post_encounter', 'mp_dd_encounter_save_meta');
