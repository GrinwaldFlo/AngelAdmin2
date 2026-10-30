<?php
declare(strict_types=1);
namespace App\Policy;
use App\Model\Entity\Member;
use Authorization\IdentityInterface;

/**
 * Member policy
 * A TeamScoped role (coach) only reaches the members of its teams, a new member can always be created
 */
class MemberPolicy
{
  public function canViewAll(?IdentityInterface $user, Member $member)
  {
    return $user !== null && $user->Role->MemberViewAll;
  }

  public function canEdit(?IdentityInterface $user, Member $member)
  {
    if ($user === null) {
      return false;
    }

    if ($member->id == $user->member_id && $user->Role->MemberEditOwn) {
      return true;
    }

    return $user->Role->MemberEditAll && ($member->isNew() || TeamScope::canAccessMember($user, $member->id));
  }

  // Not for the coaches: pages about all the members (signatures validation)
  public function canEditAll(?IdentityInterface $user, Member $member)
  {
    return TeamScope::canUnscoped($user, 'MemberEditAll');
  }

  // Members to validate (without team): not for the coaches
  public function canViewNew(?IdentityInterface $user, Member $member)
  {
    return TeamScope::canUnscoped($user, 'MemberViewAll');
  }

  // Same roles as the ones seeing the site (city) menu
  public function canSetSite(?IdentityInterface $user, Member $member)
  {
    return $user !== null && ($user->Role->MemberViewAll || $user->Role->BillViewAll);
  }

  public function canAdmin(?IdentityInterface $user, Member $member)
  {
    return $user !== null && $user->Role->Admin;
  }

  public function canView(?IdentityInterface $user, Member $member)
  {
    if ($user === null) {
      return false;
    }

    if ($user->member_id == $member->id) {
      return true;
    }

    return $user->Role->MemberViewAll && TeamScope::canAccessMember($user, $member->id);
  }
}
