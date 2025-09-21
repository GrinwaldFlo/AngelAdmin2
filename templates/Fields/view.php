<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Field $field
 */
?>
<div class="row">
    <aside class="col-4">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Field'), ['action' => 'edit', $field->member_id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Field'), ['action' => 'delete', $field->member_id], ['confirm' => __('Are you sure you want to delete # {0}?', $field->member_id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Fields'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Field'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="col-8">
        <div class="fields view content">
            <h3><?= h($field->member_id) ?></h3>
            <table class="table table-striped table-hover table-sm">
                <tr>
                    <th><?= __('Member') ?></th>
                    <td><?= $field->has('member') ? $this->Html->link($field->member->id, ['controller' => 'Members', 'action' => 'view', $field->member->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Field Type') ?></th>
                    <td><?= $field->has('field_type') ? $this->Html->link($field->field_type->id, ['controller' => 'FieldTypes', 'action' => 'view', $field->field_type->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Value') ?></th>
                    <td><?= h($field->value) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
