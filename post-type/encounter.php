<?php

use dd_encounters\models\Player;
use mp_general\base\BaseFunctions;

if (!defined('ABSPATH')) {
    exit;
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
    register_taxonomy(
        'encounter_creatures',
        'encounter',
        [
            'label'     => 'Creatures',
            'query_var' => true,
            'rewrite'   => [
                'slug'       => 'encounter_creatures',
                'with_front' => false,
            ],
        ]
    );
}

add_action('init', 'mp_dd_encounters_category_taxonomy');

function category_edit_form_fields(WP_Term $term)
{
    \mp_general\base\BaseFunctions::var_export($term);
    ?>
    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="level">CR</label>
        </th>
        <td>
            <input type="number" id="level" name="level"/>
        </td>
    </tr>
    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="hp">HP</label>
        </th>
        <td>
            <input type="number" id="hp" name="hp" value="<?= get_option('encounter_creatures_' . $termId . '_level') ?>"/>
        </td>
    </tr>
    <?php
}

// add_action('encounter_creatures_edit_form_fields','category_edit_form_fields');
// add_action('encounter_creatures_add_form_fields', 'category_edit_form_fields');

// function save_taxonomy_custom_meta($termId)
// {
//     if ($_POST['taxonomy'] !== 'encounter_creatures') {
//         return;
//     }
//     $term = get_term($termId);
//     $term->description = json_encode(
//         [
//             'level' => BaseFunctions::sanitize($_POST['level'], 'int'),
//             'hp' => BaseFunctions::sanitize($_POST['hp'], 'int'),
//         ]
//     );
//     wp_update_term($termId, 'encounter_creatures', $term->to_array());
// }
//
// add_action('edited_encounter_creatures', 'save_taxonomy_custom_meta');
// add_action('create_encounter_creatures', 'save_taxonomy_custom_meta');

/**
 * This method adds the custom Meta Boxes
 */
function mp_dd_encounters_meta_boxes()
{
    add_meta_box('dd_encounter_creatures', 'Creatures', 'dd_encounter_creatures', 'encounter', 'side', 'default');
}

add_action('add_meta_boxes', 'mp_dd_encounters_meta_boxes');

function dd_encounter_creatures()
{
    global $post;
    ?>
    <ul id="fieldsList">
        <?php foreach (Player::getAll() as $player): ?>
            <li>
                <span><?= BaseFunctions::escape($player->getName(), 'html') ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
    <div id="encounter_creature-adder" class="wp-hidden-children">
        <a href="javascript:void(0)" onclick="encounterEditor.show()" style="font-weight: 600;">+ Add Creature</a>
        <p id="creatureAddForm" class="category-add" style="display: none;">
            <label for="new_encounter_creature"></label>
            <input type="text" name="new_encounter_creature_name" id="new_encounter_creature"
                   placeholder="Creature Name">
            <input type="number" name="new_encounter_creature_level" id="new_encounter_creature" placeholder="Level"
                   style="width: 50%; margin: 0 -1px 1em;">
            <input type="number" name="new_encounter_creature_hp" id="new_encounter_creature" placeholder="HP"
                   style="width: 50%; margin: 0 -1px 1em;">
            <input type="button" id="addCreatureButton" class="button category-add-submit" value="Add New Creature">
        </p>
    </div>
    <?php
}

function mp_dd_encounters_save_meta($post_id): int
{
    if (!current_user_can('edit_post', $post_id)) {
        return $post_id;
    }
    if (isset($_POST['registration'])) {
        update_post_meta($post_id, 'registration',
                         SSV_General::sanitize($_POST['registration'], ['disabled', 'members_only', 'everyone']));
    }
    if (isset($_POST['start'])) {
        update_post_meta($post_id, 'start', SSV_General::sanitize($_POST['start'], 'datetime'));
    }
    if (isset($_POST['end'])) {
        update_post_meta($post_id, 'end', SSV_General::sanitize($_POST['end'], 'datetime'));
    }
    if (isset($_POST['location'])) {
        update_post_meta($post_id, 'location', SSV_General::sanitize($_POST['location'], 'text'));
    }

//    Form::saveEditorFromPost();
    return $post_id;
}

add_action('save_post_encounters', 'mp_dd_encounters_save_meta');
