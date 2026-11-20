<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * Presence type of a coach at a meeting
 * Open while coach_payment_id is null (amount read from the type), the amount is copied when paid
 *
 * @property int $id
 * @property int $meeting_id
 * @property int $member_id
 * @property int $coach_presence_type_id
 * @property int|null $coach_payment_id
 * @property string|float|null $amount copied from the type when paid
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Meeting $meeting
 * @property \App\Model\Entity\Member $member
 * @property \App\Model\Entity\CoachPresenceType $coach_presence_type
 * @property \App\Model\Entity\CoachPayment|null $coach_payment
 */
class CoachPresence extends Entity
{
    protected array $_accessible = [];

    public function isPaid(): bool
    {
        return $this->coach_payment_id !== null;
    }

    /**
     * The meeting date is reached: it can be paid (needs the meeting)
     */
    public function isDue(): bool
    {
        return $this->meeting->meeting_date <= DateTime::now();
    }

    /**
     * Amount to pay (open, needs the type) or paid
     */
    public function value(): float
    {
        return (float)($this->isPaid() ? $this->amount : $this->coach_presence_type->amount);
    }
}
