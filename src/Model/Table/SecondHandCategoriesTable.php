<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * SecondHandCategories Model: categories of the second-hand items
 *
 * @property \App\Model\Table\SecondHandItemsTable&\Cake\ORM\Association\HasMany $SecondHandItems
 */
class SecondHandCategoriesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('second_hand_categories');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->hasMany('SecondHandItems', [
            'foreignKey' => 'category_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('name')
            ->maxLength('name', 100)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        return $validator;
    }

    /**
     * Category of at least one item: it cannot be deleted
     */
    public function isUsed(int $id): bool
    {
        return $this->SecondHandItems->exists(['category_id' => $id]);
    }

    /**
     * @return array<int, string> name by id
     */
    public function listByName(): array
    {
        return $this->find('list')->orderBy(['name' => 'ASC'])->toArray();
    }
}
