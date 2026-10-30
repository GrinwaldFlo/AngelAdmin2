<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\TeamsCoach;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * TeamsCoaches Model: coaches and help-coaches of each team
 * The access of a TeamScoped role is limited to the teams it coaches
 *
 * @property \App\Model\Table\TeamsTable&\Cake\ORM\Association\BelongsTo $Teams
 * @property \App\Model\Table\MembersTable&\Cake\ORM\Association\BelongsTo $Members
 */
class TeamsCoachesTable extends Table
{
    /**
     * @var array<int, int[]> coached team ids by member id, for the current request
     */
    private array $teamIdsCache = [];

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('teams_coaches');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Teams', [
            'foreignKey' => 'team_id',
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
            ->integer('team_id')
            ->notEmptyString('team_id');

        $validator
            ->integer('member_id')
            ->notEmptyString('member_id');

        $validator
            ->inList('type', array_keys(TeamsCoach::types()));

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['team_id'], 'Teams'), ['errorField' => 'team_id']);
        $rules->add($rules->existsIn(['member_id'], 'Members'), ['errorField' => 'member_id']);
        $rules->add($rules->isUnique(['team_id', 'member_id']), ['errorField' => 'member_id']);

        return $rules;
    }

    public function afterSave(): void
    {
        $this->teamIdsCache = [];
    }

    public function afterDelete(): void
    {
        $this->teamIdsCache = [];
    }

    /**
     * Active teams coached (as coach or help-coach) by a member
     * @return int[]
     */
    public function teamIdsOf(int $memberId): array
    {
        if (!isset($this->teamIdsCache[$memberId])) {
            $this->teamIdsCache[$memberId] = $this->find()
                ->select(['TeamsCoaches.team_id'])
                ->innerJoinWith('Teams', fn ($q) => $q->where(['Teams.active' => true]))
                ->where(['TeamsCoaches.member_id' => $memberId])
                ->all()
                ->extract('team_id')
                ->map(fn ($id) => (int)$id)
                ->toList();
        }

        return $this->teamIdsCache[$memberId];
    }

    /**
     * Coach assignments of teams with their member, coaches first then help-coaches, sorted by name
     * @param int[] $teamIds
     * @return array<int, \App\Model\Entity\TeamsCoach[]> assignments by team id
     */
    public function rowsByTeam(array $teamIds): array
    {
        $result = array_fill_keys($teamIds, []);
        if (empty($teamIds)) {
            return $result;
        }

        $rows = $this->find()
            ->contain(['Members'])
            ->where(['TeamsCoaches.team_id IN' => $teamIds])
            ->orderBy(['TeamsCoaches.type' => 'ASC', 'Members.first_name' => 'ASC', 'Members.last_name' => 'ASC']);
        foreach ($rows as $row) {
            $result[$row->team_id][] = $row;
        }

        return $result;
    }

    /**
     * Coaches of teams, sorted by name
     * @param int[] $teamIds
     * @return array<int, array<int, \App\Model\Entity\Member[]>> members by team id, then by type
     */
    public function coachesByTeam(array $teamIds): array
    {
        $result = [];
        foreach ($this->rowsByTeam($teamIds) as $teamId => $rows) {
            $result[$teamId] = array_fill_keys(array_keys(TeamsCoach::types()), []);
            foreach ($rows as $row) {
                $result[$teamId][$row->type][] = $row->member;
            }
        }

        return $result;
    }

    /**
     * Coaches of one team, sorted by name
     * @return array<int, \App\Model\Entity\Member[]> members by type
     */
    public function coachesOf(int $teamId): array
    {
        return $this->coachesByTeam([$teamId])[$teamId];
    }

    /**
     * Members who can be assigned as coach: active members having a user account
     * @return array<int, string> full name by member id
     */
    public function candidates(): array
    {
        $members = $this->Members->find()
            ->innerJoinWith('Users')
            ->where(['Members.active' => true])
            ->distinct(['Members.id'])
            ->orderBy(['Members.first_name' => 'ASC', 'Members.last_name' => 'ASC']);
        $result = [];
        foreach ($members as $member) {
            $result[$member->id] = $member->fullName;
        }

        return $result;
    }

    /**
     * Assign a member as coach or help-coach of a team
     * @return \App\Model\Entity\TeamsCoach saved, or with errors (e.g. the member already coaches the team)
     */
    public function addCoach(int $teamId, int $memberId, int $type): TeamsCoach
    {
        $row = $this->newEntity(['team_id' => $teamId, 'member_id' => $memberId, 'type' => $type]);
        $this->save($row);

        return $row;
    }
}
