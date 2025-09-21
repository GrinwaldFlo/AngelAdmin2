<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * MemberField1 Entity
 *
 * @property int $id
 * @property int $member_id
 * @property string $facebook
 * @property string $problemes_medicaux
 * @property string $contact1_first_name
 * @property string $contact1_last_name
 * @property string $contact1_natel
 * @property string $contact1_email
 * @property string $contact2_first_name
 * @property string $contact2_last_name
 * @property string $contact2_natel
 * @property string $contact2_email
 * @property string $remarque
 * @property string $a_connu_le_club_de
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Member $member
 */
class MemberField1 extends Entity
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
        'member_id' => true,
        'facebook' => true,
        'problemes_medicaux' => true,
        'contact1_first_name' => true,
        'contact1_last_name' => true,
        'contact1_natel' => true,
        'contact1_email' => true,
        'contact2_first_name' => true,
        'contact2_last_name' => true,
        'contact2_natel' => true,
        'contact2_email' => true,
        'remarque' => true,
        'a_connu_le_club_de' => true,
        'created' => true,
        'modified' => true,
        'member' => true,
    ];
}
