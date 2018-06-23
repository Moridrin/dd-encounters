<?php

use dd_encounters\models\Creature;
use dd_encounters\models\Player;
use mp_general\base\BaseFunctions;
use mp_general\base\SSV_Global;
use mp_general\exceptions\NotFoundException;

if (!defined('ABSPATH')) {
    exit;
}

function mp_dd_encounters_save_player()
{
    $id    = BaseFunctions::sanitize($_POST['id'], 'int');
    $name  = BaseFunctions::sanitize($_POST['name'], 'text');
    $level = BaseFunctions::sanitize($_POST['level'], 'int');
    $hp    = BaseFunctions::sanitize($_POST['hp'], 'int');
    if ($id === null) {
        $id = Player::create($name, $level, $hp, 0);
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

function mp_dd_encounters_save_creature()
{
    $id                 = BaseFunctions::sanitize($_POST['id'], 'int');
    $name               = BaseFunctions::sanitize($_POST['name'], 'text');
    $hp                 = BaseFunctions::sanitize($_POST['hp'], 'text');
    $initiativeModifier = BaseFunctions::sanitize($_POST['initiativeModifier'], 'int');
    $url                = BaseFunctions::sanitize($_POST['url'], 'text');
    if ($id === null) {
        $id = Creature::create($name, $hp, $initiativeModifier, $url);
        if ($id === null) {
            wp_die();
        }
    } else {
        try {
            $creature = Creature::findById($id);
            $creature
                ->setName($name)
                ->setHp($hp)
                ->setInitiativeModifier($initiativeModifier)
                ->setUrl($url)
                ->save()
            ;
        } catch (NotFoundException $e) {
            SSV_Global::addError('Creature with ID "' . $id . '" not found.');
            wp_die();
        }
        wp_die(json_encode(['success' => true, 'id' => $id]));
    }
}

add_action('wp_ajax_mp_dd_encounters_save_creature', 'mp_dd_encounters_save_creature', 10, 0);

function mp_dd_encounters_delete_creature()
{
    $id = BaseFunctions::sanitize($_POST['id'], 'int');
    Creature::deleteByIds([$id]);
    wp_die(json_encode(['success' => true]));
}

add_action('wp_ajax_mp_dd_encounters_delete_creature', 'mp_dd_encounters_delete_creature');
