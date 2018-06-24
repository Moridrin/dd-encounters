<?php

namespace dd_encounters\PostType;

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
        $currentCreature = BaseFunctions::sanitize($_GET['activeCreature'] ?? 1, 'int');
        $nextCreatureUrl = BaseFunctions::getCurrentUrlWithArguments(['activeCreature' => ($currentCreature + 1) % count($creatures)]);
        ob_start();
        ?>
        <form method="post">
            <div class="row">
                <div class="input-field col s3">
                    <select id="actor" name="actor">
                        <?php
                        $i = 1;
                        foreach ($creatures as $id => $creature) {
                            ?>
                            <option value="<?= BaseFunctions::escape($id, 'attr') ?>" <?= selected($currentCreature, $i) ?>><?= BaseFunctions::escape($creature->getName(), 'html') ?> (<?= BaseFunctions::escape($creature->getCurrentHp(), 'html') ?>)</option>
                            <?php
                            ++$i;
                        }
                        ?>
                    </select>
                    <label for="actor">Actor</label>
                </div>
                <div class="input-field col s3">
                    <select id="affectedCreatures" name="affectedCreatures[]" multiple>
                        <?php foreach ($creatures as $id => $creature): ?>
                            <option value="<?= BaseFunctions::escape($id, 'attr') ?>"><?= BaseFunctions::escape($creature->getName(), 'html') ?> (<?= BaseFunctions::escape($creature->getCurrentHp(), 'html') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <label for="affectedCreatures">Target</label>
                </div>
                <div class="input-field col s3">
                    <input id="action" type="text" name="action">
                    <label for="action">Action</label>
                </div>
                <div class="input-field col s3">
                    <input id="damage" type="number" name="damage">
                    <label for="damage">Damage</label>
                </div>
            </div>
            <div class="row">
                <a href="<?= $nextCreatureUrl ?>" class="btn">Next Monster</a>
            </div>
        </form>
        <?php
        return ob_get_clean() . $content;
    }

    private static function setupEncounter(array $monsterCounts, array $players): ?string
    {
        global $post;
        if (BaseFunctions::isValidPOST(null)) {
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

