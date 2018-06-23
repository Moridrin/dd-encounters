<?php

namespace dd_encounters\models;

use mp_general\base\Database;
use mp_general\base\models\Model;

if (!defined('ABSPATH')) {
    exit;
}

class Creature extends Model
{
    #region Class
    public static function create(string $name, string $hp, int $initiativeModifier = 0, string $url = ''): ?int
    {
        return parent::_create(['c_name' => $name, 'c_hp' => $hp, 'c_initiativeModifier' => $initiativeModifier, 'c_url' => $url]);
    }

    /**
     * @param string $orderBy
     * @param string $order
     * @param string $key
     *
     * @return Creature[]
     */
    public static function getAll(string $orderBy = 'id', string $order = 'ASC', string $key = 'id'): array
    {
        return parent::_getAll($orderBy, $order, $key);
    }

    /**
     * @param int $id
     *
     * @return Creature
     * @throws \mp_general\exceptions\NotFoundException
     */
    public static function findById(int $id): Model
    {
        return parent::_findById($id);
    }

    /**
     * @param int[]  $ids
     * @param string $orderBy
     * @param string $order
     *
     * @return Creature[]
     */
    public static function findByIds(array $ids, string $orderBy = 'id', string $order = 'ASC'): array
    {
        return parent::_findByIds($ids, $orderBy, $order);
    }

    public static function findByName(string $name): ?Model
    {
        $row = parent::_findRow('c_name = "' . $name . '"');
        if ($row === null) {
            return null;
        } else {
            return new Creature($row);
        }
    }

    public static function deleteByIds(array $ids): bool
    {
        return parent::_deleteByIds($ids);
    }

    public static function getTableColumns(): array
    {
        return [
            'c_name'               => 'Name',
            'c_hp'                 => 'Max HP',
            'c_initiativeModifier' => 'Initiative Modifier',
            'c_url'                => 'URL',
        ];
    }

    public static function getDatabaseTableName(int $blogId = null): string
    {
        return Database::getPrefixForBlog($blogId) . 'dd_creatures';
    }

    protected static function _getDatabaseFields(): array
    {
        return ['`c_name` VARCHAR(50)', '`c_hp` VARCHAR(50) NOT NULL', '`c_initiativeModifier` int(11) NOT NULL DEFAULT 0', '`c_url` VARCHAR(255)'];
    }

    public static function getDatabaseCreateQuery(int $blogId = null): string
    {
        return parent::_getDatabaseCreateQuery($blogId);
    }
    #endregion

    #region Instance

    public function getName(): string
    {
        return $this->row['c_name'];
    }

    public function setName(string $name): self
    {
        $this->row['c_name'] = $name;
        return $this;
    }

    public function getHp(): string
    {
        return $this->row['c_hp'];
    }

    public function getMinHp(): int
    {
        list($diceCount, $diceType, $modifier) = preg_split('/(D|\+)/', $this->getHp());
        return ($diceCount + $modifier);
    }

    public function getMaxHp(): int
    {
        list($diceCount, $diceType, $modifier) = preg_split('/(D|\+)/', $this->getHp());
        return (($diceCount * $diceType) + $modifier);
    }

    public function getGeneratedHp(): int
    {
        list($diceCount, $diceType, $modifier) = preg_split('/(D|\+)/', $this->getHp());
        $generatedHp = 0;
        for ($dice = 0; $dice < $diceCount; ++$dice) {
            $generatedHp += random_int(1, $diceType);
        }
        $generatedHp += $modifier;
        return $generatedHp;
    }

    public function setHp(string $hp): self
    {
        $this->row['c_hp'] = $hp;
        return $this;
    }

    public function getInitiativeModifier(): int
    {
        return $this->row['c_initiativeModifier'] ?? 0;
    }

    public function getMinInitiative(): int
    {
        return 1 + $this->getInitiativeModifier();
    }

    public function getMaxInitiative(): int
    {
        return 20 + $this->getInitiativeModifier();
    }

    public function getGeneratedInitiative(): int
    {
        return random_int(1, 20) + $this->getInitiativeModifier();
    }

    public function setInitiativeModifier(int $initiativeModifier): self
    {
        $this->row['c_initiativeModifier'] = $initiativeModifier;
        return $this;
    }

    public function getUrl(): string
    {
        return $this->row['c_url'] ?? '';
    }

    public function setUrl(string $url): self
    {
        $this->row['c_url'] = $url;
        return $this;
    }

    public function getData(): array
    {
        return [
            'name'               => $this->getName(),
            'hp'                 => $this->getHp(),
            'initiativeModifier' => $this->getInitiativeModifier(),
            'url'                => $this->getUrl(),
        ];
    }

    public function getTableRow(): array
    {
        return [
            'c_name'               => $this->getName(),
            'c_hp'                 => $this->getHp(),
            'c_initiativeModifier' => $this->getInitiativeModifier(),
            'c_url'                => $this->getUrl(),
        ];
    }

    public function getRowActions(): array
    {
        return [
            [
                'spanClass' => '',
                'onclick'   => 'creatureManager.edit(\'' . $this->getId() . '\')',
                'linkClass' => 'edit',
                'linkText'  => 'Edit',
            ],
            [
                'spanClass' => 'trash',
                'onclick'   => 'creatureManager.deleteRow(\'' . $this->getId() . '\')',
                'linkClass' => 'submitdelete',
                'linkText'  => 'Trash',
            ],
        ];
    }

    #endregion
}
