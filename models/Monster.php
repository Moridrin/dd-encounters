<?php

namespace dd_encounters\models;

use mp_general\base\Database;
use mp_general\base\models\Model;

if (!defined('ABSPATH')) {
    exit;
}

class Monster extends Model
{
    #region Class
    public static function create(string $name, string $hp, int $initiativeModifier = 0, string $url = ''): ?int
    {
        return parent::_create(['m_name' => $name, 'm_hp' => $hp, 'm_initiativeModifier' => $initiativeModifier, 'm_url' => $url]);
    }

    /**
     * @param string $orderBy
     * @param string $order
     * @param string $key
     *
     * @return Monster[]
     */
    public static function getAll(string $orderBy = 'id', string $order = 'ASC', string $key = 'id'): array
    {
        return parent::_getAll($orderBy, $order, $key);
    }

    /**
     * @param int $id
     *
     * @return Monster
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
     * @return Monster[]
     */
    public static function findByIds(array $ids, string $orderBy = 'id', string $order = 'ASC'): array
    {
        return parent::_findByIds($ids, $orderBy, $order);
    }

    public static function findByName(string $name): ?Model
    {
        $row = parent::_findRow('m_name = "' . $name . '"');
        if ($row === null) {
            return null;
        } else {
            return new Monster($row);
        }
    }

    public static function deleteByIds(array $ids): bool
    {
        return parent::_deleteByIds($ids);
    }

    public static function getTableColumns(): array
    {
        return [
            'm_name'               => 'Name',
            'm_hp'                 => 'Max HP',
            'm_initiativeModifier' => 'Initiative Modifier',
            'm_url'                => 'URL',
        ];
    }

    public static function getDatabaseTableName(int $blogId = null): string
    {
        return Database::getPrefixForBlog($blogId) . 'dd_monsters';
    }

    protected static function _getDatabaseFields(): array
    {
        return ['`m_name` VARCHAR(50)', '`m_hp` VARCHAR(50) NOT NULL', '`m_initiativeModifier` int(11) NOT NULL DEFAULT 0', '`m_url` VARCHAR(255)'];
    }

    public static function getDatabaseCreateQuery(int $blogId = null): string
    {
        return parent::_getDatabaseCreateQuery($blogId);
    }
    #endregion

    #region Instance

    private $diceCount;
    private $diceType;
    private $modifier;

    protected function __init()
    {
        $this->setHp($this->row['m_hp']);
    }

    public function getName(): string
    {
        return $this->row['m_name'];
    }

    public function setName(string $name): self
    {
        $this->row['m_name'] = $name;
        return $this;
    }

    public function getHp(): string
    {
        return $this->row['m_hp'];
    }

    public function getMinHp(): int
    {
        return ($this->diceCount + $this->modifier);
    }

    public function getMaxHp(): int
    {
        return (($this->diceCount * $this->diceType) + $this->modifier);
    }

    public function getGeneratedHp(): int
    {
        $generatedHp = 0;
        for ($dice = 0; $dice < $this->diceCount; ++$dice) {
            $generatedHp += random_int(1, $this->diceType);
        }
        $generatedHp += $this->modifier;
        return $generatedHp;
    }

    public function setHp(string $hp): self
    {
        $this->row['m_hp'] = $hp;
        list($diceCount, $diceType, $modifier) = preg_split('/(D|\+)/', $hp);
        $this->diceCount = (int)$diceCount;
        $this->diceType = (int)$diceType;
        $this->modifier = intval($modifier);
        return $this;
    }

    public function getInitiativeModifier(): int
    {
        return $this->row['m_initiativeModifier'] ?? 0;
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
        $this->row['m_initiativeModifier'] = $initiativeModifier;
        return $this;
    }

    public function getUrl(): string
    {
        return $this->row['m_url'] ?? '';
    }

    public function setUrl(string $url): self
    {
        $this->row['m_url'] = $url;
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
            'm_name'               => $this->getName(),
            'm_hp'                 => $this->getHp(),
            'm_initiativeModifier' => $this->getInitiativeModifier(),
            'm_url'                => $this->getUrl(),
        ];
    }

    public function getRowActions(): array
    {
        return [
            [
                'spanClass' => '',
                'onclick'   => 'monsterManager.edit(\'' . $this->getId() . '\')',
                'linkClass' => 'edit',
                'linkText'  => 'Edit',
            ],
            [
                'spanClass' => 'trash',
                'onclick'   => 'monsterManager.deleteRow(\'' . $this->getId() . '\')',
                'linkClass' => 'submitdelete',
                'linkText'  => 'Trash',
            ],
        ];
    }

    #endregion
}
