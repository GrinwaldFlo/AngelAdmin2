<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * MemberOrders Model
 *
 * @property \App\Model\Table\ShopItemsTable&\Cake\ORM\Association\BelongsTo $ShopItems
 * @property \App\Model\Table\MembersTable&\Cake\ORM\Association\BelongsTo $Members
 * @property \App\Model\Table\BillsTable&\Cake\ORM\Association\BelongsTo $Bills
 *
 * @method \App\Model\Entity\MemberOrder newEmptyEntity()
 * @method \App\Model\Entity\MemberOrder newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\MemberOrder[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\MemberOrder get($primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, ...$args)
 * @method \App\Model\Entity\MemberOrder findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\MemberOrder patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\MemberOrder[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\MemberOrder|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\MemberOrder saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\MemberOrder[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\MemberOrder[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\MemberOrder[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\MemberOrder[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class MemberOrdersTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('member_orders');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('ShopItems', [
            'foreignKey' => 'shop_item_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Members', [
            'foreignKey' => 'member_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Bills', [
            'foreignKey' => 'bill_id',
            'joinType' => 'LEFT',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('shop_item_id')
            ->notEmptyString('shop_item_id');

        $validator
            ->integer('member_id')
            ->notEmptyString('member_id');

        $validator
            ->integer('bill_id')
            ->allowEmptyString('bill_id');

        $validator
            ->integer('quantity')
            ->requirePresence('quantity', 'create')
            ->notEmptyString('quantity')
            ->greaterThan('quantity', 0);

        $validator
            ->boolean('delivered')
            ->notEmptyString('delivered');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn('shop_item_id', 'ShopItems'), ['errorField' => 'shop_item_id']);
        $rules->add($rules->existsIn('member_id', 'Members'), ['errorField' => 'member_id']);
        $rules->add($rules->existsIn('bill_id', 'Bills'), ['errorField' => 'bill_id']);

        return $rules;
    }
}
