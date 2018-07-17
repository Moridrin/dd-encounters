<?php

namespace dd_encounters\PostType\Templates\Standard;

use dd_encounters\models\Monster;
use dd_encounters\models\Player;
use \mp_general\base\BaseFunctions;

if (!defined('ABSPATH')) {
    exit;
}

abstract class EncounterSetup
{

    /**
     * @param int    $postId
     * @param string $content
     *
     * @return string
     */
    public static function show(int $postId, string $content): string
    {
        $monsterCounts = get_post_meta($postId, 'monsters', true);
        $monsters      = Monster::findByIds(array_keys($monsterCounts));
        $players       = Player::findByIds(get_post_meta($postId, 'activePlayers', true), 'p_initiative', 'DESC');
        $uniqueId      = 0;
        ob_start();
        ?>
        <form method="post">
            <input type="hidden" name="action" value="encounterSetup">
            <?php
            foreach ($players as $playerId => $player) {
                ?>
                <div class="row">
                    <div class="col s3">
                        <?php if ($player->getPostId() !== null): ?>
                            <label for="player_<?= $uniqueId ?>"><a href="<?= BaseFunctions::escape(get_permalink($player->getPostId()), 'url') ?>" target="_blank">Player</a></label>
                        <?php else: ?>
                            <label for="player_<?= $uniqueId ?>">Player</label>
                        <?php endif; ?>
                        <input id="player_<?= $uniqueId ?>" type="text" value="<?= BaseFunctions::escape($player->getName(), 'html') ?>" readonly required>
                    </div>
                    <div class="col s3">
                        <label for="initiative_<?= $uniqueId ?>">Initiative</label>
                        <input id="initiative_<?= $uniqueId ?>" type="number" class="validate" min="1" name="p_initiative[<?= $playerId ?>]" value="" required>
                    </div>
                    <div class="col s3">
                        <label for="hp_<?= $uniqueId ?>">HP</label>
                        <input id="hp_<?= $uniqueId ?>" type="number" value="<?= $player->getHp() ?>" readonly required>
                    </div>
                    <div class="col s3">
                        <label for="currentHp_<?= $uniqueId ?>">Current HP</label>
                        <input id="currentHp_<?= $uniqueId ?>" type="number" class="validate" min="0" name="p_currentHp[<?= $playerId ?>]" value="<?= $player->getCurrentHp() ?: $player->getHp() ?>" required>
                    </div>
                </div>
                <?php
                ++$uniqueId;
            }
            foreach ($monsterCounts as $monsterId => $monsterCount) {
                $monster = $monsters[$monsterId];
                for ($i = 1; $i <= $monsterCount; ++$i) {
                    $generatedHp = $monster->getGeneratedHp();
                    ?>
                    <div class="row">
                        <div class="col s3">
                            <?php if ($monster->getUrl() !== ''): ?>
                                <label for="monster_<?= $uniqueId ?>"><a href="<?= BaseFunctions::escape($monster->getUrl(), 'url') ?>" target="_blank">Monster</a></label>
                            <?php else: ?>
                                <label for="monster_<?= $uniqueId ?>">Monster</label>
                            <?php endif; ?>
                            <input id="monster_<?= $uniqueId ?>" type="text" name="name[<?= $monsterId ?>_<?= $i ?>]" value="<?= BaseFunctions::escape($monster->getName(), 'html') ?><?= ($monsterCount > 1 ? ' ' . $i : '') ?>" required>
                        </div>
                        <div class="col s3">
                            <label for="initiative_<?= $uniqueId ?>">Initiative</label>
                            <input id="initiative_<?= $uniqueId ?>" type="number" class="validate" min="<?= $monster->getMinInitiative() ?>" max="<?= $monster->getMaxInitiative() ?>" name="initiative[<?= $monsterId ?>_<?= $i ?>]" value="<?= $monster->getGeneratedInitiative() ?>" required>
                        </div>
                        <div class="col s3">
                            <label for="hp_<?= $uniqueId ?>">HP</label>
                            <input id="hp_<?= $uniqueId ?>" type="number" class="validate" min="<?= $monster->getMinHp() ?>" max="<?= $monster->getMaxHp() ?>" name="hp[<?= $monsterId ?>_<?= $i ?>]" value="<?= $generatedHp ?>" required>
                        </div>
                        <div class="col s3">
                            <label for="currentHp_<?= $uniqueId ?>">Current HP</label>
                            <input id="currentHp_<?= $uniqueId ?>" type="number" class="validate" min="0" name="currentHp[<?= $monsterId ?>_<?= $i ?>]" value="<?= $generatedHp ?>" required>
                        </div>
                    </div>
                    <?php
                    ++$uniqueId;
                }
            }
            ?>
            <button type="submit" class="btn">Setup Players</button>
        </form>
        <?php
        return ob_get_clean() . $content;
    }
}
