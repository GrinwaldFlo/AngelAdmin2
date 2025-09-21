<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FieldType $fieldType
 */
?>
<div class="row">
  <aside class="col-4">
    <div class="side-nav">
      <h4 class="heading"><?= __('Actions') ?></h4>
      <?= $this->Html->link(__('Edit Field'), ['action' => 'edit', $fieldType->id], ['class' => 'side-nav-item']) ?>
      <?= $this->Form->postLink(__('Delete Field'), ['action' => 'delete', $fieldType->id], ['confirm' => __('Are you sure you want to delete # {0}?', $fieldType->id), 'class' => 'side-nav-item']) ?>
      <?= $this->Html->link(__('List Fields'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
      <?= $this->Html->link(__('New Field'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
    </div>
  </aside>
  <div class="col-8">
    <div class="fieldTypes view content">
      <h3><?= h($fieldType->id) ?></h3>
      <table class="table table-striped table-hover table-sm">
        <tr>
          <th><?= __x('noun', 'Label') ?></th>
          <td><?= h($fieldType->label) ?></td>
        </tr>
        <tr>
          <th><?= __x('Like alphabetical order', 'Order') ?></th>
          <td><?= $this->Number->format($fieldType->sort) ?></td>
        </tr>
        <tr>
          <th><?= __('Id') ?></th>
          <td><?= $this->Number->format($fieldType->id) ?></td>
        </tr>
        <tr>
          <th><?= __('Style') ?></th>
          <td><?= h($fieldType->StyleStr) ?></td>
        </tr>
        <tr>
          <th><?= __('Member can edit') ?></th>
          <td><?= $this->Number->format($fieldType->member_edit) ?></td>
        </tr>
        <tr>
          <th><?= __('Hidden') ?></th>
          <td><?= $this->Number->format($fieldType->hidden) ?></td>
        </tr>
        <tr>
          <th><?= __('Mandatory') ?></th>
          <td><?= $this->Number->format($fieldType->mandatory) ?></td>
        </tr>
      </table>
    </div>
  </div>
</div>
