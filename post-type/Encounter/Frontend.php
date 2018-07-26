<?php

namespace dd_encounters\PostType\Encounter;

use dd_encounters\DD_Encounters;
use dd_encounters\models\CombatAction;
use dd_encounters\models\CombatMonster;
use dd_encounters\models\Creature;
use dd_encounters\models\Monster;
use dd_encounters\models\Player;
use /** @noinspection PhpUndefinedClassInspection */
    dd_encounters\PostType\Encounter\Templates\ActionLog;
use /** @noinspection PhpUndefinedClassInspection */
    dd_encounters\PostType\Encounter\Templates\EncounterForm;
use /** @noinspection PhpUndefinedClassInspection */
    dd_encounters\PostType\Encounter\Templates\EncounterSetup;
use Exception;
use mp_general\base\BaseFunctions;

if (!defined('ABSPATH')) {
    exit;
}

abstract class Frontend
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
                    self::processEncounterSetup($post->ID);
                    break;
                case 'saveCombatAction':
                    self::processEncounterForm($post->ID);
                    break;
            }
            BaseFunctions::redirect();
            return '<h1>Processing...</h1>';
        }

        $players        = Player::findByIds(get_post_meta($post->ID, 'activePlayers', true), 'p_initiative', 'DESC');
        $combatMonsters = CombatMonster::findByEncounterId($post->ID);
        $startSetup     = empty($combatMonsters);
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
            return self::getEncounterSetup($post->ID, $content);
        } else {
            return self::getEncounterForm($post->ID, $content);
        }
    }

    private static function getMessageContainer(): string
    {
        return '<div id="messagesContainer" class="notice"></div>';
    }

    private static function getEncounterSetup(int $postId, string $content): string
    {
        /** @noinspection PhpIncludeInspection */
        require_once 'templates/'.DD_Encounters::getCurrentTheme().'/EncounterSetup.php';
        /** @noinspection PhpUndefinedClassInspection */

        $monsterCounts = get_post_meta($postId, 'monsters', true);
        $monsters      = Monster::findByIds(array_keys($monsterCounts));
        $players       = Player::findByIds(get_post_meta($postId, 'activePlayers', true), 'p_initiative', 'DESC');

        $html = self::getMessageContainer();
        /** @noinspection PhpUndefinedClassInspection */
        $html .= EncounterSetup::show($monsterCounts, $monsters, $players);
        $html .= $content;

        return $html;
    }

    /**
     * @param $postId
     *
     * @throws Exception
     */
    private static function processEncounterSetup($postId): void
    {
        if (!BaseFunctions::isValidPOST(null)) {
            throw new Exception('Not a valid Post. Not Processing.');
        }
        if ($_POST['action'] !== 'encounterSetup') {
            throw new Exception('Not a post request to process the setup for the encounter. Not Processing.');
        }
        foreach (Player::findByIds(get_post_meta($postId, 'activePlayers', true)) as $playerId => $player) {
            $player
                ->setInitiative(BaseFunctions::sanitize($_POST['p_initiative'][$playerId], 'int'))
                ->setCurrentHp(BaseFunctions::sanitize($_POST['p_currentHp'][$playerId], 'int'))
                ->save()
            ;
        }
        foreach (BaseFunctions::sanitize($_POST['name'], 'text') as $id => $name) {
            CombatMonster::create(
                $postId,
                explode('_', $id)[0],
                $name,
                BaseFunctions::sanitize($_POST['hp'][$id], 'int'),
                BaseFunctions::sanitize($_POST['currentHp'][$id], 'int'),
                BaseFunctions::sanitize($_POST['initiative'][$id], 'int')
            );
        }
    }

    private static function getEncounterForm(int $postId, string $content): string
    {
        $currentTheme = DD_Encounters::getCurrentTheme();
        /** @noinspection PhpIncludeInspection */
        require_once 'templates/' . $currentTheme . '/EncounterForm.php';
        /** @noinspection PhpIncludeInspection */
        require_once 'templates/' . $currentTheme . '/ActionLog.php';
        /** @noinspection PhpUndefinedClassInspection */

        $players           = Player::findByIds(get_post_meta($postId, 'activePlayers', true), 'p_initiative', 'DESC');
        $monsters          = CombatMonster::findByEncounterId($postId);
        $actions           = CombatAction::findByEncounterId($postId);
        $creatures         = array_merge($monsters, $players);
        $currentCreatureId = BaseFunctions::sanitize($_GET['activeCreature'] ?? key($creatures), 'int');
        $nextCreatureUrl   = BaseFunctions::getCurrentUrlWithArguments(['activeCreature' => ($currentCreatureId + 1) % count($creatures)]);
        $previousCombatActions = CombatAction::getAutocompleteByEncounterAmdActorId($postId, $currentCreatureId);
        usort(
            $creatures,
            function (Creature $a, Creature $b) {
                return $b->getInitiative() - $a->getInitiative();
            }
        );

        $html = self::getMessageContainer();
        /** @noinspection PhpUndefinedClassInspection */
        $html .= EncounterForm::show($currentCreatureId, $creatures, $previousCombatActions, $nextCreatureUrl);
        /** @noinspection PhpUndefinedClassInspection */
        $html .= ActionLog::show($actions, $creatures);
        $html .= $content;

        return $html;
    }

    /**
     * @param int $postId
     *
     * @throws Exception
     */
    public static function processEncounterForm(int $postId): void
    {
        if (!BaseFunctions::isValidPOST(null)) {
            throw new Exception('Not a valid Post. Not Processing.');
        }
        if ($_POST['action'] !== 'saveCombatAction') {
            throw new Exception('Not a post request to process the setup for the encounter. Not Processing.');
        }
        $actor             = BaseFunctions::sanitize($_POST['actor'], 'text');
        $affectedCreatures = BaseFunctions::sanitize($_POST['affectedCreatures'], 'int');
        $creatureAction    = BaseFunctions::sanitize($_POST['creatureAction'], 'text');
        $damage            = BaseFunctions::sanitize($_POST['damage'], 'int');
        $damage            = array_filter(
            $damage,
            function ($key) use ($affectedCreatures) {
                return in_array($key, $affectedCreatures);
            },
            ARRAY_FILTER_USE_KEY
        );
        $damage            = array_map(
            function ($value) {
                return (is_null($value)) ? 0 : $value;
            },
            $damage
        );

        CombatAction::create($postId, $actor, $affectedCreatures, $creatureAction, $damage);
    }
}

add_filter('the_content', [Frontend::class, 'filterContent'], 13);

