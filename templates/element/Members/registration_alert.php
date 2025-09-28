<?php
/**
 * Member Registration Alert Element
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member $member
 * @var bool $isOwn
 * @var array $config
 * @var bool $isMobile
 * @var string|null $contactEmail
 */

if (!($isOwn && $member->RegistrationStep($config['year']) > 0)) {
    return;
}
?>
<div class="col-12">
    <div class="alert alert-danger" role="alert">
        <h4><?= __('Registration for the year {0}-{1}', $config['year'], $config['year'] + 1) ?></h4>
        <i><?= __('Each year you need to register for the current season, please follow the steps below:') ?></i>

        <br /><br />
        <?php if ($member->RegistrationStep($config['year']) == 1): ?>
            <strong><?= __('Please check your contact information') ?></strong>
            <?= $this->Html->link(__('The information is correct'), ['action' => 'checked', $member->id], ['class' => 'btn btn-primary btn-sm']) ?>

            <?php if (isset($contactEmail)): ?>
                <a href="mailto:<?= h($contactEmail) ?>?subject=<?= urlencode(__('Ask for modifications')) ?>" class="btn btn-secondary">
                    <?= __('Ask for modifications') ?>
                </a>
            <?php endif; ?>
        <?php elseif ($member->RegistrationStep($config['year']) == 2): ?>
            <?= __('You are a past member') ?>
            <?= $this->Html->link(__('I want to start again !'), ['controller' => 'Members', 'action' => 'active', $member->id], ['class' => 'btn btn-primary btn-sm']) ?>
        <?php elseif ($member->RegistrationStep($config['year']) == 3): ?>
            <?php if (!$isMobile): ?>
                <?= __('You will need to sign the aggreement for this year. A mobile phone is recomended') ?>
            <?php endif; ?>
            <br />
            <strong><?= __('You are not registered for this season') ?></strong>
            <?= $this->Html->link(__('I register for the season'), ['controller' => 'Members', 'action' => 'agreement', $member->id], ['class' => 'btn btn-primary btn-sm']) ?>
        <?php elseif ($member->RegistrationStep($config['year']) == 4): ?>
            <?= __('You have to be approved by a coach, please wait') ?>
        <?php elseif ($member->RegistrationStep($config['year']) == 6): ?>
            <?= __('You have no teams, wait a while or check with your coach') ?>
        <?php else: ?>
            <?= __('This should not append') ?>
        <?php endif; ?>

        <br /><br />
        <?php if ($member->active): ?>
            <?= __('But if you would not like to continue') ?>
            <?= $this->Html->link(__('Quit membership'), ['action' => 'cancelRegistration', $member->id], ['class' => 'btn btn-primary btn-sm']); ?>
        <?php endif; ?>
    </div>
</div>
