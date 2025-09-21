<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\MemberDoc;
use Authorization\IdentityInterface;

/**
 * MemberDoc policy
 */
class MemberDocPolicy
{
    /**
     * Check if $user can create MemberDoc
     *
     * @param Authorization\IdentityInterface $user The user.
     * @param App\Model\Entity\MemberDoc $memberDoc
     * @return bool
     */
    public function canCreate(IdentityInterface $user, MemberDoc $memberDoc)
    {
    }

    /**
     * Check if $user can update MemberDoc
     *
     * @param Authorization\IdentityInterface $user The user.
     * @param App\Model\Entity\MemberDoc $memberDoc
     * @return bool
     */
    public function canUpdate(IdentityInterface $user, MemberDoc $memberDoc)
    {
    }

    /**
     * Check if $user can delete MemberDoc
     *
     * @param Authorization\IdentityInterface $user The user.
     * @param App\Model\Entity\MemberDoc $memberDoc
     * @return bool
     */
    public function canDelete(IdentityInterface $user, MemberDoc $memberDoc)
    {
    }

    /**
     * Check if $user can view MemberDoc
     *
     * @param Authorization\IdentityInterface $user The user.
     * @param App\Model\Entity\MemberDoc $memberDoc
     * @return bool
     */
    public function canView(IdentityInterface $user, MemberDoc $memberDoc)
    {
    }
}
