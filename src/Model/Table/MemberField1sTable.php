<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * MemberField1s Model
 *
 * @property \App\Model\Table\MembersTable&\Cake\ORM\Association\BelongsTo $Members
 *
 * @method \App\Model\Entity\MemberField1 newEmptyEntity()
 * @method \App\Model\Entity\MemberField1 newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\MemberField1[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\MemberField1 get($primaryKey,array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, ...$args)
 * @method \App\Model\Entity\MemberField1 findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\MemberField1 patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\MemberField1[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\MemberField1|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\MemberField1 saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\MemberField1[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\MemberField1[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\MemberField1[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\MemberField1[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class MemberField1sTable extends Table
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

        $this->setTable('member_field1s');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Members', [
            'foreignKey' => 'member_id',
            'joinType' => 'INNER',
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
            ->scalar('facebook')
            ->maxLength('facebook', 255)
            ->allowEmptyString('facebook');

        $validator
            ->scalar('problemes_medicaux')
            ->maxLength('problemes_medicaux', 255)
            ->allowEmptyString('problemes_medicaux');

        $validator
            ->scalar('contact1_first_name')
            ->maxLength('contact1_first_name', 255)
            ->allowEmptyString('contact1_first_name');

        $validator
            ->scalar('contact1_last_name')
            ->maxLength('contact1_last_name', 255)
            ->allowEmptyString('contact1_last_name');

        $validator
            ->scalar('contact1_natel')
            ->maxLength('contact1_natel', 255)
            ->allowEmptyString('contact1_natel');

        $validator
            ->email('contact1_email')
            ->allowEmptyString('contact1_email');

        $validator
            ->scalar('contact2_first_name')
            ->maxLength('contact2_first_name', 255)
            ->allowEmptyString('contact2_first_name');

        $validator
            ->scalar('contact2_last_name')
            ->maxLength('contact2_last_name', 255)
            ->allowEmptyString('contact2_last_name');

        $validator
            ->scalar('contact2_natel')
            ->maxLength('contact2_natel', 255)
            ->allowEmptyString('contact2_natel');

        $validator
            ->email('contact2_email')
            ->allowEmptyString('contact2_email');

        $validator
            ->scalar('remarque')
            ->maxLength('remarque', 255)
            ->allowEmptyString('remarque');

        $validator
            ->scalar('a_connu_le_club_de')
            ->maxLength('a_connu_le_club_de', 200)
            ->allowEmptyString('a_connu_le_club_de');

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
        $rules->add($rules->existsIn(['member_id'], 'Members'));

        return $rules;
    }
}
