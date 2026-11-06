<?php
/**
 * Name of the member of an order, a link only for the roles seeing all the members
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member|null $member
 * @var \App\Model\Entity\Role $curRole
 */
if (empty($member)) {
    return;
}
?>
<?php if (!empty($curRole->MemberViewAll)): ?>
    <?= $this->Html->link($member->fullName, ['controller' => 'Members', 'action' => 'view', $member->id]) ?>
<?php else: ?>
    <?= h($member->fullName) ?>
<?php endif; ?>
