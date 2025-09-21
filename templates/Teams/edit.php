<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Team $team
 */
?>
<div class="row">
  <div class="col">
    <div class="teams form content">
      <div class="row">
        <div class="col">
          <?= $this->BackButton($refer, true) ?>
          <h3><?= __('Edit Team') ?></h3>
        </div>
      </div>
      <?= $this->Form->create($team) ?>
      <fieldset>
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
