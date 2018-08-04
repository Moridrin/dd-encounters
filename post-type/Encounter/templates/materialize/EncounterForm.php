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
            <div class="row">
                <div class="input-field col s3">
                    <select id="actor" name="actor">
                        <?php
                        foreach ($creatures as $id => $creature) {
                            ?>
                            <option value="<?= BaseFunctions::escape($id, 'attr') ?>" <?= selected($currentCreatureId, $id) ?>><?= BaseFunctions::escape($creature->getName(), 'html') ?> (<?= BaseFunctions::escape($creature->getCurrentHp(), 'html') ?>)</option><?php
                        }
                        ?>
                    </select>
                    <?php if ($currentCreature->getUrl() !== ''): ?>
                        <label for="actor"><a href="<?= BaseFunctions::escape($currentCreature->getUrl(), 'url') ?>" target="_blank">Actor</a></label>
                    <?php else: ?>
                        <label for="actor">Actor</label>
                    <?php endif; ?>
                </div>
                <div class="input-field col s3">
                    <input id="creatureAction" type="text" name="creatureAction" list="previousActions" autocomplete="off">
                    <label for="creatureAction">Action</label>
                </div>
                <datalist id="previousActions">
                    <?php foreach ($previouslyUsedCombatActions as $previousAction): ?>
                        <option value="<?= $previousAction ?>"><?= $previousAction ?></option>
                    <?php endforeach; ?>
                </datalist>
                <div class="input-field col s3">
                    <select id="affectedCreatures" name="affectedCreatures[]" multiple onchange="targetChanged()">
                        <?php foreach ($creatures as $id => $creature): ?>
                            <option value="<?= BaseFunctions::escape($id, 'attr') ?>"><?= BaseFunctions::escape($creature->getName(), 'html') ?> (<?= BaseFunctions::escape($creature->getCurrentHp(), 'html') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <label for="affectedCreatures">Target</label>
                </div>
                <div class="col s3">
                    <?php foreach ($creatures as $id => $creature): ?>
                        <div class="input-field" style="display: none;">
                            <input id="damage_<?= BaseFunctions::escape($id, 'attr') ?>" class="damageInput" onchange="damageChanged(this)" onfocusout="damageFocusLost()" type="number" name="damage[<?= BaseFunctions::escape($id, 'attr') ?>]" placeholder="Damage">
                            <label for="damage_<?= BaseFunctions::escape($id, 'attr') ?>"><?= BaseFunctions::escape($creature->getName(), 'html') ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <!--suppress JSUnusedLocalSymbols -->
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
            </div>
            <div class="row">
                <button type="submit" style="display: none;">Submit Action</button>
                <div class="col s6">
                    <a href="<?= BaseFunctions::escape($nextCreatureUrl, 'url') ?>" class="btn">Next Monster</a>
                </div>
                <div class="col s6 right-align">
                    <a href="<?= BaseFunctions::escape(BaseFunctions::getCurrentUrlWithArguments(['finish' => true]), 'url') ?>" class="btn">Finish Combat</a>
                </div>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }
}
