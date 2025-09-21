<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Utility\Text;
use Cake\Datasource\ResultSetInterface;
use Cake\I18n\Date;

/**
 * Bills Model
 *
 * @property \App\Model\Table\MembersTable&\Cake\ORM\Association\BelongsTo $Members
 * @property \App\Model\Table\SitesTable&\Cake\ORM\Association\BelongsTo $Sites
 * @property \App\Model\Table\MemberOrdersTable&\Cake\ORM\Association\HasMany $MemberOrders
 *
 * @method \App\Model\Entity\Bill newEmptyEntity()
 * @method \App\Model\Entity\Bill newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Bill[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Bill get($primaryKey,array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, ...$args)
 * @method \App\Model\Entity\Bill findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Bill patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Bill[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Bill|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Bill saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Bill[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Bill[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Bill[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Bill[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class BillsTable extends Table
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

    $this->setTable('bills');
    $this->setDisplayField('id');
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
      ->scalar('label')
      ->maxLength('label', 200)
      ->requirePresence('label', 'create')
      ->notEmptyString('label');

    $validator
      ->integer('amount')
      ->requirePresence('amount', 'create')
      ->notEmptyString('amount');

    $validator
      ->boolean('printed')
      ->requirePresence('printed', 'create')
      ->notEmptyString('printed');

    $validator
      ->boolean('paid')
      ->requirePresence('paid', 'create')
      ->notEmptyString('paid');

    $validator
      ->integer('reminder')
      ->requirePresence('reminder', 'create')
      ->notEmptyString('reminder');

    $validator
      ->date('due_date')
      ->notEmptyDate('due_date');

    $validator
      ->date('due_date_ori')
      ->requirePresence('due_date_ori', 'create')
      ->notEmptyDate('due_date_ori');

    $validator
      ->boolean('link_membership_fee')
      ->notEmptyString('link_membership_fee');

    $validator
      ->boolean('canceled')
      ->requirePresence('canceled', 'create')
      ->notEmptyString('canceled');

    $validator
      ->scalar('tokenhash')
      ->maxLength('tokenhash', 255)
      ->requirePresence('tokenhash', 'create')
      ->notEmptyString('tokenhash');

    $validator
      ->dateTime('confirmation')
      ->allowEmptyDateTime('confirmation');

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
    $rules->add($rules->existsIn(['site_id'], 'Sites'));

    return $rules;
  }
  // Just kept for sample, use App GetBills()
  public function findBills(Query $query, array $options)
  {
    $columns = [
      'Bills.id',
      'Bills.label',
      'Bills.amount',
      'Bills.reminder',
      'Bills.due_date',
      'Bills.printed',
      'Bills.paid',
      'Bills.canceled',
      'Members.first_name',
      'Members.last_name',
      'Members.hash',
      'Members.id'
    ];
    $query = $query->select($columns)->distinct($columns);
    $query->contain([
      'Members',
      'Members.Teams',
    ]);

    //************************************** Bill status **************************************
    //We never show canceled bills
    //$query->where(['Bills.canceled =' => 0]);
    switch ($options['billStatus'] ) {
      case 1;
        $query->where(['Bills.paid =' => 0]);
        break;
      case 2;
        $query->where(['Bills.due_date <' => new \DateTime()])->where(['Bills.paid =' => 0]);
        break;
      case 3;
        $query->where(['Bills.paid =' => 1]);
        break;
      case 4;
        $query->where(['Bills.printed =' => 0]);
        break;
    }
    //************************************** Member status **************************************
    switch ($options['memberStatus']) {
      case 1;
        $query->where(['Members.active =' => 1]);
        break;
      case 2;
        $query->where(['Members.active =' => 0]);
        break;
    }
    //************************************** Team filter **************************************
    if (false && !empty($options['teamId']) && $options['teamId'] != 0) {
      //$query->innerJoinWith('Teams')->where(['Teams.id =' => $options['teamId']]);
//$query->contain('Members', 'Teams')->where(['Teams.id =' => $options['teamId']]);
      $teamId = $options['teamId'];
      $query->matching('Members.Teams', function ($q) use ($teamId) {
        return $q->where(['Teams.id' => $teamId]);
      });
      //       // In a controller or table method.
//$query = $products->find()->matching(
//   'Shops.Cities.Countries', function ($q) {
//      return $q->where(['Countries.name' => 'Japan']);    });
    }
    // sql($query);
    return $query; //->team(['Bills.id']);
  }

  /**
   * Get all late bills from a member
   *
   * @param int $memberId
   * @return ResultSetInterface
   */
  public function getLateBillsByMemberId(int $memberId): ResultSetInterface
  {
    return $this->find()
      ->where([
        'Bills.member_id' => $memberId,
        'Bills.paid' => 0,
        'Bills.canceled' => 0,
        'Bills.due_date <' => new \DateTime()
      ])
      ->all();
  }

  public function findwithMembersTeamsSites(Query $query, array $options)
  {
    return $query
      ->contain(['Members', 'Members.Teams', 'Sites']);
  }

  public function GetNbOpenInvoice()
  {
    return $this->find('all')->where(['Bills.paid =' => 0, 'Bills.canceled =' => 0])->count();
  }
  public function GetSumOpenInvoice()
  {
    return $this->GetSum(['Bills.paid =' => 0, 'Bills.canceled =' => 0]);
  }
  public function GetSum($condition)
  {
    $query = $this->find();
    $result = $query->select(['sum' => $query->func()->sum('Bills.amount')])
      ->where($condition)->first();
    return $result->sum;
  }
  public function GetSumPaidFees($dateFrom)
  {
    return $this->GetSum(['Bills.created >' => $dateFrom, 'Bills.paid =' => 1, 'Bills.canceled =' => 0, 'Bills.link_membership_fee =' => 1]);
  }
  public function GetSumFeesFrom($dateFrom)
  {
    return $this->GetSum(['Bills.created >' => $dateFrom, 'Bills.canceled =' => 0, 'Bills.link_membership_fee =' => 1]);
  }
  public function GetSumInvoicedFrom($dateFrom)
  {
    return $this->GetSum(['Bills.created >' => $dateFrom, 'Bills.canceled =' => 0]);
  }
  public function GetSumInvoicedPaidFrom($dateFrom)
  {
    return $this->GetSum(['Bills.paid =' => 1, 'Bills.created >' => $dateFrom, 'Bills.canceled =' => 0]);
  }
  public function GetFeeLabel($config)
  {
    return $config['feeLabel'] . " " . __("{0}-{1}", $config['year'], $config['year'] + 1);
  }
  public function CreateMembershipFee($config, $member, $extraLabel, $date, $amount)
  {
    $bill = $this->newEmptyEntity();
    $bill->member_id = $member->id;
    $bill->label = $this->GetFeeLabel($config);
    if (!empty($extraLabel))
      $bill->label = $bill->label . " " . $extraLabel;
    $bill->amount = $amount;
    $bill->printed = 0;
    $bill->paid = 0;
    $bill->reminder = 0;
    $bill->due_date = $date;
    $bill->due_date_ori = $date;
    $bill->link_membership_fee = 1;
    $bill->canceled = 0;
    $bill->state_id = 0;
    $bill->tokenhash = Text::uuid();
    $this->save($bill);
  }

  /**
   * Create a shop bill for member orders
   *
   * @param \App\Model\Entity\Member $member The member placing the order
   * @param array $orderItems Array of order items with id, label, price, quantity
   * @param int $siteId The site ID
   * @return \App\Model\Entity\Bill|false The created bill or false on failure
   */
  public function CreateShopBill($member, $orderItems, $siteId)
  {
    $dateBill = new \DateTime();
    $dateBill->add(new \DateInterval('P10D'));

    // Calculate total amount
    $totalAmount = 0;
    $itemLabels = [];
    foreach ($orderItems as $item) {
      $totalAmount += $item['price'] * $item['quantity'];
      $itemLabels[] = $item['label'] . ' (x' . $item['quantity'] . ')';
    }

    $bill = $this->newEmptyEntity();
    $bill->printed = 0;
    $bill->paid = 0;
    $bill->reminder = 0;
    $bill->canceled = false;
    $bill->due_date = new \Cake\I18n\Date($dateBill);
    $bill->state_id = 0;
    $bill->tokenhash = Text::uuid();
    $bill->due_date_ori = new \Cake\I18n\Date($dateBill);
    $bill->link_membership_fee = 0;
    $bill->member_id = $member->id;
    $bill->amount = $totalAmount;
    $bill->label = __('Shop Order: {0}', implode(', ', $itemLabels));

    if ($this->save($bill)) {
      return $bill;
    }
    
    return false;
  }
}
