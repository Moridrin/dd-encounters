<?php

namespace dd_encounters\PostType\Encounter;

use dd_encounters\DD_Encounters;
use dd_encounters\models\CombatAction;
use dd_encounters\models\CombatMonster;
use dd_encounters\models\Player;
use dd_encounters\PostType\Encounter\Templates\EncounterForm;
use dd_encounters\PostType\Encounter\Templates\EncounterSetup;
use Exception;
use mp_general\base\BaseFunctions;
use mp_general\base\SSV_Themes;

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
            /** @noinspection PhpIncludeInspection */
            require_once 'templates/'.DD_Encounters::getCurrentTheme().'/EncounterSetup.php';
            /** @noinspection PhpUndefinedClassInspection */
            return self::getMessageContainer() . EncounterSetup::show($post->ID, $content);
        } else {
            /** @noinspection PhpIncludeInspection */
            require_once 'templates/'.DD_Encounters::getCurrentTheme().'/EncounterForm.php';
            /** @noinspection PhpUndefinedClassInspection */
            return self::getMessageContainer().EncounterForm::show($post->ID, $content);
        }
    }

    private static function getMessageContainer(): string
    {
        return '<div id="messagesContainer" class="notice"></div>';
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
        $damage = array_map(
            function ($value) {
                return (is_null($value)) ? 0 : $value;
            },
            $damage
        );

        CombatAction::create($postId, $actor, $affectedCreatures, $creatureAction, $damage);
    }
}

add_filter('the_content', [Frontend::class, 'filterContent'], 13);

