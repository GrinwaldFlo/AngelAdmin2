<?php
declare(strict_types=1);
namespace App\Model\Table;
use Cake\ORM\Query;
use App\Model\Entity\Meeting;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Presences Model
 *
 * @property \App\Model\Table\MeetingsTable&\Cake\ORM\Association\BelongsTo $Meetings
 * @property \App\Model\Table\MembersTable&\Cake\ORM\Association\BelongsTo $Members
 *
 * @method \App\Model\Entity\Presence newEmptyEntity()
 * @method \App\Model\Entity\Presence newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Presence[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Presence get($primaryKey,array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, ...$args)
 * @method \App\Model\Entity\Presence findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Presence patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Presence[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Presence|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Presence saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Presence[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Presence[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Presence[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Presence[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class PresencesTable extends Table
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

    $this->setTable('presences');
    $this->setDisplayField('id');
    $this->setPrimaryKey('id');

    $this->addBehavior('Timestamp');

    $this->belongsTo('Meetings', [
        'foreignKey' => 'meeting_id',
        'joinType' => 'INNER',
    ]);
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
      ->integer('state')
      ->notEmptyString('state');

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
    $rules->add($rules->existsIn(['meeting_id'], 'Meetings'));
    $rules->add($rules->existsIn(['member_id'], 'Members'));

    return $rules;
  }

  /**
   * Count presences by team and state for meetings in a period
   * @param int $siteId 0 = all sites
   * @return array [team_id => [state => count]], state: 0 = absent, 1 = present, 2 = excused, 3 = late
   */
  public function CountStatesByTeam(\DateTimeInterface $from, \DateTimeInterface $to, int $siteId = 0): array
  {
    $query = $this->find();
    $query->select(['team_id' => 'Meetings.team_id', 'state' => 'Presences.state', 'nb' => $query->func()->count('*')])
      ->innerJoinWith('Meetings.Teams')
      ->where(['Meetings.meeting_date >=' => $from, 'Meetings.meeting_date <=' => $to, 'Presences.state IN' => [0, 1, 2, 3]])
      ->groupBy(['Meetings.team_id', 'Presences.state'])
      ->disableHydration();
    if ($siteId > 0) {
      $query->where(['Teams.site_id =' => $siteId]);
    }

    $result = [];
    foreach ($query as $row) {
      $result[$row['team_id']][$row['state']] = (int)$row['nb'];
    }
    return $result;
  }

  /**
   * Set the state of members for a meeting (creates the missing presences) and refresh the meeting counters
   * @param \App\Model\Entity\Meeting $meeting
   * @param int[] $memberIds
   * @param int $state One of the Presence constants
   * @return bool false if one presence could not be saved
   */
  public function setState(Meeting $meeting, array $memberIds, int $state): bool
  {
    $existing = $this->find()
      ->where(['meeting_id' => $meeting->id, 'member_id IN' => $memberIds ?: [0]])
      ->all()
      ->indexBy('member_id')
      ->toArray();

    $presences = [];
    foreach ($memberIds as $memberId) {
      $presence = $existing[$memberId] ?? $this->newEntity(['meeting_id' => $meeting->id, 'member_id' => $memberId]);
      $presence->state = $state;
      $presences[] = $presence;
    }

    $saved = $this->saveMany($presences) !== false;
    $meeting->CheckPresences(true);
    return $saved;
  }
}
