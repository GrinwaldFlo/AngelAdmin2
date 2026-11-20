<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\SecondHandItem;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * SecondHandItems Model: second-hand items for sale
 *
 * @property \App\Model\Table\SecondHandProfilesTable&\Cake\ORM\Association\BelongsTo $Profiles
 * @property \App\Model\Table\SecondHandCategoriesTable&\Cake\ORM\Association\BelongsTo $Categories
 */
class SecondHandItemsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('second_hand_items');
        $this->setDisplayField('description');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Profiles', [
            'className' => 'SecondHandProfiles',
            'foreignKey' => 'profile_id',
            'propertyName' => 'profile',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Categories', [
            'className' => 'SecondHandCategories',
            'foreignKey' => 'category_id',
            'propertyName' => 'category',
            'joinType' => 'INNER',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('category_id')
            ->requirePresence('category_id', 'create')
            ->notEmptyString('category_id');

        $validator
            ->scalar('description')
            ->maxLength('description', 2000)
            ->requirePresence('description', 'create')
            ->notEmptyString('description');

        $validator
            ->scalar('size')
            ->maxLength('size', 50)
            ->requirePresence('size', 'create')
            ->notEmptyString('size');

        $validator
            ->decimal('price')
            ->requirePresence('price', 'create')
            ->notEmptyString('price')
            ->greaterThanOrEqual('price', 0)
            ->lessThan('price', 1000000);

        return $validator;
    }

    public function buildRules(\Cake\ORM\RulesChecker $rules): \Cake\ORM\RulesChecker
    {
        $rules->add($rules->existsIn(['category_id'], 'Categories'), ['errorField' => 'category_id']);

        return $rules;
    }

    /**
     * New items get the hash naming their picture folder
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, \ArrayObject $options): void
    {
        /** @var \App\Model\Entity\SecondHandItem $entity */
        if ($entity->isNew()) {
            $entity->ensureHash();
        }
    }

    /**
     * The picture is deleted with the item
     */
    public function afterDelete(EventInterface $event, EntityInterface $entity, \ArrayObject $options): void
    {
        /** @var \App\Model\Entity\SecondHandItem $entity */
        $entity->deleteFolder();
    }

    /**
     * Items shown to the buyers: not sold, not expired, of a seller with a verified email
     * @param int $months expiration duration
     */
    public function findForSale(SelectQuery $query, int $months): SelectQuery
    {
        return $query
            ->contain(['Profiles', 'Categories'])
            ->where([
                'SecondHandItems.sold IS' => null,
                'SecondHandItems.created >=' => SecondHandItem::expiryLimit($months),
                'Profiles.email_verified' => true,
            ])
            ->orderBy(['SecondHandItems.created' => 'DESC', 'SecondHandItems.id' => 'DESC']);
    }

    /**
     * Previous and next items for sale around an item, in the order of the list
     * @param int $months expiration duration
     * @param int $categoryId 0 = all categories
     * @return array{prev: int|null, next: int|null} null when there is none (or the item is not in the list)
     */
    public function neighbours(SecondHandItem $item, int $months, int $categoryId = 0): array
    {
        $inList = $this->forSaleIds($months, $categoryId)->where(['SecondHandItems.id' => $item->id])->first();
        if ($inList === null) {
            return ['prev' => null, 'next' => null];
        }

        return [
            'prev' => $this->adjacentId($this->forSaleIds($months, $categoryId), $item, '>', 'ASC'),
            'next' => $this->adjacentId($this->forSaleIds($months, $categoryId), $item, '<', 'DESC'),
        ];
    }

    private function forSaleIds(int $months, int $categoryId): SelectQuery
    {
        $query = $this->unhydratedFind('forSale', months: $months)->select(['SecondHandItems.id']);
        if ($categoryId > 0) {
            $query->where(['SecondHandItems.category_id' => $categoryId]);
        }

        return $query;
    }

    /**
     * Closest item on one side of an item, the list being ordered by date then id
     * @param string $operator '>' for the newer items, '<' for the older ones
     * @param string $direction 'ASC' from the closest newer item, 'DESC' from the closest older one
     */
    private function adjacentId(SelectQuery $query, SecondHandItem $item, string $operator, string $direction): ?int
    {
        $row = $query
            ->where(['OR' => [
                "SecondHandItems.created $operator" => $item->created,
                'AND' => ['SecondHandItems.created' => $item->created, "SecondHandItems.id $operator" => $item->id],
            ]])
            ->orderBy(['SecondHandItems.created' => $direction, 'SecondHandItems.id' => $direction], true)
            ->first();

        return $row === null ? null : (int)$row['id'];
    }

    /**
     * Items of a seller, the ones for sale first
     */
    public function findOfProfile(SelectQuery $query, int $profileId): SelectQuery
    {
        return $query
            ->contain(['Categories'])
            ->where(['SecondHandItems.profile_id' => $profileId])
            ->orderBy(['SecondHandItems.sold IS NULL' => 'DESC', 'SecondHandItems.created' => 'DESC']);
    }
}
