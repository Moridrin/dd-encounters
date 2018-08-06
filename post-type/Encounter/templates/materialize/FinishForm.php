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

abstract class FinishForm
{
    /**
     * @param CombatAction[] $actions
     * @param Creature[]     $creatures
     * @param string         $content
     *
     * @return string
     */
    public static function show(array $actions, array $creatures, string $content): string
    {
        ob_start();
        foreach ($creatures as $creature) {
            if ($creature instanceof Player && $creature->getPostId() !== null) {
                ?>
                <div class="modal" id="player_<?= $creature->getId() ?>">
                    <div class="modal-content"><?= '[pc header="' . $creature->getName() . '" header-url="' . $creature->getUrl() . '" id="' . $creature->getPostId() . ' "]' ?></div>
                </div>
                <?php
            }
        }
        $modalsHtml = preg_replace('/\h+/', ' ', ob_get_clean());
        $parts      = [$content];
        $rows       = [];
        foreach ($actions as $action) {
            $killsHtml = [];
            $damages   = $action->getDamage();
            foreach ($action->getAffectedCreatures() as $affectedCreatureId) {
                $affectedCreature = $creatures[$affectedCreatureId];
                $died             = $affectedCreature->addDamage($damages[$affectedCreatureId]);
                if ($died) {
                    $killsHtml[] = self::getCreatureHtml($creatures[$affectedCreatureId]);
                }
            }
            $actorHtml                 = self::getCreatureHtml($creatures[$action->getActor()]);
            $affectedCreaturesInAction = false;
            $affectedCreaturesHtml     = [];
            foreach ($action->getAffectedCreatures() as $affectedCreatureId) {
                $affectedCreaturesHtml[] = self::getCreatureHtml($creatures[$affectedCreatureId]);
            }
            $affectedCreaturesHtml = BaseFunctions::arrayToEnglish($affectedCreaturesHtml);
            $killsHtml             = BaseFunctions::arrayToEnglish($killsHtml);
            $totalDamage           = array_sum($action->getDamage());
            $actionHtml            = (string)BaseFunctions::escape($action->getAction(), 'html');

            $rowHtml = $actorHtml;
            if (strpos($actionHtml, '$target$') || strpos($actionHtml, '$$')) {
                $actionHtml                = str_replace(['$$', '$target$'], $affectedCreaturesHtml, $actionHtml);
                $affectedCreaturesInAction = true;
            } elseif (count($action->getAffectedCreatures()) === 1 && strpos($creatures[$action->getAffectedCreatures()[0]]->getName(), $actionHtml) !== false) {
                $affectedCreaturesInAction = true;
            }
            if ($actionHtml !== '') {
                $rowHtml .= ' ' . $actionHtml;
            }
            if (!$affectedCreaturesInAction && $affectedCreaturesHtml !== '') {
                $rowHtml .= ' ' . $affectedCreaturesHtml;
            }
            if ($totalDamage !== 0) {
                $rowHtml .= '<span data-display="game"> dealing ';
                if (count($action->getAffectedCreatures()) > 1) {
                    $rowHtml .= 'a total of ';
                }
                $rowHtml .= BaseFunctions::escape($totalDamage, 'html') . ' damage</span>';
            } elseif ($totalDamage < 0) {
                $rowHtml .= '<span data-display="game"> healing ';
                if (count($action->getAffectedCreatures()) > 1) {
                    $rowHtml .= 'a total of ';
                }
                $rowHtml .= BaseFunctions::escape($totalDamage * -1, 'html') . 'hp</span>';
            }
            if ($killsHtml !== '') {
                $rowHtml .= ', killing ' . $killsHtml;
            }
            $rows[] = ucfirst($rowHtml) . '.';
        }
        // $rows    = array_reverse($rows);
        $parts[] = '<h1>Log</h1>' . PHP_EOL . implode(PHP_EOL, $rows);
        $parts[] = $modalsHtml;
        $parts   = array_filter($parts);
        ob_start();
        ?>
        <form method="post">
            <input type="hidden" name="action" value="finishEncounter">
            <div class="row">
                <div class="input-field col s12 m6">
                    <textarea id="final" name="final" class="materialize-textarea" onchange="updatePreview()"><?= stripslashes(implode(PHP_EOL . PHP_EOL, $parts)) ?></textarea>
                    <label for="final">Final Content</label>
                </div>
                <div class="col s12 m6">
                    <div class="card">
                        <div class="card-image primary">
                            <h1 style="margin: 0; padding: 10px;">Preview</h1>
                            <a class="btn-floating halfway-fab waves-effect waves-light accent" onclick="updatePreview()"><i id="syncButton" class="material-icons">sync</i></a>
                        </div>
                        <div class="card-content" id="preview">
                        </div>
                    </div>
                    <button class="button btn">Save</button>
                </div>
            </div>
        </form>
        <script>
            let finalTextArea = document.getElementById('final');
            let previewDiv = document.getElementById('preview');
            let lastValue = '';

            function checkForUpdate() {
                if (finalTextArea.value !== lastValue) {
                    updatePreview();
                }
            }

            setInterval(checkForUpdate, 5000);

            function updatePreview() {
                let currentValue = finalTextArea.value;
                let syncButton = document.getElementById('syncButton');
                syncButton.classList.add('rotating');
                previewDiv.innerHTML = '';
                setTimeout(function () {
                    if (currentValue !== lastValue) {
                        jQuery.post(
                            '<?= BaseFunctions::escape(admin_url('admin-ajax.php'), 'js') ?>',
                            {
                                action: 'mp_dd_encounters_filterContent',
                                content: currentValue,
                            },
                            function (data) {
                                if (generalFunctions.ajaxResponse(data)) {
                                    data = JSON.parse(data);
                                    previewDiv.innerHTML = data['content'];
                                    syncButton.classList.remove('rotating');
                                    lastValue = currentValue;
                                }
                            }
                        );
                    } else {
                        syncButton.classList.remove('rotating');
                        previewDiv.innerHTML = lastValue;
                    }
                }, 200);
            }

            updatePreview();
        </script>
        <?php
        return ob_get_clean();
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
