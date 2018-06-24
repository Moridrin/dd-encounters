<?php

use dd_encounters\models\CombatAction;
use dd_encounters\models\Monster;
use dd_encounters\models\Player;
use mp_general\base\BaseFunctions;
use mp_general\base\SSV_Global;
use mp_general\exceptions\NotFoundException;

if (!defined('ABSPATH')) {
    exit;
}


function mp_dd_encounters_delete_log_entry()
{
    $id         = BaseFunctions::sanitize($_POST['id'], 'int');
    CombatAction::deleteByIds([$id]);
    wp_die(json_encode(['id' => $id]));
}

add_action('wp_ajax_mp_dd_encounters_delete_log_entry', 'mp_dd_encounters_delete_log_entry', 10, 0);

function mp_dd_encounters_save_player()
{
    $id         = BaseFunctions::sanitize($_POST['id'], 'int');
    $name       = BaseFunctions::sanitize($_POST['name'], 'text');
    $level      = BaseFunctions::sanitize($_POST['level'], 'int');
    $hp         = BaseFunctions::sanitize($_POST['hp'], 'int');
    $postId     = BaseFunctions::sanitize($_POST['postId'] ?? null, 'int');
    $initiative = BaseFunctions::sanitize($_POST['initiative'] ?? null, 'int');
    $currentHp  = BaseFunctions::sanitize($_POST['currentHp'] ?? null, 'int');
    if ($id === null) {
        $id = Player::create($name, $level, $hp, $postId, $initiative, $currentHp);
        if ($id === null) {
            wp_die();
        }
    }
    try {
        $player = Player::findById($id);
        $player
            ->setName($name)
            ->setLevel($level)
            ->setHp($hp)
            ->setPostId($postId)
            ->setInitiative($initiative)
            ->setCurrentHp($currentHp)
            ->save()
        ;
    } catch (NotFoundException $e) {
        SSV_Global::addError('Player with ID "' . $id . '" not found.');
        wp_die();
    }
    wp_die(json_encode(['id' => $id]));
}

add_action('wp_ajax_mp_dd_encounters_save_player', 'mp_dd_encounters_save_player', 10, 0);

function mp_dd_encounters_delete_player()
{
    $id = BaseFunctions::sanitize($_POST['id'], 'int');
    Player::deleteByIds([$id]);
    wp_die(json_encode(['success' => true]));
}

add_action('wp_ajax_mp_dd_encounters_delete_player', 'mp_dd_encounters_delete_player');

function mp_dd_encounters_save_monster()
{
    $id                 = BaseFunctions::sanitize($_POST['id'], 'int');
    $name               = BaseFunctions::sanitize($_POST['name'], 'text');
    $hp                 = BaseFunctions::sanitize($_POST['hp'], 'text');
    $initiativeModifier = BaseFunctions::sanitize($_POST['initiativeModifier'], 'int');
    $url                = BaseFunctions::sanitize($_POST['url'], 'text');
    if ($id === null) {
        $id = Monster::create($name, $hp, $initiativeModifier, $url);
        if ($id === null) {
            wp_die();
        }
    } else {
        try {
            $monster = Monster::findById($id);
            $monster
                ->setName($name)
                ->setHp($hp)
                ->setInitiativeModifier($initiativeModifier)
                ->setUrl($url)
                ->save()
            ;
        } catch (NotFoundException $e) {
            SSV_Global::addError('Monster with ID "' . $id . '" not found.');
            wp_die();
        }
        wp_die(json_encode(['success' => true, 'id' => $id]));
    }
}

add_action('wp_ajax_mp_dd_encounters_save_monster', 'mp_dd_encounters_save_monster', 10, 0);

function mp_dd_encounters_delete_monster()
{
    $id = BaseFunctions::sanitize($_POST['id'], 'int');
    Monster::deleteByIds([$id]);
    wp_die(json_encode(['success' => true]));
}

add_action('wp_ajax_mp_dd_encounters_delete_monster', 'mp_dd_encounters_delete_monster');
