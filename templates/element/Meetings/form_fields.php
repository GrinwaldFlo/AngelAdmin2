<?php
/**
 * Fields shared by the add and edit forms of a meeting
 *
 * @var \App\View\AppView $this
 * @var array $teams [id => name]
 */
?>
<?= $this->Form->control('team_id', ['options' => $teams, 'label' => __('Team')]) ?>
<?= $this->Form->control('name', ['label' => __('Name'), 'placeholder' => __('Training')]) ?>
<details class="mt-options" <?= $this->Form->isFieldError('url') ? 'open' : '' ?>>
  <summary><?= __('More options') ?></summary>
  <?= $this->Form->control('big_event', ['label' => __('Big event')]) ?>
  <?= $this->Form->control('doodle', ['label' => __('Members can register from the home page')]) ?>
  <?= $this->Form->control('url', ['label' => __('Link'), 'type' => 'url', 'placeholder' => 'https://']) ?>
</details>
