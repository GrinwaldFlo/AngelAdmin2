<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\CoachAccount;
use App\Service\QrBillBuilder;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * IBAN of the coaches, one per coach and per site
 * Also finds who is a coach of a site (assigned to a team, declared at a meeting, or with an account)
 *
 * @property \App\Model\Table\MembersTable&\Cake\ORM\Association\BelongsTo $Members
 * @property \App\Model\Table\SitesTable&\Cake\ORM\Association\BelongsTo $Sites
 */
class CoachAccountsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('coach_accounts');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Members', [
            'foreignKey' => 'member_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Sites', [
            'foreignKey' => 'site_id',
            'joinType' => 'INNER',
        ]);
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if (isset($data['iban']) && is_string($data['iban'])) {
            $data['iban'] = QrBillBuilder::normalizeIban($data['iban']);
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('iban')
            ->requirePresence('iban', 'create')
            ->notEmptyString('iban', __('Enter an IBAN.'))
            ->maxLength('iban', 34)
            ->add('iban', 'qrBill', [
                'rule' => fn ($value) => QrBillBuilder::isValidIban((string)$value),
                'message' => __('The IBAN is not valid: only Swiss (CH) and Liechtenstein (LI) IBANs can be used.'),
            ]);

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['member_id'], 'Members'), ['errorField' => 'member_id']);
        $rules->add($rules->existsIn(['site_id'], 'Sites'), ['errorField' => 'site_id']);
        $rules->add($rules->isUnique(['member_id', 'site_id']), ['errorField' => 'iban']);

        return $rules;
    }

    public function accountOf(int $memberId, int $siteId): ?CoachAccount
    {
        return $this->find()->where(['member_id' => $memberId, 'site_id' => $siteId])->first();
    }

    /**
     * Accounts of a site
     * @return array<int, \App\Model\Entity\CoachAccount> by member id
     */
    public function accountsOfSite(int $siteId): array
    {
        return $this->find()->where(['site_id' => $siteId])->all()->indexBy('member_id')->toArray();
    }

    /**
     * Set or replace the IBAN of a coach for a site
     * @return \App\Model\Entity\CoachAccount saved, or with errors
     */
    /**
     * Account of a coach in a site (existing or new) with a new IBAN, not saved
     */
    public function patchIban(int $memberId, int $siteId, string $iban): CoachAccount
    {
        $account = $this->accountOf($memberId, $siteId) ?? $this->newEmptyEntity();
        $account = $this->patchEntity($account, ['iban' => $iban]);
        $account->member_id = $memberId;
        $account->site_id = $siteId;

        return $account;
    }

    /**
     * Save a new IBAN for a coach in a site
     * @return \App\Model\Entity\CoachAccount the account, with its errors if it was not saved
     */
    public function setIban(int $memberId, int $siteId, string $iban): CoachAccount
    {
        $account = $this->patchIban($memberId, $siteId, $iban);
        $this->save($account);

        return $account;
    }

    public function removeIban(int $memberId, int $siteId): bool
    {
        $account = $this->accountOf($memberId, $siteId);

        return $account !== null && $this->delete($account);
    }

    /**
     * Sites where a member is a coach: sites of their coached teams, of their declared presences and of their accounts
     * @return int[]
     */
    public function siteIdsOf(int $memberId): array
    {
        $teams = TableRegistry::getTableLocator()->get('TeamsCoaches')->find()
            ->select(['site_id' => 'Teams.site_id'])
            ->innerJoinWith('Teams')
            ->where(['TeamsCoaches.member_id' => $memberId]);
        $presences = TableRegistry::getTableLocator()->get('CoachPresences')->find()
            ->select(['site_id' => 'Teams.site_id'])
            ->innerJoinWith('Meetings.Teams')
            ->where(['CoachPresences.member_id' => $memberId]);
        $accounts = $this->find()->select(['site_id'])->where(['member_id' => $memberId]);

        return $this->uniqueIds([$teams, $presences, $accounts], 'site_id');
    }

    /**
     * Coaches of a site: assigned to one of its teams, declared at one of its meetings, or with an account in this site
     * @return array<int, \App\Model\Entity\Member> by member id, sorted by name
     */
    public function coachesOfSite(int $siteId): array
    {
        $teams = TableRegistry::getTableLocator()->get('TeamsCoaches')->find()
            ->select(['member_id' => 'TeamsCoaches.member_id'])
            ->innerJoinWith('Teams')
            ->where(['Teams.site_id' => $siteId]);
        $presences = TableRegistry::getTableLocator()->get('CoachPresences')->find()
            ->select(['member_id' => 'CoachPresences.member_id'])
            ->innerJoinWith('Meetings.Teams')
            ->where(['Teams.site_id' => $siteId]);
        $accounts = $this->find()->select(['member_id'])->where(['site_id' => $siteId]);

        $memberIds = $this->uniqueIds([$teams, $presences, $accounts], 'member_id');
        if ($memberIds === []) {
            return [];
        }

        return $this->Members->find()
            ->where(['Members.id IN' => $memberIds])
            ->orderBy(['Members.first_name' => 'ASC', 'Members.last_name' => 'ASC'])
            ->all()
            ->indexBy('id')
            ->toArray();
    }

    /**
     * Names of the teams of a site coached by each member
     * @return array<int, string[]> team names by member id
     */
    public function coachedTeamNames(int $siteId): array
    {
        $rows = TableRegistry::getTableLocator()->get('TeamsCoaches')->find()
            ->contain(['Teams'])
            ->where(['Teams.site_id' => $siteId])
            ->orderBy(['Teams.name' => 'ASC']);
        $result = [];
        foreach ($rows as $row) {
            $result[$row->member_id][] = $row->team->name;
        }

        return $result;
    }

    /**
     * @param \Cake\ORM\Query\SelectQuery[] $queries
     * @return int[] distinct values of the field
     */
    private function uniqueIds(array $queries, string $field): array
    {
        $ids = [];
        foreach ($queries as $query) {
            foreach ($query->disableHydration()->all() as $row) {
                $ids[(int)$row[$field]] = true;
            }
        }

        return array_keys($ids);
    }
}
