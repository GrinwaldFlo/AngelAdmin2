<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Role Entity
 *
 * @property int $id
 * @property string $name
 * @property bool $MemberViewAll
 * @property bool $MemberEditAll
 * @property bool $MemberEditOwn
 * @property bool $BillViewAll
 * @property bool $BillEditAll
 * @property bool $BillValidate
 * @property bool $Editor
 * @property bool $Admin Is admin
 *
 * @property \App\Model\Entity\User[] $users
 */
class Role extends Entity
{
    /**
     * @var mixed|null
     */
    protected $_hasAllowsNull;

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
        'name' => true,
        'MemberViewAll' => true,
        'MemberEditAll' => true,
        'MemberEditOwn' => true,
        'BillViewAll' => true,
        'BillEditAll' => true,
        'BillValidate' => true,
        'Admin' => true,
        'Editor' => true,
        'users' => true,
    ];
}
