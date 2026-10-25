<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * MailingItem Entity: one recipient of a mailing, the pending items are the queue still to send
 *
 * @property int $id
 * @property int $mailing_id
 * @property int $member_id
 * @property string $email address used when the mailing was created
 * @property int $status
 * @property int $attempts number of failed sending attempts
 * @property string|null $error last sending error
 * @property \Cake\I18n\DateTime|null $sent
 *
 * @property \App\Model\Entity\Mailing $mailing
 * @property \App\Model\Entity\Member $member
 */
class MailingItem extends Entity
{
    public const PENDING = 0;
    public const SENT = 1;
    // The address is not a valid email, retrying would never work
    public const INVALID = 2;

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::SENT => __('Sent'),
            self::INVALID => __('Invalid address'),
            default => $this->attempts > 0 ? __('Pending ({0} attempts)', $this->attempts) : __('Pending'),
        };
    }

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'mailing_id' => true,
        'member_id' => true,
        'email' => true,
        'status' => true,
        'attempts' => true,
        'error' => true,
        'sent' => true,
        'mailing' => true,
        'member' => true,
    ];
}
