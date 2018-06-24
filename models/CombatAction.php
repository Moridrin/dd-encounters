<?php

namespace dd_encounters\models;

use mp_general\base\Database;
use mp_general\base\models\Model;

if (!defined('ABSPATH')) {
    exit;
}

class CombatAction extends Model
{
    #region Class
    public static function create(int $encounterId, string $actor, array $affectedMonsters, ?string $action, int $damage): ?int
    {
        return parent::_create(['ca_encounterId' => $encounterId, 'ca_actor' => $actor, 'ca_affectedMonsters' => json_encode($affectedMonsters), 'ca_action' => $action, 'ca_damage' => $damage]);
    }

    /**
     * @param string $orderBy
     * @param string $order
     * @param string $key
     *
     * @return Player[]
     */
    public static function getAll(string $orderBy = 'id', string $order = 'ASC', string $key = 'id'): array
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

    public static function findByIds(array $ids, string $orderBy = 'id', string $order = 'ASC'): array
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
    public static function findByEncounterId(int $encounterId, string $orderBy = 'id', string $order = 'ASC'): array
    {
        return parent::_find('ca_encounterId = ' . $encounterId, $orderBy, $order);
    }

    /**
     * @param int    $encounterId
     * @param string $affectedMonster
     * @param string $orderBy
     * @param string $order
     *
     * @return CombatAction[]
     */
    public static function findByEncounterIdAndAffectedMonster(int $encounterId, string $affectedMonster, string $orderBy = 'id', string $order = 'ASC'): array
    {
        return parent::_find('ca_encounterId = ' . $encounterId . ' AND JSON_SEARCH(ca_affectedMonsters, "all", "' . $affectedMonster . '") IS NOT NULL', $orderBy, $order);
    }

    public static function deleteByIds(array $ids): bool
    {
        return parent::_deleteByIds($ids);
    }

    public static function getTableColumns(): array
    {
        return [
            'ca_actor'            => 'Actor',
            'ca_affectedMonsters' => 'Affected Monsters',
            'ca_action'           => 'Action',
            'ca_damage'           => 'Damage',
        ];
    }

    public static function getDatabaseTableName(int $blogId = null): string
    {
        return Database::getPrefixForBlog($blogId) . 'dd_encounter_actions';
    }

    protected static function _getDatabaseFields(): array
    {
        return ['`ca_encounterId` INT NOT NULL', '`ca_actor` VARCHAR(50) NOT NULL', '`ca_affectedMonsters` TEXT NOT NULL', '`ca_action` VARCHAR(255)', '`ca_damage` INT NOT NULL'];
    }

    public static function getDatabaseCreateQuery(int $blogId = null): string
    {
        return parent::_getDatabaseCreateQuery($blogId);
    }
    #endregion

    #region Instance
    public function __init(): void
    {
        $this->row['ca_affectedMonsters'] = json_decode($this->row['ca_affectedMonsters'], true);
    }

    public function getActor(): string
    {
        return $this->row['ca_actor'];
    }

    public function setActor(string $actor)
    {
        $this->row['ca_actor'] = $actor;
    }

    public function getAffectedMonsters(): array
    {
        return $this->row['ca_affectedMonsters'];
    }

    public function setAffectedMonsters(array $affectedMonsters)
    {
        $this->row['ca_affectedMonsters'] = $affectedMonsters;
    }

    public function getAction(): string
    {
        return $this->row['ca_action'];
    }

    public function setAction(string $action)
    {
        $this->row['ca_action'] = $action;
    }


    public function getDamage(): int
    {
        return $this->row['ca_damage'];
    }

    public function setDamage(int $damage)
    {
        $this->row['ca_damage'] = $damage;
    }

    public function getData(): array
    {
        return [
            'actor'            => $this->getActor(),
            'affectedMonsters' => $this->getAffectedMonsters(),
            'damage'           => $this->getDamage(),
        ];
    }

    public function getTableRow(): array
    {
        return [
            'ca_actor'            => $this->getActor(),
            'ca_affectedMonsters' => $this->getAffectedMonsters(),
            'ca_damage'           => $this->getDamage(),
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
            [
                'spanClass' => 'trash',
                'onclick'   => 'playerManager.deleteRow(\'' . $this->getId() . '\')',
                'linkClass' => 'submitdelete',
                'linkText'  => 'Trash',
            ],
        ];
    }

    #endregion
}
