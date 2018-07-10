<?php

namespace dd_encounters\models;

use mp_general\base\Database;
use mp_general\base\models\Model;
use mp_general\exceptions\NotFoundException;

if (!defined('ABSPATH')) {
    exit;
}

class CombatMonster extends Model implements Creature
{
    #region Class
    public static function create(int $encounterId, int $monsterId, string $name, int $maxHp, int $currentHp, int $initiative): ?int
    {
        return parent::_create(['cm_encounterId' => $encounterId, 'cm_monsterId' => $monsterId, 'cm_name' => $name, 'cm_maxHp' => $maxHp, 'cm_currentHp' => $currentHp, 'cm_initiative' => $initiative]);
    }

    /**
     * @param string $orderBy
     * @param string $order
     * @param string $key
     *
     * @return CombatMonster[]
     */
    public static function getAll(string $orderBy = 'id', string $order = 'ASC', string $key = 'id'): array
    {
        return parent::_getAll($orderBy, $order, $key);
    }

    /**
     * @param int $id
     *
     * @return CombatMonster
     * @throws NotFoundException
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
     * @return CombatMonster[]
     */
    public static function findByEncounterId(int $encounterId, string $orderBy = 'id', string $order = 'ASC'): array
    {
        return parent::_find('cm_encounterId = ' . $encounterId, $orderBy, $order);
    }

    public static function deleteByIds(array $ids): bool
    {
        return parent::_deleteByIds($ids);
    }

    public static function getTableColumns(): array
    {
        return [
            'cm_name'       => 'Name',
            'cm_maxHp'      => 'MaxHp',
            'cm_currentHp'  => 'CurrentHp',
            'cm_initiative' => 'Initiative',
        ];
    }

    public static function getDatabaseTableName(int $blogId = null): string
    {
        return Database::getPrefixForBlog($blogId) . 'dd_combat_monster';
    }

    protected static function _getDatabaseFields(): array
    {
        return [
            '`cm_encounterId` BIGINT(20) NOT NULL',
            '`cm_monsterId` BIGINT(20) NOT NULL',
            '`cm_name` VARCHAR(50) NOT NULL',
            '`cm_maxHp` int NOT NULL',
            '`cm_currentHp` int NOT NULL',
            '`cm_initiative` int NOT NULL',
        ];
    }

    public static function getDatabaseCreateQuery(int $blogId = null): string
    {
        return parent::_getDatabaseCreateQuery($blogId);
    }
    #endregion

    #region Instance
    #region Getters & Setters
    public function getEncounterId(): int
    {
        return $this->row['cm_encounterId'];
    }

    public function setEncounterId(int $encounterId): self
    {
        $this->row['cm_encounterId'] = $encounterId;
        return $this;
    }

    public function getMonsterId(): int
    {
        return $this->row['cm_monsterId'];
    }

    public function setMonsterId(int $monsterId): self
    {
        $this->row['cm_monsterId'] = $monsterId;
        return $this;
    }

    public function getName(): string
    {
        return $this->row['cm_name'];
    }

    public function setName(string $name): self
    {
        $this->row['cm_name'] = $name;
        return $this;
    }

    public function getMaxHp(): int
    {
        return $this->row['cm_maxHp'];
    }

    public function setMaxHp(int $maxHp): self
    {
        $this->row['cm_maxHp'] = $maxHp;
        return $this;
    }

    public function getCurrentHp(): int
    {
        return $this->row['cm_currentHp'];
    }

    public function setCurrentHp(int $currentHp): self
    {
        $this->row['cm_currentHp'] = $currentHp;
        return $this;
    }

    public function addDamage(int $damage): bool
    {
        $this->setCurrentHp($this->getCurrentHp() - $damage);
        if ($this->getCurrentHp() < 0) {
            $this->setCurrentHp(0);
            return true;
        }
        return false;
    }

    public function getInitiative(): int
    {
        return $this->row['cm_initiative'];
    }

    public function setInitiative(int $initiative): self
    {
        $this->row['cm_initiative'] = $initiative;
        return $this;
    }

    #endregion

    public function getData(): array
    {
        return [
            'encounterId' => $this->getEncounterId(),
            'monsterId'   => $this->getMonsterId(),
            'name'        => $this->getName(),
            'maxHp'       => $this->getMaxHp(),
            'currentHp'   => $this->getCurrentHp(),
            'initiative'  => $this->getInitiative(),
        ];
    }

    public function getTableRow(): array
    {
        return [
            'cm_encounterId' => $this->getEncounterId(),
            'cm_monsterId'   => $this->getMonsterId(),
            'cm_name'        => $this->getName(),
            'cm_maxHp'       => $this->getMaxHp(),
            'cm_currentHp'   => $this->getCurrentHp(),
            'cm_initiative'  => $this->getInitiative(),
        ];
    }

    public function getRowActions(): array
    {
        return [
        ];
    }
    #endregion
}
