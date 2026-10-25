<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Meeting $meeting
 * @var array $teams [id => name]
 */
$this->loadHelper('Meeting');
$this->Html->css('meetings', ['block' => true]);
?>
<div class="mt-page">
  <div class="mt-view-head">
    <?= $this->Html->link($this->Meeting->icon('arrow_back'), ['action' => 'view', $meeting->id], ['class' => 'mt-icon-btn', 'escape' => false, 'title' => __('Back'), 'aria-label' => __('Back')]) ?>
    <h2 class="mt-title"><?= __('Edit Meeting') ?></h2>
  </div>

  <?= $this->Form->create($meeting) ?>
  <fieldset class="mt-card mt-form-narrow">
    <?= $this->Form->control('meeting_date', ['label' => __('Date and time'), 'step' => 60 * 15]) ?>
    <?= $this->element('Meetings/form_fields', ['teams' => $teams]) ?>
  </fieldset>
  <div class="mt-submit mt-form-narrow">
    <?= $this->Form->button(__('Save'), ['class' => 'mt-btn mt-btn-primary']) ?>
  </div>
  <?= $this->Form->end() ?>
</div>
