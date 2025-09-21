<?php
declare(strict_types=1);
namespace App\Model\Table;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Roles Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\HasMany $Users
 *
 * @method \App\Model\Entity\Role newEmptyEntity()
 * @method \App\Model\Entity\Role newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Role[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Role get($primaryKey,array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, ...$args)
 * @method \App\Model\Entity\Role findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Role patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Role[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Role|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Role saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Role[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Role[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Role[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Role[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class RolesTable extends Table
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

    $this->setTable('roles');
    $this->setDisplayField('name');
    $this->setPrimaryKey('id');

    $this->hasMany('Users', [
        'foreignKey' => 'role_id',
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
      ->nonNegativeInteger('id')
      ->allowEmptyString('id', null, 'create');

    $validator
      ->scalar('name')
      ->maxLength('name', 50)
      ->requirePresence('name', 'create')
      ->notEmptyString('name');

    $validator
      ->boolean('MemberViewAll')
      ->requirePresence('MemberViewAll', 'create')
      ->notEmptyString('MemberViewAll');

    $validator
      ->boolean('MemberEditAll')
      ->requirePresence('MemberEditAll', 'create')
      ->notEmptyString('MemberEditAll');

    $validator
      ->boolean('MemberEditOwn')
      ->requirePresence('MemberEditOwn', 'create')
      ->notEmptyString('MemberEditOwn');

    $validator
      ->boolean('BillViewAll')
      ->requirePresence('BillViewAll', 'create')
      ->notEmptyString('BillViewAll');

    $validator
      ->boolean('BillEditAll')
      ->requirePresence('BillEditAll', 'create')
      ->notEmptyString('BillEditAll');

    $validator
      ->boolean('BillValidate')
      ->requirePresence('BillValidate', 'create')
      ->notEmptyString('BillValidate');

    $validator
      ->boolean('Editor')
      ->requirePresence('Editor', 'create')
      ->notEmptyString('Editor');

    
    return $validator;
  }

}