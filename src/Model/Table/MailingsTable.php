<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Mailings Model
 *
 * @property \App\Model\Table\MailingItemsTable&\Cake\ORM\Association\HasMany $MailingItems
 *
 * @method \App\Model\Entity\Mailing newEmptyEntity()
 * @method \App\Model\Entity\Mailing newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Mailing[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Mailing get($primaryKey,array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, ...$args)
 * @method \App\Model\Entity\Mailing findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Mailing patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Mailing[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Mailing|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Mailing saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Mailing[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Mailing[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Mailing[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Mailing[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class MailingsTable extends Table
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

        $this->setTable('mailings');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->hasMany('MailingItems', [
            'foreignKey' => 'mailing_id',
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
            ->scalar('title')
            ->maxLength('title', 100)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->scalar('content')
            ->requirePresence('content', 'create')
            ->notEmptyString('content');

        $validator
            ->scalar('attachement1')
            ->maxLength('attachement1', 255)
            ->requirePresence('attachement1', 'create')
            ->allowEmptyString('attachement1');

        $validator
            ->scalar('attachement2')
            ->maxLength('attachement2', 255)
            ->requirePresence('attachement2', 'create')
            ->allowEmptyString('attachement2');

        $validator
            ->scalar('attachement3')
            ->maxLength('attachement3', 255)
            ->requirePresence('attachement3', 'create')
            ->allowEmptyString('attachement3');

        $validator
            ->integer('status')
            ->requirePresence('status', 'create')
            ->notEmptyString('status');

        $validator
            ->dateTime('sentDate')
            ->requirePresence('sentDate', 'create')
            ->notEmptyDateTime('sentDate');

        return $validator;
    }
}
