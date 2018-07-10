<?php

namespace dd_encounters\models;

use InvalidArgumentException;
use mp_general\base\Database;
use mp_general\base\models\Model;

if (!defined('ABSPATH')) {
    exit;
}

class CombatAction extends Model
{
    #region Class
    public static function create(int $encounterId, string $actor, array $affectedCreatures, ?string $action, array $damage): ?int
    {
        if ($affectedCreatures !== array_keys($damage)) {
            throw new InvalidArgumentException('The keys of the damage array should equal the keys of the affected creatures array.');
        }
        return parent::_create(['ca_encounterId' => $encounterId, 'ca_actor' => $actor, 'ca_affectedCreatures' => json_encode($affectedCreatures), 'ca_action' => $action, 'ca_damage' => json_encode($damage)]);
    }

    /**
     * @param string $orderBy
     * @param string $order
     * @param string $key
     *
     * @return Player[]
     */
    public static function getAll(string $orderBy = 'id', string $order = 'DESC', string $key = 'id'): array
    {
        return parent::_getAll($orderBy, $order, $key);
    }

    /**
     * @param int $id
     *
     * @return Player
     * @throws \mp_general\exceptions\NotFoundException
     */
    public static function findById(int $id): Model
    {
        return parent::_findById($id);
    }

    public static function findByIds(array $ids, string $orderBy = 'id', string $order = 'DESC'): array
    {
        return parent::_findByIds($ids, $orderBy, $order);
    }

    /**
     * @param int    $encounterId
     * @param string $orderBy
     * @param string $order
     *
     * @return CombatAction[]
     */
    public static function findByEncounterId(int $encounterId, string $orderBy = 'id', string $order = 'DESC'): array
    {
        return parent::_find('ca_encounterId = ' . $encounterId, $orderBy, $order);
    }

    /**
     * @param int    $encounterId
     * @param string $affectedCreature
     * @param string $orderBy
     * @param string $order
     *
     * @return CombatAction[]
     */
    public static function findByEncounterIdAndAffectedCreature(int $encounterId, string $affectedCreature, string $orderBy = 'id', string $order = 'DESC'): array
    {
        return parent::_find('ca_encounterId = ' . $encounterId . ' AND JSON_SEARCH(ca_affectedCreatures, "all", "' . $affectedCreature . '") IS NOT NULL', $orderBy, $order);
    }

    /**
     * @param int $encounterId
     * @param int $actorId
     *
     * @return string[]
     */
    public static function getAutocompleteByEncounterAmdActorId(int $encounterId, int $actorId): array
    {
        global $wpdb;
        $table           = static::getDatabaseTableName();
        $previousActions = $wpdb->get_results("SELECT ca_action FROM $table WHERE ca_encounterId = $encounterId AND ca_actor = $actorId", ARRAY_A);
        $previousActions = array_count_values(array_column($previousActions, 'ca_action'));
        arsort($previousActions);
        return array_keys($previousActions);
    }

    public static function deleteByIds(array $ids): bool
    {
        return parent::_deleteByIds($ids);
    }

    public static function getTableColumns(): array
    {
        return [
            'ca_actor'             => 'Actor',
            'ca_affectedCreatures' => 'Affected Monsters',
            'ca_action'            => 'Action',
            'ca_damage'            => 'Damage',
        ];
    }

    public static function getDatabaseTableName(int $blogId = null): string
    {
        return Database::getPrefixForBlog($blogId) . 'dd_combat_actions';
    }

    protected static function _getDatabaseFields(): array
    {
        return ['`ca_encounterId` INT NOT NULL', '`ca_actor` VARCHAR(50) NOT NULL', '`ca_affectedCreatures` TEXT NOT NULL', '`ca_action` VARCHAR(255)', '`ca_damage` INT NOT NULL', '`ca_kills` TEXT NULL'];
    }

    public static function getDatabaseCreateQuery(int $blogId = null): string
    {
        return parent::_getDatabaseCreateQuery($blogId);
    }
    #endregion

    #region Instance
    public function __init(): void
    {
        $this->row['ca_affectedCreatures'] = json_decode($this->row['ca_affectedCreatures'], true);
        $this->row['ca_damage']            = json_decode($this->row['ca_damage'], true);
    }

    public function _beforeSave(): bool
    {
        $this->row['ca_affectedCreatures'] = json_encode($this->row['ca_affectedCreatures']);
        $this->row['ca_damage']            = json_encode($this->row['ca_damage']);
        return true;
    }

    public function getActor(): string
    {
        return $this->row['ca_actor'];
    }

    public function setActor(string $actor)
    {
        $this->row['ca_actor'] = $actor;
    }

    public function getAffectedCreatures(): array
    {
        return $this->row['ca_affectedCreatures'];
    }

    public function setAffectedCreatures(array $affectedCreatures)
    {
        $this->row['ca_affectedCreatures'] = $affectedCreatures;
    }

    public function getAction(): string
    {
        return $this->row['ca_action'];
    }

    public function setAction(string $action)
    {
        $this->row['ca_action'] = $action;
    }


    public function getDamage(): array
    {
        return $this->row['ca_damage'];
    }

    public function setDamage(array $damage)
    {
        $this->row['ca_damage'] = $damage;
    }

    public function getData(): array
    {
        return [
            'actor'             => $this->getActor(),
            'affectedCreatures' => $this->getAffectedCreatures(),
            'damage'            => $this->getDamage(),
        ];
    }

    public function getTableRow(): array
    {
        return [
            'ca_actor'             => $this->getActor(),
            'ca_affectedCreatures' => $this->getAffectedCreatures(),
            'ca_damage'            => $this->getDamage(),
        ];
    }

    public function getRowActions(): array
    {
        return [
            [
                'spanClass' => '',
                'onclick'   => 'playerManager.edit(\'' . $this->getId() . '\')',
                'linkClass' => 'edit',
                'linkText'  => 'Edit',
            ],
        ];
    }

    #endregion
}
