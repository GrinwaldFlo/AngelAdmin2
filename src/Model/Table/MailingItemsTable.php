<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\MailingItem;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * MailingItems Model: recipients of the mailings, the pending ones are the queue still to send
 *
 * @property \App\Model\Table\MailingsTable&\Cake\ORM\Association\BelongsTo $Mailings
 * @property \App\Model\Table\MembersTable&\Cake\ORM\Association\BelongsTo $Members
 */
class MailingItemsTable extends Table
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

        $this->setTable('mailing_items');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Mailings', [
            'foreignKey' => 'mailing_id',
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
            ->integer('status')
            ->notEmptyString('status');

        $validator
            ->scalar('email')
            ->maxLength('email', 255);

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
        $rules->add($rules->existsIn(['mailing_id'], 'Mailings'));
        $rules->add($rules->existsIn(['member_id'], 'Members'));

        return $rules;
    }

    /**
     * Next emails to send, the ones that already failed come last
     * @return \Cake\Datasource\ResultSetInterface<\App\Model\Entity\MailingItem> with the member
     */
    public function nextPending(int $mailingId, int $limit)
    {
        return $this->find()
            ->contain(['Members'])
            ->where(['MailingItems.mailing_id' => $mailingId, 'MailingItems.status' => MailingItem::PENDING])
            ->orderBy(['MailingItems.attempts' => 'ASC', 'MailingItems.id' => 'ASC'])
            ->limit($limit)
            ->all();
    }

    /**
     * Number of emails by state for each mailing
     * @return array [mailing_id => ['total', 'pending', 'sent', 'invalid']], every given mailing is in the result
     */
    public function countsByMailing(array $mailingIds): array
    {
        $result = [];
        foreach ($mailingIds as $mailingId) {
            $result[$mailingId] = ['total' => 0, 'pending' => 0, 'sent' => 0, 'invalid' => 0];
        }
        if (empty($mailingIds)) {
            return $result;
        }

        $query = $this->find();
        $rows = $query->select(['mailing_id', 'status', 'nb' => $query->func()->count('*')])
            ->where(['mailing_id IN' => $mailingIds])
            ->groupBy(['mailing_id', 'status'])
            ->disableHydration()
            ->all();

        $keys = [MailingItem::PENDING => 'pending', MailingItem::SENT => 'sent', MailingItem::INVALID => 'invalid'];
        foreach ($rows as $row) {
            $result[$row['mailing_id']][$keys[$row['status']]] = (int)$row['nb'];
            $result[$row['mailing_id']]['total'] += (int)$row['nb'];
        }

        return $result;
    }
}
