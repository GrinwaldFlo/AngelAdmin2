<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Mailing Entity
 *
 * @property int $id
 * @property string $title
 * @property string $content
 * @property string $attachement1
 * @property string $attachement2
 * @property string $attachement3
 * @property int $status
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property \Cake\I18n\DateTime $sentDate
 *
 * @property \App\Model\Entity\MailingItem[] $mailing_items
 */
class Mailing extends Entity
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
        'title' => true,
        'content' => true,
        'attachement1' => true,
        'attachement2' => true,
        'attachement3' => true,
        'status' => true,
        'created' => true,
        'modified' => true,
        'sentDate' => true,
        'mailing_items' => true,
    ];
}
