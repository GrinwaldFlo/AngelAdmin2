<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Meeting $meeting
 */
?>
<div class="row">
  <div class="col">
    <div class="meetings form content">
      <div class="tags float-end" style="width: 100px">
        <?= $this->backButtonCtrl('Meetings', 'index') ?>
      </div>
      <?= $this->Form->create($meeting) ?>
      <fieldset>
        <legend><?= __('Edit Meeting') ?></legend>
        <?php
        echo $this->Form->control('meeting_date', ['step' => 60 * 15]);
        echo $this->Form->control('team_id', ['options' => $teams]);
        echo $this->Form->control('name');
        echo $this->Form->control('big_event');
        echo $this->Form->control('url');
        echo $this->Form->control('doodle');
        ?>
      </fieldset>
      <?= $this->Form->button(__('Submit')) ?>
      <?= $this->Form->end() ?>
    </div>
  </div>
</div>
