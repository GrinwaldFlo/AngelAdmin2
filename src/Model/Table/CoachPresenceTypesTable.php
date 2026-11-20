<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\CoachPresenceType;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Presence types of the coaches (label and amount), defined per site
 *
 * @property \App\Model\Table\SitesTable&\Cake\ORM\Association\BelongsTo $Sites
 * @property \App\Model\Table\CoachPresencesTable&\Cake\ORM\Association\HasMany $CoachPresences
 */
class CoachPresenceTypesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('coach_presence_types');
        $this->setDisplayField('label');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Sites', [
            'foreignKey' => 'site_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('CoachPresences', [
            'foreignKey' => 'coach_presence_type_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('label')
            ->maxLength('label', 50)
            ->requirePresence('label', 'create')
            ->notEmptyString('label');

        $validator
            ->decimal('amount')
            ->greaterThanOrEqual('amount', 0)
            ->lessThan('amount', 1000000)
            ->notEmptyString('amount');

        $validator
            ->integer('sort')
            ->allowEmptyString('sort');

        $validator
            ->boolean('active');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['site_id'], 'Sites'), ['errorField' => 'site_id']);
        $rules->addDelete(
            fn (CoachPresenceType $type) => !$this->isUsed($type->id),
            'typeUsed',
            ['errorField' => 'label', 'message' => __('This type is used by coach presences: deactivate it instead.')]
        );

        return $rules;
    }

    public function isUsed(int $typeId): bool
    {
        return $this->CoachPresences->exists(['coach_presence_type_id' => $typeId]);
    }

    /**
     * Types of a site, in their order
     * @param bool $activeOnly false = also the deactivated types (reports)
     */
    public function findOfSite(SelectQuery $query, int $siteId, bool $activeOnly = true): SelectQuery
    {
        $query->where(['CoachPresenceTypes.site_id' => $siteId])
            ->orderBy(['CoachPresenceTypes.sort' => 'ASC', 'CoachPresenceTypes.amount' => 'DESC', 'CoachPresenceTypes.id' => 'ASC']);
        if ($activeOnly) {
            $query->where(['CoachPresenceTypes.active' => true]);
        }

        return $query;
    }

    /**
     * New type of a site
     * @param array $data label, amount, sort, active
     */
    public function newForSite(int $siteId, array $data): CoachPresenceType
    {
        $type = $this->newEntity($data);
        $type->site_id = $siteId;

        return $type;
    }
}
