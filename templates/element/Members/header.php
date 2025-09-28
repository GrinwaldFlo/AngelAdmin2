<?php
/**
 * Member View Header Element
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member $member
 * @var bool $isLogged
 * @var object $curRole
 * @var bool $isOwn
 */
?>
<div class="icon float-end" style="width: 400px">
    <?php if (!$isLogged): ?>
        <?php if (!empty($member->user)): ?>
            <?= $this->Html->link(__('Login'), ['action' => 'view', $member->id], ['class' => 'btn btn-primary btn-sm', 'style' => 'width:300px;height:32px']) ?>
            <?= __('Your user name is {0}', $member->user->username) ?>
        <?php else: ?>
            <?= $this->Html->link(__('Create account with this member'), ['controller' => 'Users', 'action' => 'register', $member->hash], ['class' => 'btn btn-primary btn-sm', 'style' => 'width:300px;height:32px']) ?>
        <?php endif; ?>
    <?php else: ?>
        <?= $curRole->MemberEditAll ? $this->backButtonCtrl('Members', 'index') : $this->BackButton('/') ?>
    <?php endif; ?>
</div>

<h1><?= h(($member->coach ? __('Coach') . ' ' : '') . $member->fullName) ?></h1>
<h4><?= h($member->teamString) ?></h4>
