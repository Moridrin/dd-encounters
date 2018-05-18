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
    public static function create(string $name, int $level, int $hp): ?int
    {
        return parent::_create(['c_name' => $name, 'c_maxHp' => $level, 'c_url' => $hp]);
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

    public static function findByIds(array $ids, string $orderBy = 'id', string $order = 'ASC'): array
    {
        return parent::_findByIds($ids, $orderBy, $order);
    }

    public static function deleteByIds(array $ids): bool
    {
        return parent::_deleteByIds($ids);
    }

    public static function getTableColumns(): array
    {
        return [
            'c_name'  => 'Name',
            'c_maxHp' => 'Level',
            'c_url'   => 'HP',
        ];
    }

    public static function getDatabaseTableName(int $blogId = null): string
    {
        return Database::getPrefixForBlog($blogId) . 'dd_creatures';
    }

    protected static function _getDatabaseFields(): array
    {
        return ['`c_name` VARCHAR(50)', '`c_maxHp` VARCHAR(7) NOT NULL', '`c_url` VARCHAR(255)'];
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

    public function getMaxHp(): int
    {
        return $this->row['c_maxHp'];
    }

    public function setMaxHp(int $level): self
    {
        $this->row['c_maxHp'] = $level;
        return $this;
    }

    public function getUrl(): int
    {
        return $this->row['c_url'];
    }

    public function setUrl(int $hp): self
    {
        $this->row['c_url'] = $hp;
        return $this;
    }

    public function getData(): array
    {
        return [
            'name'  => $this->getName(),
            'maxHp' => $this->getMaxHp(),
            'url'   => $this->getUrl(),
        ];
    }

    public function getTableRow(): array
    {
        return [
            'c_name'  => $this->row['c_name'],
            'c_maxHp' => $this->row['c_maxHp'],
            'c_url'   => $this->row['c_url'],
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
