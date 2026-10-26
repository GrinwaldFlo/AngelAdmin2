<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, string> $emails email of the municipality by city
 * @var array<string, int> $counts number of members by city
 * @var int $ageFilter
 * @var bool $hasBody the email body content exists
 */
?>
<div class="row">
  <div class="col">
    <div class="members form content">
      <h3><?= __('Municipality emails') ?></h3>
      <?php if (!$hasBody) : ?>
        <div class="alert alert-warning">
          <?= __('The email body is empty: add a content with the location "{0}".', 'Email subventions') ?>
          <?= $this->Html->link(__('Contents'), ['controller' => 'Contents', 'action' => 'add']) ?>
        </div>
      <?php endif; ?>
      <p><?= __('The email pack contains one email per city, with its letter attached. Without an email address, the recipient must be added in the email client.') ?></p>

      <?= $this->Form->create(null) ?>
      <table class="table table-striped table-hover table-sm">
        <thead>
          <tr>
            <th><?= __('City') ?></th>
            <th><?= __('Members') ?></th>
            <th><?= __('Email') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 0; ?>
          <?php foreach ($emails as $city => $email) : ?>
            <tr>
              <td><?= h($city) ?></td>
              <td><?= $counts[$city] ?? 0 ?></td>
              <td>
                <?= $this->Form->hidden("cities.$i.city", ['value' => $city]) ?>
                <?= $this->Form->control("cities.$i.email", ['type' => 'email', 'label' => false, 'value' => $email]) ?>
              </td>
            </tr>
            <?php $i++; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?= $this->Form->button(__('Save')) ?>
      <?= $this->Html->link(__('Cancel'), ['action' => 'subventions', $ageFilter], ['class' => 'btn btn-secondary']) ?>
      <?= $this->Form->end() ?>
    </div>
  </div>
</div>
