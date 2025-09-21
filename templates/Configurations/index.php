<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Configuration[]|\Cake\Collection\CollectionInterface $configurations
 */
?>
<div class="configurations index content">
  <?= $this->my->adminButtons(1); ?>
  <h3><?= __('Configurations') ?></h3>
  <table class="table table-striped table-hover table-sm">
    <thead>
      <tr>
        <th><?= $this->Paginator->sort('id') ?></th>
        <th><?= $this->Paginator->sort('label') ?></th>
        <th><?= $this->Paginator->sort('value') ?></th>
        <th class="actions"><?= __('Actions') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($configurations as $configuration): ?>
        <tr>
          <td><?= $this->Number->format($configuration->id) ?></td>
          <td><?= h($configuration->label) ?></td>
          <td><?= h($configuration->value) ?></td>
          <td class="actions">
            <?= $this->Html->link(__('View'), ['action' => 'view', $configuration->id]) ?>
            <?= $this->Html->link(__('Edit'), ['action' => 'edit', $configuration->id]) ?>
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
