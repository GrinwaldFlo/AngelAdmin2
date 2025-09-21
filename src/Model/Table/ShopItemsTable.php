<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ShopItems Model
 *
 * @property \App\Model\Table\MemberOrdersTable&\Cake\ORM\Association\HasMany $MemberOrders
 *
 * @method \App\Model\Entity\ShopItem newEmptyEntity()
 * @method \App\Model\Entity\ShopItem newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\ShopItem[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ShopItem get($primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, ...$args)
 * @method \App\Model\Entity\ShopItem findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\ShopItem patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\ShopItem[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ShopItem|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ShopItem saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ShopItem[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\ShopItem[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\ShopItem[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\ShopItem[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ShopItemsTable extends Table
{
    // Category constants
    public const CATEGORY_OTHER = 1;
    public const CATEGORY_TRAVEL = 2;
    public const CATEGORY_EQUIPMENT = 3;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('shop_items');
        $this->setDisplayField('label');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('MemberOrders', [
            'foreignKey' => 'shop_item_id',
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
            ->scalar('label')
            ->maxLength('label', 255)
            ->requirePresence('label', 'create')
            ->notEmptyString('label');

        $validator
            ->decimal('price')
            ->requirePresence('price', 'create')
            ->notEmptyString('price')
            ->greaterThan('price', 0);

        $validator
            ->integer('category')
            ->requirePresence('category', 'create')
            ->notEmptyString('category')
            ->inList('category', [self::CATEGORY_OTHER, self::CATEGORY_TRAVEL, self::CATEGORY_EQUIPMENT], __('Please select a valid category.'));

        $validator
            ->boolean('active')
            ->notEmptyString('active');

        return $validator;
    }

    /**
     * Get category options for forms
     *
     * @return array
     */
    public static function getCategoryOptions(): array
    {
        return [
            self::CATEGORY_OTHER => __('Other'),
            self::CATEGORY_TRAVEL => __('Travel'),
            self::CATEGORY_EQUIPMENT => __('Equipment'),
        ];
    }

    /**
     * Get category label by ID
     *
     * @param int $categoryId
     * @return string
     */
    public static function getCategoryLabel(int $categoryId): string
    {
        $options = self::getCategoryOptions();
        return $options[$categoryId] ?? __('Unknown');
    }

    /**
     * Find active shop items
     */
    public function findActive(Query $query, array $options): Query
    {
        return $query->where(['active' => true]);
    }

    /**
     * Find orders by payment status
     */
    public function findOrdersByPaymentStatus(Query $query, array $options): Query
    {
        $query = $query->contain(['MemberOrders', 'MemberOrders.Bills']);
        
        if (isset($options['paid']) && $options['paid'] === true) {
            $query->innerJoinWith('MemberOrders.Bills')->where(['Bills.paid' => 1]);
        } elseif (isset($options['paid']) && $options['paid'] === false) {
            $query->leftJoinWith('MemberOrders.Bills')->where([
                'OR' => [
                    'Bills.paid' => 0,
                    'Bills.id IS' => null
                ]
            ]);
        }
        
        return $query;
    }
}
