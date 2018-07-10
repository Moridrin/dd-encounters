<?php

namespace dd_encounters\PostType\Templates;

use dd_encounters\models\CombatAction;
use dd_encounters\models\CombatMonster;
use dd_encounters\models\Creature;
use dd_encounters\models\Player;
use Exception;
use mp_general\base\BaseFunctions;

if (!defined('ABSPATH')) {
    exit;
}

abstract class EncounterForm
{
    /**
     * @param int $postId
     *
     * @throws Exception
     */
    public static function process(int $postId): void
    {
        $players        = Player::findByIds(get_post_meta($postId, 'activePlayers', true), 'p_initiative', 'DESC');
        $combatMonsters = CombatMonster::findByEncounterId($postId);
        /** @var Creature[] $creatures */
        $creatures = array_merge($players, $combatMonsters);

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
        CombatAction::create($postId, $actor, $affectedCreatures, $creatureAction, $damage);
    }

    public static function show(int $postId, string $content): string
    {
        $players        = Player::findByIds(get_post_meta($postId, 'activePlayers', true), 'p_initiative', 'DESC');
        $combatMonsters = CombatMonster::findByEncounterId($postId);
        $actions        = CombatAction::findByEncounterId($postId);
        $creatures      = array_merge($combatMonsters, $players);
        usort(
            $creatures,
            function (Creature $a, Creature $b) {
                return $b->getInitiative() - $a->getInitiative();
            }
        );
        $actionLog = self::getActionLog($actions, $creatures);
        return self::actionForm($postId, $creatures) . $actionLog . $content;
    }

    /**
     * @param int        $postId
     * @param Creature[] $creatures
     *
     * @return string|null
     */
    private static function actionForm(int $postId, array $creatures): ?string
    {
        $currentCreature = BaseFunctions::sanitize($_GET['activeCreature'] ?? 0, 'int');
        $nextCreatureUrl = BaseFunctions::getCurrentUrlWithArguments(['activeCreature' => ($currentCreature + 1) % count($creatures)]);
        ob_start();
        ?>
        <form method="post">
            <input type="hidden" name="action" value="saveCombatAction">
            <table>
                <tr>
                    <td>
                        <label for="actor">Actor</label>
                        <select id="actor" name="actor">
                            <?php
                            foreach ($creatures as $id => $creature) {
                                ?>
                                <option value="<?= BaseFunctions::escape($id, 'attr') ?>" <?= selected($currentCreature, $id) ?>><?= BaseFunctions::escape($creature->getName(), 'html') ?> (<?= BaseFunctions::escape($creature->getCurrentHp(), 'html') ?>)</option><?php
                            }
                            ?>
                        </select>
                    </td>
                    <td>
                        <label for="creatureAction">Action</label>
                        <input id="creatureAction" type="text" name="creatureAction" list="previousActions" autocomplete="off">
                        <datalist id="previousActions">
                            <?php foreach (CombatAction::getAutocompleteByEncounterAmdActorId($postId, $currentCreature) as $previousAction): ?>
                                <option value="<?= $previousAction ?>"><?= $previousAction ?></option>
                            <?php endforeach; ?>
                        </datalist>
                    </td>
                    <td>
                        <label for="affectedCreatures">Target</label>
                        <select id="affectedCreatures" name="affectedCreatures[]" multiple onchange="targetChanged()" size="<?= count($creatures) ?>" style="height: 100%;">
                            <?php foreach ($creatures as $id => $creature): ?>
                                <option value="<?= BaseFunctions::escape($id, 'attr') ?>"><?= BaseFunctions::escape($creature->getName(), 'html') ?> (<?= BaseFunctions::escape($creature->getCurrentHp(), 'html') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <?php foreach ($creatures as $id => $creature): ?>
                            <div style="display: none;">
                                <label for="damage_<?= BaseFunctions::escape($id, 'attr') ?>"><?= BaseFunctions::escape($creature->getName(), 'html') ?></label>
                                <input id="damage_<?= BaseFunctions::escape($id, 'attr') ?>" class="damageInput" onchange="damageChanged(this)" onfocusout="damageFocusLost()" type="number" name="damage[<?= BaseFunctions::escape($id, 'attr') ?>]" placeholder="Damage">
                            </div>
                        <?php endforeach; ?>
                    </td>
                </tr>
            </table>
            <script>
                function targetChanged() {
                    let select = document.getElementById('affectedCreatures');
                    for (let i = 0; i < select.length; i++) {
                        document.getElementById('damage_' + select.options[i].value).parentElement.style.display = select.options[i].selected ? 'block' : 'none';
                    }
                }

                function damageChanged(sender) {
                    let damageInputs = document.getElementsByClassName('damageInput');
                    for (let i = 0; i < damageInputs.length; ++i) {
                        damageInputs.item(i).value = sender.value;
                    }
                }

                function damageFocusLost() {
                    let damageInputs = document.getElementsByClassName('damageInput');
                    for (let i = 0; i < damageInputs.length; ++i) {
                        damageInputs.item(i).removeAttribute("onchange")
                    }
                }
            </script>
            <div class="row">
                <button type="submit" style="display: none;">Submit Action</button>
                <a href="<?= $nextCreatureUrl ?>" class="btn">Next Monster</a>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * @param CombatAction[] $actions
     * @param Creature[]     $creatures
     *
     * @return string
     */
    private static function getActionLog(array $actions, array $creatures): string
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
            $hurtHtml             = [];
            $damages = $action->getDamage();
            foreach ($action->getAffectedCreatures() as $affectedCreatureId) {
                $affectedCreature = $creatures[$affectedCreatureId];
                $died = $affectedCreature->addDamage($damages[$affectedCreatureId]);
                if ($died) {
                    $killsHtml[] = self::getCreatureHtml($creatures[$affectedCreatureId]);
                } elseif ($damages[$affectedCreatureId] > ($affectedCreature->getMaxHp() / 3)) {
                    $hurtHtml[] = self::getCreatureHtml($creatures[$affectedCreatureId]);
                }
            }
            $actorHtml             = self::getCreatureHtml($creatures[$action->getActor()]);
            $affectedCreaturesHtml = [];
            foreach ($action->getAffectedCreatures() as $affectedCreatureId) {
                $affectedCreaturesHtml[] = self::getCreatureHtml($creatures[$affectedCreatureId]);
            }
            $affectedCreaturesHtml = BaseFunctions::arrayToEnglish($affectedCreaturesHtml);
            $killsHtml = BaseFunctions::arrayToEnglish($killsHtml);
            $hurtHtml = BaseFunctions::arrayToEnglish($hurtHtml);
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
                <td><?= !empty($hurtHtml) ? 'and severely hurting' : '' ?></td>
                <td><?= $hurtHtml ?></td>
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
