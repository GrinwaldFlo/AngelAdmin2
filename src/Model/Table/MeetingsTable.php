<?php
declare(strict_types=1);
namespace App\Model\Table;
use App\Model\Entity\Meeting;
use App\Model\Entity\Presence;
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
  public const FILTER_PAST = 1;
  public const FILTER_TODAY = 2;
  public const FILTER_UPCOMING = 3;

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
      ->allowEmptyString('url')
      ->add('url', 'webUrl', [
        'rule' => fn($value) => Meeting::isWebUrl($value),
        'message' => __('The link must start with http:// or https://'),
      ]);


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
    // Today's meetings stay in "upcoming" for the whole day
    $today = new \DateTime('today');
    switch ($options['meetingFilter']) {
      case self::FILTER_PAST:
        $query->where(['Meetings.meeting_date <' => $today]);
        break;
      case self::FILTER_TODAY:
        $query->where(['Meetings.meeting_date >=' => $today, 'Meetings.meeting_date <' => new \DateTime('tomorrow')]);
        break;
      case self::FILTER_UPCOMING:
        $query->where(['Meetings.meeting_date >=' => $today]);
        break;
    }

    //************************************** Team filter **************************************

    if (empty($options['teamId']) || $options['teamId'] == 0) {

    } else {
      $query->where(['Teams.id =' => $options['teamId']]);
    }

    //************************************** Site filter **************************************

    if (!empty($options['siteId'])) {
      $query->where(['Teams.site_id =' => $options['siteId']]);
    }

    return $query; //->team(['Members.id']);
  }


  /**
   * Number of presences by state for several meetings, in one query
   * @param int[] $meetingIds
   * @return array [meeting_id => [state => count]]
   */
  public function countPresences(array $meetingIds): array
  {
    if (empty($meetingIds)) {
      return [];
    }
    $query = $this->Presences->find();
    $query->select(['meeting_id', 'state', 'nb' => $query->func()->count('*')])
      ->where(['meeting_id IN' => $meetingIds])
      ->groupBy(['meeting_id', 'state'])
      ->disableHydration();

    $result = [];
    foreach ($query as $row) {
      $result[$row['meeting_id']][$row['state']] = (int)$row['nb'];
    }
    return $result;
  }

  /**
   * Fill the unknown counters of a list of meetings with one query (not saved, so a GET stays read-only)
   * @param \App\Model\Entity\Meeting[] $meetings
   */
  public function loadCounts(array $meetings): void
  {
    $unknown = array_filter($meetings, fn(Meeting $meeting) => !$meeting->hasCounts());
    $counts = $this->countPresences(array_map(fn(Meeting $meeting) => $meeting->id, $unknown));
    foreach ($unknown as $meeting) {
      $meeting->setCounts($counts[$meeting->id] ?? []);
      $meeting->clean();
    }
  }

  /**
   * Members to show on the attendance page: active team members plus anyone already having a presence
   * @param \App\Model\Entity\Meeting $meeting with presences.member loaded
   * @param iterable $teamMembers Active members of the meeting's team
   * @return array list of ['id', 'name', 'state', 'inTeam'] sorted by name, team members first
   */
  public function attendanceList(Meeting $meeting, iterable $teamMembers): array
  {
    $list = [];
    foreach ($teamMembers as $member) {
      $list[$member->id] = ['id' => $member->id, 'name' => $member->FullNameShort, 'state' => Presence::NONE, 'inTeam' => true];
    }
    foreach ($meeting->presences as $presence) {
      if (isset($list[$presence->member_id])) {
        $list[$presence->member_id]['state'] = $presence->state;
      } elseif ($presence->member !== null) {
        $list[$presence->member_id] = ['id' => $presence->member_id, 'name' => $presence->member->FullNameShort, 'state' => $presence->state, 'inTeam' => false];
      }
    }

    usort($list, fn($a, $b) => [!$a['inTeam'], mb_strtolower($a['name'])] <=> [!$b['inTeam'], mb_strtolower($b['name'])]);
    return $list;
  }

  /**
   * Previous and next meetings of the same team
   * @return array ['prev' => Meeting|null, 'next' => Meeting|null]
   */
  public function neighbours(Meeting $meeting): array
  {
    $sameTeam = fn() => $this->find()->where(['team_id' => $meeting->team_id, 'Meetings.id !=' => $meeting->id]);
    return [
      'prev' => $sameTeam()
        ->where(['OR' => [
          'meeting_date <' => $meeting->meeting_date,
          'AND' => ['meeting_date' => $meeting->meeting_date, 'Meetings.id <' => $meeting->id],
        ]])
        ->orderBy(['meeting_date' => 'DESC', 'Meetings.id' => 'DESC'])->first(),
      'next' => $sameTeam()
        ->where(['OR' => [
          'meeting_date >' => $meeting->meeting_date,
          'AND' => ['meeting_date' => $meeting->meeting_date, 'Meetings.id >' => $meeting->id],
        ]])
        ->orderBy(['meeting_date' => 'ASC', 'Meetings.id' => 'ASC'])->first(),
    ];
  }
}
