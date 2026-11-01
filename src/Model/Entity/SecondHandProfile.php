<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * SecondHandProfile Entity: second-hand terms accepted by a member, and its details shown to the buyers
 *
 * @property int $id
 * @property int $member_id
 * @property \Cake\I18n\DateTime|null $terms_accepted null = terms not accepted yet
 * @property string $name name shown in the listing (never the name of the member)
 * @property string $phone
 * @property bool $whatsapp prefers to be contacted by WhatsApp
 * @property string $email
 * @property bool $email_verified the email is the one of the member, or was confirmed with the link
 * @property string|null $email_token token of the confirmation link
 * @property string $city
 * @property \App\Model\Entity\Member $member
 */
class SecondHandProfile extends Entity
{
    protected array $_accessible = [
        'name' => true,
        'phone' => true,
        'whatsapp' => true,
        'email' => true,
        'city' => true,
    ];

    protected array $_hidden = [
        'email_token',
    ];

    /**
     * All the seller details are filled
     */
    public function isSeller(): bool
    {
        foreach (['name', 'phone', 'email', 'city'] as $field) {
            if (trim((string)$this->get($field)) === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Seller whose items can be published
     */
    public function canSell(): bool
    {
        return $this->isSeller() && (bool)$this->email_verified;
    }

    /**
     * Phone number for a wa.me link: digits only, with the country code (local numbers are Swiss)
     */
    public function whatsappNumber(): string
    {
        $phone = trim((string)$this->phone);
        $digits = preg_replace('/\D/', '', $phone);
        if (str_starts_with($phone, '+')) {
            return $digits;
        }
        if (str_starts_with($digits, '00')) {
            return substr($digits, 2);
        }
        if (str_starts_with($digits, '0')) {
            return '41' . substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Phone number for a tel: link
     */
    public function phoneLink(): string
    {
        return preg_replace('/[^\d+]/', '', (string)$this->phone);
    }
}
