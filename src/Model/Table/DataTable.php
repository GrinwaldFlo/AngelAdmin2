<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class DataTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('data');
        $this->setPrimaryKey('id');
        $this->setDisplayField('id');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->integer('param')
            ->requirePresence('param', 'create')
            ->notEmptyString('param');

        $validator
            ->scalar('value')
            ->maxLength('value', 1000)
            ->allowEmptyString('value');

        $validator
            ->scalar('data_type')
            ->maxLength('data_type', 100)
            ->requirePresence('data_type', 'create')
            ->notEmptyString('data_type');

        return $validator;
    }
}