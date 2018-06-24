<?php

namespace dd_encounters\PostType;

use dd_encounters\models\CombatAction;
use dd_encounters\models\CombatMonster;
use dd_encounters\models\Creature;
use dd_encounters\models\Monster;
use dd_encounters\models\Player;
use Exception;
use mp_general\base\BaseFunctions;

if (!defined('ABSPATH')) {
    exit;
}

abstract class Encounter
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

        $monsterCounts  = get_post_meta($post->ID, 'monsters', true);
        $combatMonsters = CombatMonster::findByEncounterId($post->ID);
        $players        = Player::findByIds(get_post_meta($post->ID, 'activePlayers', true), 'p_initiative', 'DESC');
        $startSetup     = empty($combatMonsters);
        if ($startSetup === false) {
            foreach ($players as $player) {
                if ($player->getInitiative() === null) {
                    $startSetup = true;
                    break;
                }
            }
        }
        if ($startSetup) {
            $result = self::setupEncounter($monsterCounts, $players);
            if ($result !== null) {
                return $result . $content;
            }
            $combatMonsters = CombatMonster::findByEncounterId($post->ID);
        }
        /** @var Creature[] $creatures */
        $creatures = array_merge($combatMonsters, $players);
        usort(
            $creatures,
            function (Creature $a, Creature $b) {
                return $b->getInitiative() - $a->getInitiative();
            }
        );
        $actionForm = self::actionForm($creatures);
        $actionLog  = self::getActionLog($creatures);
        return $actionForm . $actionLog . $content;
    }

    private static function setupEncounter(array $monsterCounts, array $players): ?string
    {
        global $post;
        if (BaseFunctions::isValidPOST(null) && $_POST['action'] === 'createMonsters') {
            foreach ($players as $playerId => $player) {
                $player
                    ->setInitiative(BaseFunctions::sanitize($_POST['p_initiative'][$playerId], 'int'))
                    ->setCurrentHp(BaseFunctions::sanitize($_POST['p_currentHp'][$playerId], 'int'))
                    ->save()
                ;
            }
            foreach (BaseFunctions::sanitize($_POST['name'], 'text') as $id => $name) {
                list($monsterId, $combatMonsterId) = explode('_', $id);
                CombatMonster::create(
                    $post->ID,
                    $monsterId,
                    $name,
                    BaseFunctions::sanitize($_POST['hp'][$id], 'int'),
                    BaseFunctions::sanitize($_POST['currentHp'][$id], 'int'),
                    BaseFunctions::sanitize($_POST['initiative'][$id], 'int')
                );
            }
            return null;
        }

        $monsters = Monster::findByIds(array_keys($monsterCounts));
        $uniqueId = 0;
        ob_start();
        ?>
        <form method="post">
            <input type="hidden" name="action" value="createMonsters">
            <?php
            foreach ($players as $playerId => $player) {
                ?>
                <div class="row">
                    <div class="col s3">
                        <label for="player_<?= $uniqueId ?>">Player</label>
                        <input id="player_<?= $uniqueId ?>" type="text" value="<?= $player->getName() ?>" readonly required>
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
                            <label for="player_<?= $uniqueId ?>">Monster</label>
                            <input id="player_<?= $uniqueId ?>" type="text" name="name[<?= $monsterId ?>_<?= $i ?>]" value="<?= $monster->getName() ?> <?= $i ?>" required>
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
        return ob_get_clean();
    }

    /**
     * @param Creature[] $creatures
     *
     * @return string
     */
    private static function actionForm(array $creatures): string
    {
        global $post;
        if (BaseFunctions::isValidPOST(null) && $_POST['action'] === 'saveCombatAction') {
            $actor             = BaseFunctions::sanitize($_POST['actor'], 'text');
            $affectedCreatures = BaseFunctions::sanitize($_POST['affectedCreatures'], 'int');
            $creatureAction    = BaseFunctions::sanitize($_POST['creatureAction'], 'text');
            $damage            = BaseFunctions::sanitize($_POST['damage'], 'int');
            $kills             = [];
            foreach ($affectedCreatures as $affectedCreatureId) {
                $affectedCreature = $creatures[$affectedCreatureId];
                $died             = $affectedCreature->addDamage($damage);
                $affectedCreature->save();
                if ($died) {
                    $kills[] = $affectedCreatureId;
                }
            }
            CombatAction::create($post->ID, $actor, $affectedCreatures, $creatureAction, $damage, $kills);
        }
        $currentCreature = BaseFunctions::sanitize($_GET['activeCreature'] ?? 0, 'int');
        $nextCreatureUrl = BaseFunctions::getCurrentUrlWithArguments(['activeCreature' => ($currentCreature + 1) % count($creatures)]);
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
                            <option value="<?= BaseFunctions::escape($id, 'attr') ?>" <?= selected($currentCreature, $id) ?>><?= BaseFunctions::escape($creature->getName(), 'html') ?> (<?= BaseFunctions::escape($creature->getCurrentHp(), 'html') ?>)</option><?php
                        }
                        ?>
                    </select>
                    <label for="actor">Actor</label>
                </div>
                <div class="input-field col s3">
                    <input id="creatureAction" type="text" name="creatureAction" list="previousActions" autocomplete="off">
                    <label for="creatureAction">Action</label>
                </div>
                <datalist id="previousActions">
                    <?php foreach (CombatAction::getAutocompleteByEncounterAmdActorId($post->ID, $currentCreature) as $previousAction): ?>
                        <option value="<?= $previousAction ?>"><?= $previousAction ?></option>
                    <?php endforeach; ?>
                </datalist>
                <div class="input-field col s3">
                    <select id="affectedCreatures" name="affectedCreatures[]" multiple>
                        <?php foreach ($creatures as $id => $creature): ?>
                            <option value="<?= BaseFunctions::escape($id, 'attr') ?>"><?= BaseFunctions::escape($creature->getName(), 'html') ?> (<?= BaseFunctions::escape($creature->getCurrentHp(), 'html') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <label for="affectedCreatures">Target</label>
                </div>
                <div class="input-field col s3">
                    <input id="damage" type="number" name="damage">
                    <label for="damage">Damage</label>
                </div>
            </div>
            <div class="row">
                <button type="submit" style="display: none;">Submit Action</button>
                <a href="<?= $nextCreatureUrl ?>" class="btn">Next Monster</a>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * @param Creature[] $creatures
     *
     * @return string
     */
    private static function getActionLog(array $creatures): string
    {
        global $post;
        $actions = CombatAction::findByEncounterId($post->ID, 'id', 'DESC');
        ob_start();
        foreach ($actions as $action) {
            $actor = $creatures[$action->getActor()];
            if ($actor instanceof Player && $actor->getPostId() !== null) {
                ?>
            <div class="modal" id="player_<?= $actor->getId() ?>">
                <div class="modal-content">[pc header="<?= $actor->getName() ?>" id="<?= $actor->getPostId() ?>"]</div></div><?php
            }
        }
        ?>
        <table class="striped">
            <?php
            foreach ($actions as $action) {
                $actorHtml             = self::getCreatureHtml($creatures[$action->getActor()]);
                $affectedCreaturesHtml = [];
                foreach ($action->getAffectedCreatures() as $affectedCreatureId) {
                    $affectedCreaturesHtml[] = self::getCreatureHtml($creatures[$affectedCreatureId]);
                }
                $affectedCreaturesHtml = BaseFunctions::arrayToEnglish($affectedCreaturesHtml);
                $killsHtml = [];
                foreach ($action->getKills() as $killId) {
                    $killsHtml[] = self::getCreatureHtml($creatures[$killId]);
                }
                $killsHtml = BaseFunctions::arrayToEnglish($killsHtml);
                ?>
                <tr id="logRow_<?= $action->getId() ?>">
                    <td><?= $actorHtml ?></td>
                    <td><?= BaseFunctions::escape($action->getAction(), 'html') ?></td>
                    <td><?= $affectedCreaturesHtml ?></td>
                    <td>dealing</td>
                    <td><?= BaseFunctions::escape($action->getDamage(), 'html') ?></td>
                    <td>damage</td>
                    <td><?= !empty($killsHtml) ? 'killing' : ''?></td>
                    <td><?= $killsHtml ?></td>
                    <td><a href="javascript:void(0)" onclick="deleteLogEntry(<?= $action->getId() ?>)"><i class="material-icons">delete</i></a></td>
                </tr>
                <?php
            }
            ?>
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
        return ob_get_clean();
    }

    private static function getCreatureHtml(Creature $creature): string
    {
        if ($creature instanceof Player && $creature->getPostId() !== null) {
            return '<a href="#player_' . $creature->getId() . '" class="modal-trigger">' . BaseFunctions::escape($creature->getName(), 'html') . '</a>';
        } else {
            return BaseFunctions::escape($creature->getName(), 'html');
        }
    }

    public static function setupPostType(): void
    {

        $labels = [
            'name'               => 'Encounters',
            'singular_name'      => 'Encounter',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Encounter',
            'edit_item'          => 'Edit Encounter',
            'new_item'           => 'New Encounter',
            'view_item'          => 'View Encounter',
            'search_items'       => 'Search Encounters',
            'not_found'          => 'No Encounters found',
            'not_found_in_trash' => 'No Encounters found in Trash',
            'parent_item_colon'  => 'Parent Encounter:',
            'menu_name'          => 'Encounters',
        ];

        $args = [
            'labels'              => $labels,
            'hierarchical'        => true,
            'description'         => 'Encounters filterable by category',
            'supports'            => [
                'title',
                'editor',
                'author',
                'thumbnail',
                'trackbacks',
                'custom-fields',
                'comments',
                'revisions',
                'page-attributes',
            ],
            'taxonomies'          => ['encounter_category', 'encounter_monsters'],
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-calendar-alt',
            'show_in_nav_menus'   => true,
            'publicly_queryable'  => true,
            'exclude_from_search' => false,
            'has_archive'         => true,
            'query_var'           => true,
            'can_export'          => true,
            'rewrite'             => true,
            'capability_type'     => 'post',
        ];

        register_post_type('encounter', $args);
    }

    public static function setupTaxonomy(): void
    {
        register_taxonomy(
            'encounter_category',
            'encounter',
            [
                'hierarchical' => true,
                'label'        => 'Encounter Categories',
                'query_var'    => true,
                'rewrite'      => [
                    'slug'       => 'encounter_category',
                    'with_front' => false,
                ],
            ]
        );
    }

    public static function addMetaBoxes(): void
    {
        add_meta_box('dd_encounter_players', 'Players', [Encounter::class, 'playersMetaBox'], 'encounter', 'side', 'default');
        add_meta_box('dd_encounter_monsters', 'Monsters', [Encounter::class, 'monstersMetaBox'], 'encounter', 'side', 'default');
    }

    public static function playersMetaBox(): void
    {
        global $post;
        $players          = Player::getAll();
        $activePlayersIds = get_post_meta($post->ID, 'activePlayers', true);
        if (!is_array($activePlayersIds)) {
            $activePlayersIds = array_column($players, 'id');
        }
        ?>
        <table width="100%">
            <tr>
                <th style="text-align: left;">Active</th>
                <th>Name</th>
            </tr>
            <?php foreach ($players as $player): ?>
                <tr>
                    <td>
                        <input
                                type="checkbox"
                                name="activePlayers[]"
                                value="<?= $player->getId() ?>"
                                title="<?= $player->getName() ?> is in this combat."
                            <?= checked(in_array($player->getId(), $activePlayersIds)) ?>
                        >
                    </td>
                    <td style="text-align: center;"><?= BaseFunctions::escape($player->getName(), 'html') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php
    }

    public static function monstersMetaBox(): void
    {
        global $post;
        $monsters       = Monster::getAll();
        $activeMonsters = get_post_meta($post->ID, 'monsters', true);
        if (!is_array($activeMonsters)) {
            $activeMonsters = [];
        }
        ?>
        <select id="monsterSelect" style="width: 100%;" title="Monster Selector">
            <option disabled selected="selected"></option>
            <?php foreach ($monsters as $monster): ?>
                <option value="<?= $monster->getId() ?>" <?= ($activeMonsters[$monster->getId()] ?? 0 > 0) ? 'disabled' : '' ?>><?= $monster->getName() ?></option>
            <?php endforeach; ?>
        </select>
        <script>
            jQuery(function ($) {
                $(document).ready(function () {
                    let monsterSelect = $('#monsterSelect');
                    monsterSelect.select2({
                        allowClear: true,
                        placeholder: "Add Monster",
                        tokenSeparators: [','],
                    });
                    monsterSelect.on('select2:select', function () {
                        $('#availableMonsters #monsterRow_' + $(this).val()).appendTo('#selectedMonsters');
                        let monsterCount = $('#monsterCount_' + $(this).val());
                        monsterCount.val(1);
                        monsterCount.prop('min', 1);
                        this.options[this.selectedIndex].disabled = true;
                        $(this).select2('destroy').select2();
                        $(this).val(null).trigger('change');
                    });
                    $('.removeMonster').on('click', function () {
                        $('#monsterRow_' + this.dataset.monsterId).appendTo('#availableMonsters');
                        let monsterCount = $('#monsterCount_' + this.dataset.monsterId);
                        monsterCount.prop('min', 0);
                        monsterCount.val(0);
                        $('#monsterSelect option[value="' + this.dataset.monsterId + '"]').prop('disabled', false);
                        monsterSelect.select2('destroy').select2();
                    });
                });
            });
        </script>
        <table id="availableMonsters" style="display: none;">
            <?php foreach ($monsters as $monster): ?>
                <?php if ((int)($activeMonsters[$monster->getId()] ?? 0) === 0): ?>
                    <tr id="monsterRow_<?= $monster->getId() ?>">
                        <td>
                            <input
                                    id="monsterCount_<?= $monster->getId() ?>"
                                    type="number"
                                    min="0"
                                    name="monsters[<?= $monster->getId() ?>]"
                                    value="0"
                                    style="width: 50px;"
                                    title="<?= $monster->getName() ?> is in this combat."
                                <?= checked(in_array($monster->getId(), $activeMonsters)) ?>
                            >
                        </td>
                        <td style="text-align: center;"><?= BaseFunctions::escape($monster->getName(), 'html') ?></td>
                        <td style="text-align: center;">
                            <button type="button" style="border-radius: 10px;" class="removeMonster" data-monster-id="<?= $monster->getId() ?>">X</button>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </table>
        <table id="selectedMonsters" width="100%">
            <tr>
                <th style="text-align: left;">Count</th>
                <th>Name</th>
                <th></th>
            </tr>
            <?php foreach ($monsters as $monster): ?>
                <?php if ($activeMonsters[$monster->getId()] ?? 0 > 0): ?>
                    <tr id="monsterRow_<?= $monster->getId() ?>">
                        <td>
                            <input
                                    id="monsterCount_<?= $monster->getId() ?>"
                                    type="number"
                                    min="1"
                                    name="monsters[<?= $monster->getId() ?>]"
                                    value="<?= $activeMonsters[$monster->getId()] ?>"
                                    style="width: 50px;"
                                    title="<?= $monster->getName() ?> is in this combat."
                                <?= checked(in_array($monster->getId(), $activeMonsters)) ?>
                            >
                        </td>
                        <td style="text-align: center;"><?= BaseFunctions::escape($monster->getName(), 'html') ?></td>
                        <td style="text-align: center;">
                            <button type="button" style="border-radius: 10px;" class="removeMonster" data-monster-id="<?= $monster->getId() ?>">X</button>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </table>
        <?php
    }

    public static function saveMetadata($postId): int
    {
        if (!current_user_can('edit_post', $postId)) {
            return $postId;
        }
        update_post_meta($postId, 'activePlayers', $_POST['activePlayers'] ?? []);
        update_post_meta($postId, 'monsters', array_filter($_POST['monsters'] ?? []));
        return $postId;
    }
}

add_filter('the_content', [Encounter::class, 'filterContent'], 13);
add_action('init', [Encounter::class, 'setupPostType']);
add_action('init', [Encounter::class, 'setupTaxonomy']);
add_action('add_meta_boxes', [Encounter::class, 'addMetaBoxes']);
add_action('save_post_encounter', [Encounter::class, 'saveMetadata']);

