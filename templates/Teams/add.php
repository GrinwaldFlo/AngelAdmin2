<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Team $team
 */
?>
<div class="row">
  <div class="column column">
    <div class="teams form content">
      <?= $this->Form->create($team) ?>
      <fieldset>
        <legend><?= __('Add Team') ?></legend>
        <?php
        echo $this->Form->control('name');
        echo $this->Form->control('membership_fee');
        echo $this->Form->control('active');
        echo $this->Form->control('description');
        echo $this->Form->control('site_id', ['options' => $sites]);
        ?>
      </fieldset>
      <?= $this->Form->button(__('Submit')) ?>
      <?= $this->Form->end() ?>
    </div>
  </div>
</div>
