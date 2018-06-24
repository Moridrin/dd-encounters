<?php

namespace dd_encounters\models;

use mp_general\base\Database;
use mp_general\base\models\Model;

if (!defined('ABSPATH')) {
    exit;
}

class Player extends Model implements Creature
{
    #region Class
    public static function create(string $name, int $level, int $hp, ?int $postId, ?int $initiative, ?int $currentHp): ?int
    {
        return parent::_create(['p_name' => $name, 'p_level' => $level, 'p_hp' => $hp, 'p_postId' => $postId, 'p_initiative' => $initiative, 'p_currentHp' => $currentHp]);
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

    /**
     * @param array  $ids
     * @param string $orderBy
     * @param string $order
     * @param string $key
     *
     * @return Player[]
     */
    public static function findByIds(array $ids, string $orderBy = 'id', string $order = 'ASC', string $key = 'id'): array
    {
        return parent::_findByIds($ids, $orderBy, $order, $key);
    }

    public static function deleteByIds(array $ids): bool
    {
        return parent::_deleteByIds($ids);
    }

    public static function getTableColumns(): array
    {
        return [
            'p_name'   => 'Name',
            'p_level'  => 'Level',
            'p_hp'     => 'HP',
            'p_postId' => 'Post ID',
        ];
    }

    public static function getDatabaseTableName(int $blogId = null): string
    {
        return Database::getPrefixForBlog($blogId) . 'dd_players';
    }

    protected static function _getDatabaseFields(): array
    {
        return ['`p_name` VARCHAR(50)', '`p_level` INT NOT NULL', '`p_hp` INT NOT NULL', '`p_postId` INT NULL', '`p_initiative` INT NULL', '`p_currentHp` INT NULL'];
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

    public function getPostId(): ?int
    {
        return $this->row['p_postId'];
    }

    public function setPostId(?int $postId): self
    {
        $this->row['p_postId'] = $postId;
        return $this;
    }

    public function getInitiative(): ?int
    {
        return $this->row['p_initiative'];
    }

    public function setInitiative(?int $initiative): self
    {
        $this->row['p_initiative'] = $initiative;
        return $this;
    }

    public function getCurrentHp(): ?int
    {
        return $this->row['p_currentHp'];
    }

    public function setCurrentHp(?int $currentHp): self
    {
        $this->row['p_currentHp'] = $currentHp;
        return $this;
    }

    public function getData(): array
    {
        return [
            'name'       => $this->getName(),
            'level'      => $this->getLevel(),
            'hp'         => $this->getHp(),
            'postId'     => $this->getPostId(),
            'initiative' => $this->getInitiative(),
            'currentHp'  => $this->getInitiative(),
        ];
    }

    public function getTableRow(): array
    {
        return [
            'p_name'   => $this->row['p_name'],
            'p_level'  => $this->row['p_level'],
            'p_hp'     => $this->row['p_hp'],
            'p_postId' => $this->row['p_postId'],
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
            [
                'spanClass' => '',
                'onclick'   => 'playerManager.clearCombat(\'' . $this->getId() . '\')',
                'linkClass' => 'sleep',
                'linkText'  => 'Clear Combat',
            ],
        ];
    }

    #endregion
}
