<?php
declare(strict_types=1);
namespace App\Model\Table;
use App\Model\Entity\Member;
use Cake\ORM\Query;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Members Model
 *
 * @property \App\Model\Table\BillsTable&\Cake\ORM\Association\HasMany $Bills
 * @property \App\Model\Table\PresencesTable&\Cake\ORM\Association\HasMany $Presences
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\HasMany $Users
 * @property \App\Model\Table\TeamsTable&\Cake\ORM\Association\BelongsToMany $Teams
 *
 * @method \App\Model\Entity\Member newEmptyEntity()
 * @method \App\Model\Entity\Member newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Member[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Member get($primaryKey,array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, ...$args)
 * @method \App\Model\Entity\Member findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Member patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Member[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Member|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Member saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Member[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Member[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Member[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Member[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class MembersTable extends Table
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

        $this->setTable('members');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Tools');

        $this->hasMany('Bills', [
            'foreignKey' => 'member_id',
        ]);
        $this->hasMany('Presences', [
            'foreignKey' => 'member_id',
        ]);
        $this->hasMany('MemberOrders', [
            'foreignKey' => 'member_id',
        ]);
        $this->hasOne('Users', [
            'foreignKey' => 'member_id',
        ]);
        $this->belongsToMany('Teams', [
            'foreignKey' => 'member_id',
            'targetForeignKey' => 'team_id',
            'joinTable' => 'teams_members',
        ]);
        $this->hasMany('Fields', [
            'foreignKey' => 'member_id',
        ]);
        $this->hasOne('MemberField1s', [
            'foreignKey' => 'member_id',
        ]);
        $this->hasMany('MembersSpecialFields', [
            'foreignKey' => 'member_id',
        ]);
        $this->hasMany('MailingItems', [
            'foreignKey' => 'member_id',
        ]);
        $this->hasMany('Registrations', [
            'foreignKey' => 'member_id',
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
            ->scalar('first_name')
            ->maxLength('first_name', 200)
            ->requirePresence('first_name', 'create')
            ->notEmptyString('first_name');

        $validator
            ->scalar('last_name')
            ->maxLength('last_name', 200)
            ->requirePresence('last_name', 'create')
            ->notEmptyString('last_name');

        $validator
            ->date('date_birth')
            ->requirePresence('date_birth', 'create')
            ->allowEmptyDate('date_birth');

        $validator
            ->scalar('address')
            ->maxLength('address', 255)
            ->requirePresence('address', 'create')
            ->allowEmptyString('address');

        $validator
            ->integer('postcode')
            ->allowEmptyString('postcode');

        $validator
            ->scalar('city')
            ->maxLength('city', 100)
            ->requirePresence('city', 'create')
            ->allowEmptyString('city');

        $validator
            ->scalar('phone_mobile')
            ->maxLength('phone_mobile', 50)
            ->requirePresence('phone_mobile', 'create')
            ->allowEmptyString('phone_mobile');

        $validator
            ->scalar('phone_home')
            ->maxLength('phone_home', 50)
            ->requirePresence('phone_home', 'create')
            ->allowEmptyString('phone_home');

        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email');

        $validator
            ->boolean('email_valid')
            ->requirePresence('email_valid', 'create')
            ->notEmptyString('email_valid');

        $validator
            ->scalar('nationality')
            ->maxLength('nationality', 200)
            ->requirePresence('nationality', 'create')
            ->allowEmptyString('nationality');

        $validator
            ->date('date_arrival')
            ->requirePresence('date_arrival', 'create')
            ->notEmptyDate('date_arrival');

        $validator
            ->integer('multi_payment')
            ->requirePresence('multi_payment', 'create')
            ->notEmptyString('multi_payment')
            ->add('multi_payment', 'validValue', [
                'rule' => ['range', 1, 8]
            ]);

        $validator
            ->integer('membership_fee_paid')
            ->requirePresence('membership_fee_paid', 'create')
            ->notEmptyString('membership_fee_paid');

        $validator
            ->integer('discount')
            ->requirePresence('discount', 'create')
            ->notEmptyString('discount');

        $validator
            ->date('date_fin')
            ->requirePresence('date_fin', 'create')
            ->allowEmptyDateTime('date_fin');

        $validator
            ->boolean('active')
            ->requirePresence('active', 'create')
            ->notEmptyString('active');

        $validator
            ->notEmptyString('coach');

        $validator
            ->boolean('registered')
            ->requirePresence('registered', 'create')
            ->notEmptyString('registered');

        $validator
            ->boolean('bvr')
            ->notEmptyString('bvr');

        $validator
            ->scalar('leaving_comment')
            ->maxLength('leaving_comment', 1000)
            //->requirePresence('leaving_comment', 'create')
            ->allowEmptyString('leaving_comment');

        return $validator;
    }

    /**
     * New hash giving access to the personal page of a member without account (40 hex characters, like SHA-1)
     */
    public static function newHash(): string
    {
        return bin2hex(random_bytes(20));
    }

    public static function isValidHash(?string $hash): bool
    {
        return is_string($hash) && preg_match('/^[a-f0-9]{40}$/i', $hash) === 1;
    }

    /**
     * Give a hash to a member who has none (or an invalid one), saved at once
     * @return string the hash of the member
     */
    public function ensureHash(Member $member): string
    {
        if (!self::isValidHash($member->hash)) {
            $member->hash = self::newHash();
            $this->updateAll(['hash' => $member->hash], ['id' => $member->id]);
            $member->setDirty('hash', false);
        }

        return $member->hash;
    }

    public function beforeSave($event, $entity, $options)
    {
        $entity->first_name = trim($entity->first_name);
        $entity->last_name = trim($entity->last_name);
        $entity->address = trim($entity->address);
        $entity->city = trim($entity->city);
        $entity->email = trim($entity->email);
        $entity->phone_mobile = $this->formatTel($entity->phone_mobile);
        //$entity->phone_home = $this->formatTel($entity->phone_home);
        $entity->address = $this->formatAddress($entity->address);
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
        return $rules;
    }

    public function findMembers(Query $query, array $options): Query
    {
        $columns = [
            'Members.id',
            'Members.first_name',
            'Members.last_name',
            'Members.date_birth',
            'Members.email',
            'Members.hash',
            'Members.discount',
            'Members.address',
            'Members.city',
            'Members.postcode',
            'Members.phone_mobile',
            'Members.multi_payment',
            'Members.coach',
            'Users.username',
            'Users.lastLogin',
            'Users.role_id',
            'Members.modified',
            'Members.registered'
        ];

        $query = $query->select($columns)->distinct($columns)->contain(['Users', 'Teams', 'Teams.Sites']);

        //************************************** Member status **************************************
        switch ($options['memberFilter']) {
            case 1:
                $query->where(['Members.active =' => 1]);
                break;
            case 2:
                $query->where(['Members.active =' => 0]);
                break;
            case 3:
                $query->where(['Members.active =' => 1, 'Members.membership_fee_paid =' => 0]);
                break;
            case 4:
                $query->where(['Members.active =' => 1, 'Members.registered =' => 0]);
                break;
            case 5:
                $query->where(['Members.active =' => 1])
                    ->leftJoinWith('Teams')->where(['Teams.id IS' => null]);
                break;
        }

        //************************************** Team filter **************************************

        if (!empty($options['teamId']) && $options['teamId'] != 0 && $options['memberFilter'] != 5) {
            // Find members that have one or more of the provided teams.
            $query->innerJoinWith('Teams')->where(['Teams.id IN' => $options['teamId']]); //->where(['Members.active IN' => $options['active']]);
        }

        if (!empty($options['siteId']) && $options['siteId'] != 0 && $options['memberFilter'] != 5) {
            $query->innerJoinWith('Teams')->where(['Teams.site_id IN' => $options['siteId']]);
        }

        return $query->groupBy(['Members.id']);
    }

    public function findMembersExtended(Query $query, array $options): Query
    {
        $columns = [
            'Members.id',
            'Members.first_name',
            'Members.last_name',
            'Members.date_birth',
            'Members.email',
            'Members.hash',
            'Members.discount',
            'Members.address',
            'Members.city',
            'Members.postcode',
            'Members.phone_mobile',
            'Members.multi_payment',
            'Members.coach',
            'Users.username',
            'Users.lastLogin',
            'Users.role_id',
            'Members.modified',
            'Members.registered'
        ];

        $query = $query->select($columns)->distinct($columns)->contain(['Users', 'Teams', 'Teams.Sites']);

        // Add calculation for sum of late bills using a subquery
        $lateBillsSubquery = $this->Bills->find()
            ->select([
                'member_id' => 'Bills.member_id',
                'late_bills_sum' => $query->func()->coalesce([
                    $query->func()->sum('Bills.amount'),
                    0
                ])
            ])
            ->where([
                'Bills.paid' => false,
                'Bills.canceled' => false,
                'Bills.due_date <' => \Cake\I18n\FrozenDate::today()
            ])
            ->groupBy('Bills.member_id');

        // Add the late bills sum as a virtual field using LEFT JOIN
        $query->leftJoin(
            ['LateBills' => $lateBillsSubquery],
            ['LateBills.member_id = Members.id']
        );

        // Add the late bills sum to the select
        $query->select(['late_bills_sum' => 'COALESCE(LateBills.late_bills_sum, 0)']);

        //************************************** Member status **************************************
        switch ($options['memberFilter']) {
            case 1:
                $query->where(['Members.active =' => 1]);
                break;
            case 2:
                $query->where(['Members.active =' => 0]);
                break;
            case 3:
                $query->where(['Members.active =' => 1, 'Members.membership_fee_paid =' => 0]);
                break;
            case 4:
                $query->where(['Members.active =' => 1, 'Members.registered =' => 0]);
                break;
            case 5:
                $query->where(['Members.active =' => 1])
                    ->leftJoinWith('Teams')->where(['Teams.id IS' => null]);
                break;
        }

        //************************************** Team filter **************************************

        if (!empty($options['teamId']) && $options['teamId'] != 0 && $options['memberFilter'] != 5) {
            // Find members that have one or more of the provided teams.
            $query->innerJoinWith('Teams')->where(['Teams.id IN' => $options['teamId']]); //->where(['Members.active IN' => $options['active']]);
        }

        if (!empty($options['siteId']) && $options['siteId'] != 0 && $options['memberFilter'] != 5) {
            $query->innerJoinWith('Teams')->where(['Teams.site_id IN' => $options['siteId']]);
        }

        return $query->groupBy(['Members.id']);
    }

    public function CountActiveMembers(int $siteId = 0): int
    {
        return $this->queryInSite($siteId)->count();
    }

    public function CountNotRegistered(int $siteId = 0): int
    {
        return $this->queryInSite($siteId)->where(["registered =" => 0])->count();
    }

    public function CountActiveChilds(int $siteId = 0): int
    {
        return $this->queryInSite($siteId)->where(['date_birth >' => $this->adultBirthLimit()])->count();
    }

    public function CountActiveAdults(int $siteId = 0): int
    {
        return $this->queryInSite($siteId)->where(['date_birth <=' => $this->adultBirthLimit()])->count();
    }

    /**
     * Members born on this date or before are adults (18 years old)
     */
    private function adultBirthLimit(): string
    {
        return (new \DateTime('today'))->sub(new \DateInterval('P18Y'))->format('Y-m-d');
    }

    public function CountActiveFeesPaid(int $siteId = 0): int
    {
        return $this->queryInSite($siteId)->where(['membership_fee_paid >' => 0])->count();
    }

    public function CountActiveWithoutTeam(): int
    {
        return $this->find('all')
            ->leftJoinWith('Teams')
            ->where(['Members.active =' => 1, 'Teams.id IS' => null])
            ->count();
    }

    /**
     * Members in a team of the site, each member counted once
     * @param int $siteId 0 = all sites
     * @param bool $active true = active members, false = alumni
     */
    public function queryInSite(int $siteId, bool $active = true): SelectQuery
    {
        $query = $this->find('all')->where(["Members.active =" => $active ? 1 : 0]);
        if ($siteId > 0) {
            $inSite = $this->find()->select(['Members.id'])
                ->innerJoinWith('Teams', fn($q) => $q->where(['Teams.site_id =' => $siteId]));
            $query->where(['Members.id IN' => $inSite]);
        }
        return $query;
    }

    public function findwithTeams(Query $query, array $options)
    {
        return $query
            ->contain(['Teams']);
    }
}
