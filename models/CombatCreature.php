<?php

namespace dd_encounters\models;

use mp_general\base\Database;
use mp_general\base\models\Model;
use mp_general\exceptions\NotFoundException;

if (!defined('ABSPATH')) {
    exit;
}

class CombatCreature extends Model
{
    #region Class
    public static function create(int $encounterId, int $creatureId, string $name, int $maxHp, int $currentHp, int $initiative): ?int
    {
        return parent::_create(['cc_encounterId' => $encounterId, 'cc_creatureId' => $creatureId, 'cc_name' => $name, 'cc_maxHp' => $maxHp, 'cc_currentHp' => $currentHp, 'cc_initiative' => $initiative]);
    }

    /**
     * @param string $orderBy
     * @param string $order
     * @param string $key
     *
     * @return CombatCreature[]
     */
    public static function getAll(string $orderBy = 'id', string $order = 'ASC', string $key = 'id'): array
    {
        return parent::_getAll($orderBy, $order, $key);
    }

    /**
     * @param int $id
     *
     * @return CombatCreature
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
     * @return CombatAction[]
     */
    public static function findByEncounterId(int $encounterId, string $orderBy = 'id', string $order = 'ASC'): array
    {
        return parent::_find('cc_encounterId = ' . $encounterId, $orderBy, $order);
    }

    public static function deleteByIds(array $ids): bool
    {
        return parent::_deleteByIds($ids);
    }

    public static function getTableColumns(): array
    {
        return [
            'cc_name'       => 'Name',
            'cc_maxHp'      => 'MaxHp',
            'cc_currentHp'  => 'CurrentHp',
            'cc_initiative' => 'Initiative',
        ];
    }

    public static function getDatabaseTableName(int $blogId = null): string
    {
        return Database::getPrefixForBlog($blogId) . 'combat_creature';
    }

    protected static function _getDatabaseFields(): array
    {
        return [
            '`cc_encounterId` BIGINT(20) NOT NULL',
            '`cc_creatureId` BIGINT(20) NOT NULL',
            '`cc_name` VARCHAR(50) NOT NULL',
            '`cc_maxHp` int NOT NULL',
            '`cc_currentHp` int NOT NULL',
            '`cc_initiative` int NOT NULL',
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
        return $this->row['cc_encounterId'];
    }

    public function setEncounterId(int $encounterId): self
    {
        $this->row['cc_encounterId'] = $encounterId;
        return $this;
    }

    public function getCreatureId(): int
    {
        return $this->row['cc_creatureId'];
    }

    public function setCreatureId(int $creatureId): self
    {
        $this->row['cc_creatureId'] = $creatureId;
        return $this;
    }

    public function getName(): string
    {
        return $this->row['cc_name'];
    }

    public function setName(string $name): self
    {
        $this->row['cc_name'] = $name;
        return $this;
    }

    public function getMaxHp(): int
    {
        return $this->row['cc_maxHp'];
    }

    public function setMaxHp(int $maxHp): self
    {
        $this->row['cc_maxHp'] = $maxHp;
        return $this;
    }

    public function getCurrentHp(): int
    {
        return $this->row['cc_currentHp'];
    }

    public function setCurrentHp(int $currentHp): self
    {
        $this->row['cc_currentHp'] = $currentHp;
        return $this;
    }

    public function getInitiative(): int
    {
        return $this->row['cc_initiative'];
    }

    public function setInitiative(int $initiative): self
    {
        $this->row['cc_initiative'] = $initiative;
        return $this;
    }

    #endregion

    public function getData(): array
    {
        return [
            'encounterId' => $this->getEncounterId(),
            'creatureId'  => $this->getCreatureId(),
            'name'        => $this->getName(),
            'maxHp'       => $this->getMaxHp(),
            'currentHp'   => $this->getCurrentHp(),
            'initiative'  => $this->getInitiative(),
        ];
    }

    public function getTableRow(): array
    {
        return [
            'cc_encounterId' => $this->getEncounterId(),
            'cc_creatureId'  => $this->getCreatureId(),
            'cc_name'        => $this->getName(),
            'cc_maxHp'       => $this->getMaxHp(),
            'cc_currentHp'   => $this->getCurrentHp(),
            'cc_initiative'  => $this->getInitiative(),
        ];
    }

    public function getRowActions(): array
    {
        return [
        ];
    }
    #endregion
}
