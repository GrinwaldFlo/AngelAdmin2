<?php

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Mailing $mailing
 * @var array $counts ['total', 'pending', 'sent', 'invalid']
 * @var \App\Model\Entity\MailingItem[]|\Cake\Collection\CollectionInterface $items recipients
 * @var \App\Model\Entity\MailingItem[]|\Cake\Collection\CollectionInterface $failedItems recipients with a sending error
 */
use App\Controller\MailingsController;
use App\Model\Entity\Mailing;
use Cake\Routing\Router;

$this->Html->css('mailings', ['block' => true]);
$this->Html->script('mailings', ['block' => true, 'defer' => true]);
$startLabel = $counts['sent'] + $counts['invalid'] > 0 || !$failedItems->isEmpty() ? __('Resume sending') : __('Start sending');
?>
<div class="mailings content ml-send"
     data-url="<?= Router::url(['action' => 'sendNext', $mailing->id]) ?>"
     data-csrf="<?= h($this->request->getAttribute('csrfToken')) ?>"
     data-leave="<?= h(__('Emails are being sent, leaving the page will stop the sending.')) ?>"
     data-network-error="<?= h(__('The server could not be reached. Check your connection and resume.')) ?>">
  <?= $this->Html->link(__('Emails to teams'), ['action' => 'index'], ['class' => 'button float-end']) ?>
  <h3><?= h($mailing->title) ?></h3>
  <p class="ml-muted">
    <?= h($mailing->created->i18nFormat('dd.MM.yyyy HH:mm')) ?> · <?= __('Teams') ?>: <?= h($mailing->teams) ?>
  </p>
  <iframe class="ml-preview" sandbox srcdoc="<?= h(Mailing::lineBreaks($mailing->content)) ?>" title="<?= h(__('Content')) ?>"></iframe>

  <div class="ml-progress">
    <progress class="ml-bar" max="<?= $counts['total'] ?>" value="<?= $counts['sent'] + $counts['invalid'] ?>"></progress>
    <div>
      <?= __('Sent') ?>: <strong class="ml-sent"><?= $counts['sent'] ?></strong> / <span class="ml-total"><?= $counts['total'] ?></span>
      · <?= __('Pending') ?>: <strong class="ml-pending"><?= $counts['pending'] ?></strong>
      · <?= __('Invalid addresses') ?>: <strong class="ml-invalid-count"><?= $counts['invalid'] ?></strong>
    </div>
  </div>

  <div class="alert alert-danger ml-error" role="alert" hidden></div>
  <div class="alert alert-success ml-done" role="status" <?= $mailing->isDone() ? '' : 'hidden' ?>><?= __('All emails have been processed.') ?></div>

  <?php if (!$mailing->isDone()) : ?>
    <div class="ml-controls">
      <label for="ml-delay"><?= __('Pause between each {0} emails', MailingsController::CHUNK_SIZE) ?></label>
      <select id="ml-delay" class="form-select form-select-sm ml-delay">
        <?php foreach ([0, 2, 5, 10, 30, 60] as $seconds) : ?>
          <option value="<?= $seconds ?>" <?= $seconds == 2 ? 'selected' : '' ?>><?= __('{0} s', $seconds) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="button" class="btn btn-primary ml-start"
              data-label-start="<?= h($startLabel) ?>"
              data-label-stop="<?= h(__('Stop')) ?>"><?= h($startLabel) ?></button>
    </div>
  <?php endif; ?>

  <?php if ($counts['sent'] == 0) : ?>
    <div class="ml-delete">
      <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $mailing->id], [
        'class' => 'btn btn-danger btn-sm',
        'confirm' => __('Are you sure you want to delete {0}?', $mailing->title),
      ]) ?>
    </div>
  <?php endif; ?>

  <?php if (!$failedItems->isEmpty()) : ?>
    <h4 class="ml-failed-title"><?= __('Emails with an error') ?></h4>
    <table class="table table-striped table-sm">
      <thead>
        <tr>
          <th><?= __('Member') ?></th>
          <th><?= __('Email') ?></th>
          <th><?= __('Status') ?></th>
          <th><?= __('Error') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($failedItems as $item) : ?>
          <tr>
            <td><?= $this->Html->link($item->member->FullName, ['controller' => 'Members', 'action' => 'view', $item->member_id]) ?></td>
            <td><?= h($item->email) ?></td>
            <td><?= h($item->statusLabel()) ?></td>
            <td><?= h($item->error) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <h4 class="ml-recipients-title"><?= __('Recipients') ?> (<?= $counts['total'] ?>)</h4>
  <table class="table table-striped table-sm">
    <thead>
      <tr>
        <th><?= __('Member') ?></th>
        <th><?= __('Email') ?></th>
        <th><?= __('Status') ?></th>
        <th><?= __('Sent') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item) : ?>
        <tr>
          <td><?= $this->Html->link($item->member->FullName, ['controller' => 'Members', 'action' => 'view', $item->member_id]) ?></td>
          <td><?= h($item->email) ?></td>
          <td><?= h($item->statusLabel()) ?></td>
          <td><?= $item->sent ? h($item->sent->i18nFormat('dd.MM.yyyy HH:mm')) : '' ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
