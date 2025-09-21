<?php
declare(strict_types=1);
namespace App\Model\Table;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Sites Model
 *
 * @method \App\Model\Entity\Site newEmptyEntity()
 * @method \App\Model\Entity\Site newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Site[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Site get($primaryKey,array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, ...$args)
 * @method \App\Model\Entity\Site findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Site patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Site[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Site|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Site saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Site[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Site[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Site[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Site[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class SitesTable extends Table
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

    $this->setTable('sites');
    $this->setDisplayField('city');
    $this->setPrimaryKey('id');
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
      ->scalar('city')
      ->maxLength('city', 100)
      ->requirePresence('city', 'create')
      ->notEmptyString('city');

    $validator
      ->scalar('address')
      ->maxLength('address', 100)
      ->allowEmptyString('address');

    $validator
      ->scalar('account_designation')
      ->maxLength('account_designation', 100)
      ->allowEmptyString('account_designation');

    $validator
      ->scalar('postcode')
      ->maxLength('postcode', 100)
      ->allowEmptyString('postcode');

    $validator
      ->scalar('iban')
      ->maxLength('iban', 100)
      ->allowEmptyString('iban');

    $validator
      ->scalar('bic')
      ->maxLength('bic', 100)
      ->allowEmptyString('bic');

    $validator
      ->integer('feeMax')
      ->requirePresence('feeMax', 'create')
      ->notEmptyString('feeMax');

    $validator
      ->integer('reminder_penalty')
      ->notEmptyString('reminder_penalty');

    $validator
      ->scalar('sender_email')
      ->maxLength('sender_email', 100)
      ->requirePresence('sender_email', 'create')
      ->notEmptyString('sender_email');

    $validator
      ->scalar('sender')
      ->maxLength('sender', 100)
      ->requirePresence('sender', 'create')
      ->notEmptyString('sender');

    $validator
      ->scalar('sender_phone')
      ->maxLength('sender_phone', 100)
      ->allowEmptyString('sender_phone');

    $validator
      ->integer('add_invoice_num')
      ->requirePresence('add_invoice_num', 'create')
      ->notEmptyString('add_invoice_num');

    return $validator;
  }

}