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
<div class="icon float-end member-header-actions">
    <?php if (!$isLogged): ?>
        <?php if (!empty($member->user)): ?>
            <?= $this->Html->link(__('Login'), ['action' => 'view', $member->id], ['class' => 'btn btn-primary btn-sm member-header-button']) ?>
            <?= h(__('Your user name is {0}', $member->user->username)) ?>
        <?php else: ?>
            <?= $this->Html->link(__('Create account with this member'), ['controller' => 'Users', 'action' => 'register', $member->hash], ['class' => 'btn btn-primary btn-sm member-header-button']) ?>
        <?php endif; ?>
        <?php if (!empty($config['secondHandEnabled'])): ?>
            <?= $this->Html->link(__('Second-hand'), ['controller' => 'SecondHand', 'action' => 'index'], ['class' => 'btn btn-primary btn-sm member-header-button']) ?>
        <?php endif; ?>
    <?php else: ?>
        <?= $curRole->MemberEditAll ? $this->backButtonCtrl('Members', 'index') : $this->BackButton('/') ?>
    <?php endif; ?>
</div>

<?php if (!$isLogged && count($this->my->Languages($config)) > 1): ?>
    <div class="mb-2"><?= $this->my->LanguageLinks($config) ?></div>
<?php endif; ?>

<h1><?= h($member->fullName) ?></h1>
<h4><?= h($member->activeTeamString) ?></h4>
