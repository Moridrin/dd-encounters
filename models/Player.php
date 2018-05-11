<?php

namespace dd_encounters\models;

use mp_general\base\Database;
use mp_general\base\models\Model;

if (!defined('ABSPATH')) {
    exit;
}

class Player extends Model
{
    #region Class
    public static function create(string $name, int $level, int $hp): ?int
    {
        return parent::_create(['p_name' => $name, 'p_level' => $level, 'p_hp' => $hp]);
    }

    /**
     * @param string $orderBy
     * @param string $order
     * @param string $key
     * @return Player[]
     */
    public static function getAll(string $orderBy = 'id', string $order = 'ASC', string $key = 'id'): array
    {
        return parent::_getAll($orderBy, $order, $key);
    }

    /**
     * @param int $id
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

    public static function deleteByIds(array $ids): bool
    {
        return parent::_deleteByIds($ids);
    }

    public static function getTableColumns(): array
    {
        return [
            'p_name'  => 'Name',
            'p_level' => 'Level',
            'p_hp'    => 'HP',
        ];
    }

    public static function getDatabaseTableName(int $blogId = null): string
    {
        return Database::getPrefixForBlog($blogId) . 'dd_players';
    }

    protected static function _getDatabaseFields(): array
    {
        return ['`p_name` VARCHAR(50)', '`p_level` INT NOT NULL', '`p_hp` INT NOT NULL'];
    }

    public static function getDatabaseCreateQuery(int $blogId = null): string
    {
        return parent::_getDatabaseCreateQuery($blogId);
    }
    #endregion

    #region Instance

    public function getName(): string
    {
        return $this->row['p_name'];
    }

    public function setName(string $name): self
    {
        $this->row['p_name'] = $name;
        return $this;
    }

    public function getLevel(): int
    {
        return $this->row['p_level'];
    }

    public function setLevel(int $level): self
    {
        $this->row['p_level'] = $level;
        return $this;
    }

    public function getHp(): int
    {
        return $this->row['p_hp'];
    }

    public function setHp(int $hp): self
    {
        $this->row['p_hp'] = $hp;
        return $this;
    }

    public function getData(): array
    {
        return [
            'name'  => $this->getName(),
            'level' => $this->getLevel(),
            'hp'    => $this->getHp(),
        ];
    }

    public function getTableRow(): array
    {
        return [
            'p_name'  => $this->row['p_name'],
            'p_level' => $this->row['p_level'],
            'p_hp'    => $this->row['p_hp'],
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
