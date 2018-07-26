<?php

namespace dd_encounters\PostType\Encounter\Templates;

use dd_encounters\models\Creature;
use mp_general\base\BaseFunctions;

if (!defined('ABSPATH')) {
    exit;
}

/** @noinspection PhpUndefinedClassInspection */
abstract class EncounterForm
{

    /**
     * @param int        $currentCreatureId
     * @param Creature[] $creatures
     * @param string[]   $previouslyUsedCombatActions
     * @param string     $nextCreatureUrl
     * @return string
     */
    public static function show(int $currentCreatureId, array $creatures, array $previouslyUsedCombatActions, string $nextCreatureUrl): string
    {
        $currentCreature = $creatures[$currentCreatureId];
        ob_start();
        ?>
        <form method="post">
            <input type="hidden" name="action" value="saveCombatAction">
            <table>
                <tr>
                    <td>
                        <?php if ($currentCreature->getUrl() !== ''): ?>
                            <label for="actor"><a href="<?= BaseFunctions::escape($currentCreature->getUrl(), 'url') ?>" target="_blank">Actor</a></label>
                        <?php else: ?>
                            <label for="actor">Actor</label>
                        <?php endif; ?>
                        <select id="actor" name="actor">
                            <?php
                            foreach ($creatures as $id => $creature) {
                                ?>
                                <option value="<?= BaseFunctions::escape($id, 'attr') ?>" <?= selected($currentCreatureId, $id) ?>><?= BaseFunctions::escape($creature->getName(), 'html') ?> (<?= BaseFunctions::escape($creature->getCurrentHp(), 'html') ?>)</option><?php
                            }
                            ?>
                        </select>
                    </td>
                    <td>
                        <label for="creatureAction">Action</label>
                        <input id="creatureAction" type="text" name="creatureAction" list="previousActions" autocomplete="off">
                        <datalist id="previousActions">
                            <?php foreach ($previouslyUsedCombatActions as $previousAction): ?>
                                <option value="<?= BaseFunctions::escape($previousAction, 'attr') ?>"><?= BaseFunctions::escape($previousAction, 'html') ?></option>
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
}

