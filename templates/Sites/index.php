<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Site[]|\Cake\Collection\CollectionInterface $sites
 */
?>
<div class="sites index content">
  <?= $this->my->adminButtons(2); ?>
  <?= $this->Html->link(__('New Site'), ['action' => 'add'], ['class' => 'button float-end']) ?>
  <h3><?= __x('Physical location', 'Sites') ?></h3>
  <table class="table table-striped table-hover table-sm">
    <thead>
      <tr>
        <th><?= $this->Paginator->sort('city') ?></th>
        <th><?= $this->Paginator->sort('account_designation') ?></th>
        <th><?= $this->Paginator->sort('feeMax') ?></th>
        <th><?= $this->Paginator->sort('reminder_penalty') ?></th>
        <th><?= $this->Paginator->sort('sender') ?></th>
        <th class="actions"><?= __('Actions') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($sites as $site): ?>
        <tr>
          <td><?= h($site->city) ?></td>
          <td><?= h($site->account_designation) ?></td>
          <td><?= $this->Number->format($site->feeMax) ?></td>
          <td><?= $this->Number->format($site->reminder_penalty) ?></td>
          <td><?= h($site->sender) ?></td>
          <td class="actions">
            <?= $this->Html->link(__('View'), ['action' => 'view', $site->id]) ?>
            <?= $this->Html->link(__('Edit'), ['action' => 'edit', $site->id]) ?>
            <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $site->id], ['confirm' => __('Are you sure you want to delete # {0}?', $site->id)]) ?>
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
