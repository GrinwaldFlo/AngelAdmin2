<?php
declare(strict_types=1);
namespace App\Model\Table;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * FieldTypes Model
 *
 * @property \App\Model\Table\FieldsTable&\Cake\ORM\Association\HasMany $Fields
 *
 * @method \App\Model\Entity\FieldType newEmptyEntity()
 * @method \App\Model\Entity\FieldType newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\FieldType[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\FieldType get($primaryKey,array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, ...$args)
 * @method \App\Model\Entity\FieldType findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\FieldType patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\FieldType[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\FieldType|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\FieldType saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\FieldType[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\FieldType[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\FieldType[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\FieldType[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class FieldTypesTable extends Table
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

    $this->setTable('field_types');
    $this->setDisplayField('id');
    $this->setPrimaryKey('id');

    $this->hasMany('Fields', [
        'foreignKey' => 'field_type_id',
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
      ->integer('id')
      ->allowEmptyString('id', null, 'create');

    $validator
      ->scalar('label')
      ->maxLength('label', 100)
      ->requirePresence('label', 'create')
      ->notEmptyString('label');

    $validator
      ->integer('style')
      ->notEmptyString('style');

    $validator
      ->integer('member_edit')
      ->notEmptyString('member_edit');

    $validator
      ->integer('hidden')
      ->notEmptyString('hidden');

    $validator
      ->integer('mandatory')
      ->notEmptyString('mandatory');

    return $validator;
  }

}