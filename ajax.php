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

class Ajax
{
    public static function filterContent(): void
    {
        $content = BaseFunctions::getParameter('content', 'html');
        $content = stripslashes($content);
        $content = do_shortcode($content);
        $content = wpautop($content);
        apply_filters('the_content', $content);
        wp_die(json_encode(['content' => $content]));
    }

    public static function deleteLogEntry(): void
    {
        $id = BaseFunctions::sanitize($_POST['id'], 'int');
        CombatAction::deleteByIds([$id]);
        wp_die(json_encode(['id' => $id]));
    }

    public static function savePlayer(): void
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

    public static function deletePlayer(): void
    {
        $id = BaseFunctions::sanitize($_POST['id'], 'int');
        Player::deleteByIds([$id]);
        wp_die(json_encode(['success' => true]));
    }

    public static function saveMonster(): void
    {
        $id                 = BaseFunctions::sanitize($_POST['id'], 'int');
        $name               = BaseFunctions::sanitize($_POST['name'], 'text');
        $hp                 = BaseFunctions::sanitize($_POST['hp'], 'text');
        $initiativeModifier = BaseFunctions::sanitize($_POST['initiativeModifier'], 'int');
        $url                = BaseFunctions::sanitize($_POST['url'], 'text');
        if ($initiativeModifier === null) {
            $initiativeModifier = 0;
        }
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

    public static function deleteMonster(): void
    {
        $id = BaseFunctions::sanitize($_POST['id'], 'int');
        Monster::deleteByIds([$id]);
        wp_die(json_encode(['success' => true]));
    }
}

add_action('wp_ajax_mp_dd_encounters_filterContent', [Ajax::class, 'filterContent']);
add_action('wp_ajax_mp_dd_encounters_delete_log_entry', [Ajax::class, 'deleteLogEntry']);
add_action('wp_ajax_mp_dd_encounters_save_player', [Ajax::class, 'savePlayer']);
add_action('wp_ajax_mp_dd_encounters_delete_player', [Ajax::class, 'deletePlayer']);
add_action('wp_ajax_mp_dd_encounters_save_monster', [Ajax::class, 'saveMonster']);
add_action('wp_ajax_mp_dd_encounters_delete_monster', [Ajax::class, 'deleteMonster']);
