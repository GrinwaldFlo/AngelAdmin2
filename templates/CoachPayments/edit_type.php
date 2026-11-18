<?php

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CoachPresenceType $type
 */
?>
<div class="content">
  <h3><?= __('Edit presence type') ?></h3>
  <p class="text-muted"><?= __('A new amount applies to the presences not paid yet. Paid presences keep their amount.') ?></p>
  <?= $this->Form->create($type) ?>
  <?= $this->Form->control('label', ['label' => __('Label')]) ?>
  <?= $this->Form->control('amount', ['label' => __('Amount (CHF)'), 'type' => 'number', 'step' => '0.05', 'min' => 0]) ?>
  <?= $this->Form->control('sort', ['label' => __('Order'), 'type' => 'number']) ?>
  <?= $this->Form->control('active', ['label' => __('Active (shown on the attendance page)')]) ?>
  <div class="btn-bar">
    <?= $this->Form->button(__('Save'), ['class' => 'btn btn-save']) ?>
    <?= $this->Html->link(__('Cancel'), ['action' => 'settings'], ['class' => 'btn btn-nav']) ?>
  </div>
  <?= $this->Form->end() ?>
</div>
