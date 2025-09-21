<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\BillTemplate[]|\Cake\Collection\CollectionInterface $billTemplates
 */
?>
<div class="billTemplates index content">
  <?= $this->Html->link(__('New invoice Template'), ['action' => 'add'], ['class' => 'button float-end']) ?>
  <h3><?= __('Invoice Templates') ?></h3>
  <table class="table table-striped table-hover table-sm">
    <thead>
      <tr>
        <th><?= $this->Paginator->sort('id') ?></th>
        <th><?= $this->Paginator->sort('label') ?></th>
        <th><?= $this->Paginator->sort('amount') ?></th>
        <th><?= $this->Paginator->sort('membership_fee') ?></th>
        <th><?= $this->Paginator->sort('site_id') ?></th>
        <th class="actions"><?= __('Actions') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($billTemplates as $billTemplate): ?>
        <tr>
          <td><?= $this->Number->format($billTemplate->id) ?></td>
          <td><?= h($billTemplate->label) ?></td>
          <td><?= $this->Number->format($billTemplate->amount) ?></td>
          <td><?= h($billTemplate->membership_fee) ?></td>
          <td><?= $billTemplate->has('site') ? $this->Html->link($billTemplate->site->city, ['controller' => 'Sites', 'action' => 'view', $billTemplate->site->id]) : '' ?></td>
          <td class="actions">
            <?= $this->Html->link(__('View'), ['action' => 'view', $billTemplate->id]) ?>
            <?= $this->Html->link(__('Edit'), ['action' => 'edit', $billTemplate->id]) ?>
            <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $billTemplate->id], ['confirm' => __('Are you sure you want to delete # {0}?', $billTemplate->id)]) ?>
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
