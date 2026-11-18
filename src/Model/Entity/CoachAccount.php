<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * IBAN of a coach for a site, never shown in full (only in the payment QR code)
 *
 * @property int $id
 * @property int $member_id
 * @property int $site_id
 * @property string $iban
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property string $masked_iban last 4 digits, e.g. "•••• 1234"
 * @property \App\Model\Entity\Member $member
 * @property \App\Model\Entity\Site $site
 */
class CoachAccount extends Entity
{
    protected array $_accessible = [
        'iban' => true,
    ];

    protected array $_hidden = [
        'iban',
    ];

    protected function _getMaskedIban(): string
    {
        return self::mask($this->iban);
    }

    /**
     * Last 4 characters of an IBAN, '' if none
     */
    public static function mask(?string $iban): string
    {
        return empty($iban) ? '' : '•••• ' . substr($iban, -4);
    }
}
