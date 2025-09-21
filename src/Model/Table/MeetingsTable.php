<?php
declare(strict_types=1);
namespace App\Model\Table;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Meetings Model
 *
 * @property \App\Model\Table\TeamsTable&\Cake\ORM\Association\BelongsTo $Teams
 * @property \App\Model\Table\PresencesTable&\Cake\ORM\Association\HasMany $Presences
 *
 * @method \App\Model\Entity\Meeting newEmptyEntity()
 * @method \App\Model\Entity\Meeting newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Meeting[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Meeting get($primaryKey,array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, ...$args)
 * @method \App\Model\Entity\Meeting findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Meeting patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Meeting[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Meeting|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Meeting saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Meeting[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Meeting[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Meeting[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Meeting[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class MeetingsTable extends Table
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

    $this->setTable('meetings');
    $this->setDisplayField('name');
    $this->setPrimaryKey('id');

    $this->addBehavior('Timestamp');

    $this->belongsTo('Teams', [
      'foreignKey' => 'team_id',
      'joinType' => 'INNER',
    ]);
    $this->hasMany('Presences', [
      'foreignKey' => 'meeting_id',
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
      ->dateTime('meeting_date')
      ->requirePresence('meeting_date', 'create')
      ->notEmptyDateTime('meeting_date');

    $validator
      ->scalar('name')
      ->maxLength('name', 255)
      ->requirePresence('name', 'create')
      ->notEmptyString('name');

    $validator
      ->scalar('url')
      ->maxLength('url', 250)
      ->allowEmptyString('url');


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
    $rules->add($rules->existsIn(['team_id'], 'Teams'));

    return $rules;
  }

  public function findMeetings(Query $query, array $options)
  {
    /*  $columns = [
      'Members.id', 'Members.first_name', 'Members.last_name', 'Members.date_birth', 'Members.hash'
      ];

      $query = $query->select($columns)->distinct($columns); */

    //************************************** Member status **************************************
    switch ($options['meetingFilter']) {
      case 1: // past
        $query->where(['Meetings.meeting_date <' => new \DateTime()]);
        break;
      case 2: // Now
        $query->where(['DATE(Meetings.meeting_date)' => (new \DateTime())->format('Y-m-d')]);
        break;
      case 3: // Futur
        $query->where(['Meetings.meeting_date >' => new \DateTime()]);
        break;
    }

    //************************************** Team filter **************************************

    if (empty($options['teamId']) || $options['teamId'] == 0) {

    } else {
      $query->where(['Teams.id =' => $options['teamId']]);
    }

    return $query; //->team(['Members.id']);
  }

}