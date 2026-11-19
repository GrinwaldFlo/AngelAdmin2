<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\CoachAccount;
use App\Model\Entity\CoachPayment;
use App\Model\Entity\Member;
use App\Model\Entity\Site;
use App\Service\QrBillBuilder;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Payments of a site to its coaches
 *
 * @property \App\Model\Table\MembersTable&\Cake\ORM\Association\BelongsTo $Members
 * @property \App\Model\Table\SitesTable&\Cake\ORM\Association\BelongsTo $Sites
 * @property \App\Model\Table\CoachPresencesTable&\Cake\ORM\Association\HasMany $CoachPresences
 */
class CoachPaymentsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('coach_payments');
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
        $this->hasMany('CoachPresences', [
            'foreignKey' => 'coach_payment_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->decimal('amount')
            ->greaterThan('amount', 0);

        $validator
            ->date('paid_date');

        return $validator;
    }

    /**
     * Pay all the due open presences of a coach in a site: the amounts are copied on the presences
     * @param float|null $expected total shown to the admin (QR code): refused if the balance changed since
     * @return \App\Model\Entity\CoachPayment|string the payment, or an error message
     */
    public function payOpen(int $memberId, int $siteId, ?float $expected = null): CoachPayment|string
    {
        $error = __('The balance has changed since the page was opened, check the new amount.');
        $result = $this->getConnection()->transactional(function () use ($memberId, $siteId, $expected, &$error) {
            $presences = $this->CoachPresences->find('open', siteId: $siteId, memberId: $memberId, due: true)->all()->toList();
            $total = round(array_sum(array_map(fn ($presence) => $presence->value(), $presences)), 2);
            if ($total <= 0) {
                $error = __('There is nothing to pay.');

                return false;
            }
            if ($expected !== null && abs($total - $expected) > 0.001) {
                return false;
            }

            $payment = $this->newEmptyEntity();
            $payment->member_id = $memberId;
            $payment->site_id = $siteId;
            $payment->amount = $total;
            $payment->paid_date = Date::today();
            if (!$this->save($payment)) {
                $error = __('The payment could not be saved.');

                return false;
            }
            // Each presence is taken only if it is still open with the type read above: the row lock of the
            // update makes a concurrent payment or type change wait, then miss it (rollback, nothing paid twice)
            foreach ($presences as $presence) {
                $taken = $this->CoachPresences->updateAll(
                    ['coach_payment_id' => $payment->id, 'amount' => $presence->value(), 'modified' => DateTime::now()],
                    ['id' => $presence->id, 'coach_payment_id IS' => null, 'coach_presence_type_id' => $presence->coach_presence_type_id],
                );
                if ($taken !== 1) {
                    return false;
                }
            }

            return $payment;
        });

        return $result === false ? $error : $result;
    }

    /**
     * The presences of a deleted payment become open again (runs in the delete transaction)
     */
    public function beforeDelete(EventInterface $event, EntityInterface $entity): void
    {
        $this->CoachPresences->updateAll(['coach_payment_id' => null, 'amount' => null], ['coach_payment_id' => $entity->id]);
    }

    /**
     * Payments of a site, newest first, with the number of presences
     * @return array<int, \App\Model\Entity\CoachPayment[]> by member id
     */
    public function paymentsOfSite(int $siteId, ?int $memberId = null): array
    {
        $query = $this->find()
            ->select($this)
            ->select(['presences_count' => $this->find()->func()->count('CoachPresences.id')])
            ->leftJoinWith('CoachPresences')
            ->where(['CoachPayments.site_id' => $siteId])
            ->groupBy(['CoachPayments.id'])
            ->orderBy(['CoachPayments.paid_date' => 'DESC', 'CoachPayments.id' => 'DESC']);
        if ($memberId !== null) {
            $query->where(['CoachPayments.member_id' => $memberId]);
        }
        $result = [];
        foreach ($query as $payment) {
            $result[$payment->member_id][] = $payment;
        }

        return $result;
    }

    /**
     * What is missing to build the QR code paying a coach
     * @return string[] messages, empty when the QR code can be built
     */
    public function qrMissing(Member $coach, ?CoachAccount $account): array
    {
        $missing = [];
        if ($account === null) {
            $missing[] = __('No IBAN set');
        }
        if (empty($coach->address) || empty($coach->postcode) || empty($coach->city)) {
            $missing[] = __('The address of the coach is incomplete');
        }

        return $missing;
    }

    /**
     * QR code paying a coach (creditor) from the account of the site (debtor), as an SVG data URI
     * @throws \RuntimeException when the data is not valid for a QR bill
     */
    public function qrCode(Member $coach, CoachAccount $account, Site $site, float $amount, string $information): string
    {
        $debtor = null;
        if (!empty($site->account_designation) && !empty($site->postcode) && !empty($site->city)) {
            $debtor = QrBillBuilder::structuredAddress((string)$site->account_designation, $site->address, (string)$site->postcode, (string)$site->city);
        }

        return QrBillBuilder::build(
            QrBillBuilder::structuredAddress($coach->full_name, $coach->address, (string)$coach->postcode, (string)$coach->city),
            $account->iban,
            $amount,
            $debtor,
            $information
        )->getDataUri();
    }
}
