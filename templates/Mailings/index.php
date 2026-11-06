<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Mailing[]|\Cake\Collection\CollectionInterface $mailings
 * @var array $counts [mailing_id => ['total', 'pending', 'sent', 'invalid']]
 * @var bool[] $manageable [mailing_id => can be sent / deleted], false for a coach when other teams receive it
 * @var bool $canSendByBill the user can send emails to the members having some bills
 */
$this->Html->css('mailings', ['block' => true]);
?>
<div class="mailings index content">
  <div class="float-end ml-actions">
    <?php if ($canSendByBill) : ?>
      <?= $this->Html->link(__('New email by bill'), ['action' => 'addByBill'], ['class' => 'btn btn-add btn-sm']) ?>
    <?php endif; ?>
    <?= $this->Html->link(__('New email'), ['action' => 'add'], ['class' => 'btn btn-add btn-sm']) ?>
  </div>
  <h3><?= __('Emails to teams') ?></h3>
  <table class="table table-striped table-hover table-sm">
    <thead>
      <tr>
        <th><?= $this->Paginator->sort('created', __('Date')) ?></th>
        <th><?= $this->Paginator->sort('title', __('Subject')) ?></th>
        <th><?= __('Recipients') ?></th>
        <th><?= __('Sent') ?></th>
        <th><?= __('Status') ?></th>
        <th class="actions"><?= __('Actions') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($mailings as $mailing) : ?>
            <?php $count = $counts[$mailing->id]; ?>
        <tr>
          <td><?= h($mailing->created->i18nFormat('dd.MM.yyyy HH:mm')) ?></td>
          <td><?= h($mailing->title) ?></td>
          <td><?= h($mailing->recipientsLabel()) ?></td>
          <td>
            <?= $count['sent'] ?> / <?= $count['total'] ?>
            <?php if ($count['invalid'] > 0) : ?>
              <span class="ml-invalid">(<?= __('{0} invalid', $count['invalid']) ?>)</span>
            <?php endif; ?>
          </td>
          <td><?= $mailing->isDone() ? __('Done') : __('{0} pending', $count['pending']) ?></td>
          <td class="actions">
            <?php if ($manageable[$mailing->id]) : ?>
              <?= $this->Html->link($mailing->isDone() ? __('View') : __('Resume'), ['action' => 'send', $mailing->id]) ?>
            <?php endif; ?>
            <?php if (!$mailing->isByBill()) : ?>
              <?= $this->Html->link(__('New'), ['action' => 'add', $mailing->id], ['title' => __('New email with the same subject, content and teams')]) ?>
            <?php elseif ($canSendByBill) : ?>
              <?= $this->Html->link(__('New'), ['action' => 'addByBill', $mailing->id], ['title' => __('New email with the same subject, content and bills')]) ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="paginator">
    <ul class="pagination">
      <?= $this->Paginator->first('<< ' . __('first')) ?>
      <?= $this->Paginator->prev('< ' . __('previous')) ?>
      <?= $this->Paginator->numbers() ?>
      <?= $this->Paginator->next(__('next') . ' >') ?>
      <?= $this->Paginator->last(__('last') . ' >>') ?>
    </ul>
    <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
  </div>
</div>
