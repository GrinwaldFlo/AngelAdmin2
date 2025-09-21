<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FieldType[]|\Cake\Collection\CollectionInterface $fieldTypes
 */
?>
<div class="fieldTypes index content">
  <?= $this->my->adminButtons(3); ?>

  <?= $this->Html->link(__('New Field'), ['action' => 'add'], ['class' => 'button float-end']) ?>
  <h3><?= __('Fields') ?></h3>
  <table class="table table-striped table-hover table-sm">
    <thead>
      <tr>
        <th><?= $this->Paginator->sort('order') ?></th>
        <th><?= $this->Paginator->sort('label') ?></th>
        <th><?= $this->Paginator->sort('style') ?></th>
        <th><?= $this->Paginator->sort('member_edit', __('Member can edit')) ?></th>
        <th><?= $this->Paginator->sort('hidden') ?></th>
        <th><?= $this->Paginator->sort('mandatory') ?></th>
        <th class="actions"><?= __('Actions') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($fieldTypes as $fieldType): ?>
        <tr>
          <td><?= $this->Number->format($fieldType->sort) ?></td>
          <td><?= h($fieldType->label) ?></td>
          <td><?= h($fieldType->style_str) ?></td>
          <td><?= $this->Number->format($fieldType->member_edit) ?></td>
          <td><?= $this->Number->format($fieldType->hidden) ?></td>
          <td><?= $this->Number->format($fieldType->mandatory) ?></td>
          <td class="actions">
            <?= $this->Html->link(__('View'), ['action' => 'view', $fieldType->id]) ?>
            <?= $this->Html->link(__('Edit'), ['action' => 'edit', $fieldType->id]) ?>
            <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $fieldType->id], ['confirm' => __('Are you sure you want to delete # {0}?', $fieldType->id)]) ?>
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
