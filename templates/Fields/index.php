<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Field[]|\Cake\Collection\CollectionInterface $fields
 */
?>
<div class="fields index content">
  <?= $this->Html->link(__('New Field'), ['action' => 'add'], ['class' => 'button float-end']) ?>
  <h3><?= __('Fields') ?></h3>
  <table class="table table-striped table-hover table-sm">
    <thead>
      <tr>
        <th><?= $this->Paginator->sort('member_id') ?></th>
        <th><?= $this->Paginator->sort('field_type_id') ?></th>
        <th><?= $this->Paginator->sort('value') ?></th>
        <th class="actions"><?= __('Actions') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($fields as $field): ?>
        <tr>
          <td><?= $field->has('member') ? $this->Html->link($field->member->id, ['controller' => 'Members', 'action' => 'view', $field->member->id]) : '' ?></td>
          <td><?= $field->has('field_type') ? $this->Html->link($field->field_type->id, ['controller' => 'FieldTypes', 'action' => 'view', $field->field_type->id]) : '' ?></td>
          <td><?= h($field->value) ?></td>
          <td class="actions">
            <?= $this->Html->link(__('View'), ['action' => 'view', $field->member_id]) ?>
            <?= $this->Html->link(__('Edit'), ['action' => 'edit', $field->member_id]) ?>
            <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $field->member_id], ['confirm' => __('Are you sure you want to delete # {0}?', $field->member_id)]) ?>
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
