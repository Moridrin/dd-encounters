<?php

namespace dd_encounters\PostType\Encounter\Templates;

use dd_encounters\models\CombatAction;
use dd_encounters\models\Creature;
use dd_encounters\models\Player;
use mp_general\base\BaseFunctions;

if (!defined('ABSPATH')) {
    exit;
}

/** @noinspection PhpUndefinedClassInspection */
abstract class ActionLog
{
    /**
     * @param CombatAction[] $actions
     * @param Creature[]     $creatures
     *
     * @return string
     */
    public static function show(array $actions, array $creatures): string
    {
        ob_start();
        foreach ($creatures as $creature) {
            if ($creature instanceof Player && $creature->getPostId() !== null) {
                ?>
                <div class="modal" id="player_<?= $creature->getId() ?>">
                    <div class="modal-content">[pc header="<?= $creature->getName() ?>" id="<?= $creature->getPostId() ?>"]</div>
                </div>
                <?php
            }
        }
        $html = ob_get_clean();
        $rows = [];
        foreach ($actions as $action) {
            $killsHtml             = [];
            $damages = $action->getDamage();
            foreach ($action->getAffectedCreatures() as $affectedCreatureId) {
                $affectedCreature = $creatures[$affectedCreatureId];
                $died = $affectedCreature->addDamage($damages[$affectedCreatureId]);
                if ($died) {
                    $killsHtml[] = self::getCreatureHtml($creatures[$affectedCreatureId]);
                }
            }
            $actorHtml             = self::getCreatureHtml($creatures[$action->getActor()]);
            $affectedCreaturesHtml = [];
            foreach ($action->getAffectedCreatures() as $affectedCreatureId) {
                $affectedCreaturesHtml[] = self::getCreatureHtml($creatures[$affectedCreatureId]);
            }
            $affectedCreaturesHtml = BaseFunctions::arrayToEnglish($affectedCreaturesHtml);
            $killsHtml = BaseFunctions::arrayToEnglish($killsHtml);
            $totalDamage = array_sum($action->getDamage());
            ob_start();
            ?>
            <tr id="logRow_<?= $action->getId() ?>">
                <td><?= $actorHtml ?></td>
                <td><?= BaseFunctions::escape($action->getAction(), 'html') ?></td>
                <td><?= $affectedCreaturesHtml ?></td>
                <td><?= $totalDamage > 0 ? 'dealing a total of' : '' ?></td>
                <td><?= $totalDamage > 0 ? BaseFunctions::escape($totalDamage, 'html') : '' ?></td>
                <td><?= $totalDamage > 0 ? 'damage' : '' ?></td>
                <td><?= !empty($killsHtml) ? 'killing' : '' ?></td>
                <td><?= $killsHtml ?></td>
                <td><a href="javascript:void(0)" onclick="deleteLogEntry(<?= $action->getId() ?>)"><i class="material-icons">delete</i></a></td>
            </tr>
            <?php
            $rows[] = ob_get_clean();
        }
        $rows = array_reverse($rows);
        ob_start();
        ?>
        <table class="striped">
            <?= implode('', $rows); ?>
        </table>
        <script>
            function deleteLogEntry(entryId) {
                jQuery.post(
                    '<?= admin_url('admin-ajax.php') ?>',
                    {
                        action: 'mp_dd_encounters_delete_log_entry',
                        id: entryId,
                    },
                    function (data) {
                        if (generalFunctions.ajaxResponse(data)) {
                            generalFunctions.removeElement(document.getElementById('logRow_' + entryId));
                        }
                    }
                );
            }
        </script>
        <?php
        $html .= ob_get_clean();
        return $html;
    }

    private static function getCreatureHtml(Creature $creature): string
    {
        if ($creature instanceof Player && $creature->getPostId() !== null) {
            /** @noinspection HtmlUnknownAnchorTarget */
            return '<a href="#player_' . $creature->getId() . '" class="modal-trigger">' . BaseFunctions::escape($creature->getName(), 'html') . '</a>';
        } else {
            return BaseFunctions::escape($creature->getName(), 'html');
        }
    }
}
