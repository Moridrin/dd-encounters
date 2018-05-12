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
        'supports'            => ['title', 'editor', 'author', 'thumbnail', 'trackbacks', 'custom-fields', 'comments', 'revisions', 'page-attributes'],
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
        'encounters',
        [
            'label'        => 'Creatures',
            'query_var'    => true,
            'rewrite'      => [
                'slug'       => 'encounter_creatures',
                'with_front' => false,
            ],
        ]
    );
}

add_action('init', 'mp_dd_encounters_category_taxonomy');

// function presenters_taxonomy_custom_fields($tag)
// {
//     \mp_general\base\BaseFunctions::var_export('test', true);
//     // Check for existing taxonomy meta for the term you're editing
//     $t_id      = $tag->term_id; // Get the ID of the term you're editing
//     $term_meta = get_option("taxonomy_term_$t_id"); // Do the check
//
//     ?>
<!---->
<!--    <tr class="form-field">-->
<!--        <th scope="row" valign="top">-->
<!--            <label for="presenter_id">--><?php //_e('WordPress User ID'); ?><!--</label>-->
<!--        </th>-->
<!--        <td>-->
<!--            <input type="text" name="term_meta[presenter_id]" id="term_meta[presenter_id]" size="25" style="width:60%;" value="--><?php //echo $term_meta['presenter_id'] ? $term_meta['presenter_id'] : ''; ?><!--"><br/>-->
<!--            <span class="description">--><?php //_e('The Presenter\'s WordPress User ID'); ?><!--</span>-->
<!--        </td>-->
<!--    </tr>-->
<!--    --><?php
// }
// add_action('encounter_creatures_edit_form_fields', 'presenters_taxonomy_custom_fields', 10, 2);
//
// function save_taxonomy_custom_fields( $term_id ) {
//     if ( isset( $_POST['term_meta'] ) ) {
//         $t_id = $term_id;
//         $term_meta = get_option( "taxonomy_term_$t_id" );
//         $cat_keys = array_keys( $_POST['term_meta'] );
//         foreach ( $cat_keys as $key ){
//             if ( isset( $_POST['term_meta'][$key] ) ){
//                 $term_meta[$key] = $_POST['term_meta'][$key];
//             }
//         }
//         //save the option array
//         update_option( "taxonomy_term_$t_id", $term_meta );
//     }
// }
// add_action('edited_presenters', 'save_taxonomy_custom_fields', 10, 2);


add_action('encounter_creatures_edit_form_fields','category_edit_form_fields');
add_action('encounter_creatures_edit_form', 'category_edit_form');
add_action('encounter_creatures_add_form_fields','category_edit_form_fields');
add_action('encounter_creatures_add_form','category_edit_form');


function category_edit_form() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function(){
            jQuery('#edittag').attr( "enctype", "multipart/form-data" ).attr( "encoding", "multipart/form-data" );
        });
    </script>
    <?php
}

function category_edit_form_fields () {
    ?>
    <tr class="form-field">
        <th valign="top" scope="row">
            <label for="catpic"><?php _e('Picture of the category', ''); ?></label>
        </th>
        <td>
            <input type="file" id="catpic" name="catpic"/>
        </td>
    </tr>
    <?php
}

/**
 * This method adds the custom Meta Boxes
 */
function mp_dd_encounters_meta_boxes()
{
    // add_meta_box('dd_encounter_creatures', 'Creatures', 'dd_encounter_creatures', 'encounters', 'side', 'default');
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
            <input type="text" name="new_encounter_creature_name" id="new_encounter_creature" placeholder="Creature Name">
            <input type="number" name="new_encounter_creature_level" id="new_encounter_creature" placeholder="Level" style="width: 50%; margin: 0 -1px 1em;">
            <input type="number" name="new_encounter_creature_hp" id="new_encounter_creature" placeholder="HP" style="width: 50%; margin: 0 -1px 1em;">
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
        update_post_meta($post_id, 'registration', SSV_General::sanitize($_POST['registration'], ['disabled', 'members_only', 'everyone']));
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
