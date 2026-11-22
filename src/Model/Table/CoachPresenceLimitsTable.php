<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Maximum number of coaches with a presence type at one meeting of a team (no row = no limit)
 *
 * @property \App\Model\Table\TeamsTable&\Cake\ORM\Association\BelongsTo $Teams
 * @property \App\Model\Table\CoachPresenceTypesTable&\Cake\ORM\Association\BelongsTo $CoachPresenceTypes
 */
class CoachPresenceLimitsTable extends Table
{
    /** Highest limit accepted by the settings form */
    public const MAX = 99;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('coach_presence_limits');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Teams', [
            'foreignKey' => 'team_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('CoachPresenceTypes', [
            'foreignKey' => 'coach_presence_type_id',
            'joinType' => 'INNER',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('max_count')
            ->range('max_count', [0, self::MAX])
            ->notEmptyString('max_count');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['team_id'], 'Teams'), ['errorField' => 'team_id']);
        $rules->add($rules->existsIn(['coach_presence_type_id'], 'CoachPresenceTypes'), ['errorField' => 'coach_presence_type_id']);
        $rules->add($rules->isUnique(['team_id', 'coach_presence_type_id']), ['errorField' => 'coach_presence_type_id']);

        return $rules;
    }

    /**
     * Limits of some teams
     * @param int[] $teamIds
     * @return array<int, array<int, int>> [team id => [type id => max]]
     */
    public function limitsOfTeams(array $teamIds): array
    {
        if ($teamIds === []) {
            return [];
        }
        $result = [];
        foreach ($this->find()->where(['team_id IN' => $teamIds])->all() as $limit) {
            $result[$limit->team_id][$limit->coach_presence_type_id] = (int)$limit->max_count;
        }

        return $result;
    }

    /**
     * Limits of a team
     * @return array<int, int> [type id => max]
     */
    public function limitsOfTeam(int $teamId): array
    {
        return $this->limitsOfTeams([$teamId])[$teamId] ?? [];
    }

    /**
     * Save the limits of the settings form, only for the given teams and types (those of the site)
     * @param array<int|string, mixed> $data [team id => [type id => max]], '' = no limit
     * @param int[] $teamIds teams of the site
     * @param int[] $typeIds presence types of the site
     * @return bool false = an invalid value, nothing is saved
     */
    public function saveLimits(array $data, array $teamIds, array $typeIds): bool
    {
        $existing = $this->find()
            ->where(['team_id IN' => $teamIds ?: [0], 'coach_presence_type_id IN' => $typeIds ?: [0]])
            ->all()
            ->combine(fn ($limit) => $limit->team_id . '-' . $limit->coach_presence_type_id, fn ($limit) => $limit)
            ->toArray();

        $toSave = [];
        $toDelete = [];
        foreach ($teamIds as $teamId) {
            foreach ($typeIds as $typeId) {
                $value = $data[$teamId][$typeId] ?? '';
                $limit = $existing[$teamId . '-' . $typeId] ?? null;
                if ($value === '' || $value === null) {
                    if ($limit !== null) {
                        $toDelete[] = $limit;
                    }
                    continue;
                }
                if ($limit === null) {
                    $limit = $this->newEmptyEntity();
                    $limit->team_id = $teamId;
                    $limit->coach_presence_type_id = $typeId;
                }
                $limit = $this->patchEntity($limit, ['max_count' => $value]);
                if ($limit->hasErrors()) {
                    return false;
                }
                $toSave[] = $limit;
            }
        }

        return $this->getConnection()->transactional(function () use ($toSave, $toDelete) {
            foreach ($toDelete as $limit) {
                $this->deleteOrFail($limit);
            }

            return $this->saveMany($toSave) !== false;
        });
    }
}
