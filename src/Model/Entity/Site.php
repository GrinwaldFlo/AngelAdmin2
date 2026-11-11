<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;
use Cake\Validation\Validation;

/**
 * Site Entity
 *
 * @property int $id
 * @property string $city
 * @property string|null $address
 * @property string|null $account_designation
 * @property string|null $postcode
 * @property string|null $iban
 * @property string|null $bic
 * @property int $feeMax
 * @property int $reminder_penalty
 * @property string $sender_email
 * @property string $sender
 * @property string|null $sender_phone
 * @property int $add_invoice_num
 */
class Site extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected array $_accessible = [
        'city' => true,
        'address' => true,
        'account_designation' => true,
        'postcode' => true,
        'iban' => true,
        'bic' => true,
        'feeMax' => true,
        'reminder_penalty' => true,
        'sender_email' => true,
        'sender' => true,
        'sender_phone' => true,
        'add_invoice_num' => true,
    ];

    /**
     * Sender email of the site, or the fallback (club email) if the site has no valid one
     *
     * @return string '' if none is valid
     */
    public function senderEmail(string $fallback = ''): string
    {
        foreach ([(string)$this->sender_email, $fallback] as $email) {
            if (Validation::email($email)) {
                return $email;
            }
        }

        return '';
    }
}
