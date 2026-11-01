<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Mailing $mailing
 * @var array $teams active teams [id => name]
 * @var array $teamIds selected teams
 * @var array $placeholders [placeholder => label]
 */
$this->Html->css('mailings', ['block' => true]);
$this->Html->script('mailings', ['block' => true, 'defer' => true]);
?>
<div class="row">
  <?= $this->element('Mailings/nav') ?>
  <div class="col-md-9">
    <div class="mailings form content">
      <?= $this->Form->create($mailing) ?>
      <fieldset>
        <legend><?= __('New email') ?></legend>
        <?= $this->element('Mailings/compose', compact('placeholders')) ?>
        <?= $this->Form->control('team_ids', ['type' => 'select', 'multiple' => 'checkbox', 'options' => $teams, 'value' => $teamIds, 'label' => __('Teams')]) ?>
      </fieldset>
      <p class="ml-muted"><?= __('The emails are prepared on the next page, where you can start sending them.') ?></p>
      <?= $this->Form->button(__('Next')) ?>
      <?= $this->Form->end() ?>
    </div>
  </div>
</div>
