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

    $labels = array(
        'name'               => 'Encounters',
        'encounters',
        'singular_name'      => 'Encounter',
        'encounters',
        'add_new'            => 'Add New',
        'encounters',
        'add_new_item'       => 'Add New Encounter',
        'encounters',
        'edit_item'          => 'Edit Encounter',
        'encounters',
        'new_item'           => 'New Encounter',
        'encounters',
        'view_item'          => 'View Encounter',
        'encounters',
        'search_items'       => 'Search Encounters',
        'encounters',
        'not_found'          => 'No Encounters found',
        'encounters',
        'not_found_in_trash' => 'No Encounters found in Trash',
        'encounters',
        'parent_item_colon'  => 'Parent Encounter:',
        'encounters',
        'menu_name'          => 'Encounters',
        'encounters',
    );

    $args = array(
        'labels'              => $labels,
        'hierarchical'        => true,
        'description'         => 'Encounters filterable by category',
        'supports'            => array('title', 'editor', 'author', 'thumbnail', 'trackbacks', 'custom-fields', 'comments', 'revisions', 'page-attributes'),
        'taxonomies'          => array('encounter_category'),
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
    );

    register_post_type('encounters', $args);
}

add_action('init', 'mp_dd_encounters_post_category');

/**
 * This function registers a taxonomy for the categories.
 */
function mp_dd_encounters_category_taxonomy()
{
    register_taxonomy(
        'encounter_category',
        'encounters',
        array(
            'hierarchical' => true,
            'label'        => 'Encounter Categories',
            'query_var'    => true,
            'rewrite'      => array(
                'slug'       => 'encounter_category',
                'with_front' => false,
            ),
        )
    );
}

add_action('init', 'mp_dd_encounters_category_taxonomy');

/**
 * This method adds the custom Meta Boxes
 */
function mp_dd_encounters_meta_boxes()
{
    add_meta_box('dd_encounter_creatures', 'Creatures', 'dd_encounter_creatures', 'encounters', 'side', 'default');
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
            <input type="text" name="new_encounter_creature" id="new_encounter_creature" class="form-required" required>
            <input type="button" id="encounter_creature-add-submit"
                   data-wp-lists="add:encounter_creaturechecklist:encounter_creature-add"
                   class="button category-add-submit" value="Add New Creature">
            <span id="encounter_creature-ajax-response"></span>
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
        update_post_meta($post_id, 'registration', SSV_General::sanitize($_POST['registration'], array('disabled', 'members_only', 'everyone',)));
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
