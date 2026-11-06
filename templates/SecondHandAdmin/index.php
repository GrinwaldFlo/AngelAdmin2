<?php
/**
 * Settings and categories of the second-hand items
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\SecondHandCategory> $categories with their number of items
 * @var bool $enabled
 * @var int $months
 * @var \App\Model\Entity\SecondHandCategory $category new category
 */
$this->Html->css('second-hand', ['block' => true]);
?>
<div class="content">
    <div class="sh-header">
        <h3><?= __('Second-hand') ?></h3>
        <span>
            <?= $this->Html->link(__('Log of the items'), ['action' => 'itemsLog'], ['class' => 'btn btn-nav']) ?>
            <?php if ($enabled): ?>
                <?= $this->Html->link(__('Open the second-hand page'), ['controller' => 'SecondHand', 'action' => 'index'], ['class' => 'btn btn-nav']) ?>
            <?php endif; ?>
        </span>
    </div>

    <h4><?= __('Settings') ?></h4>
    <?= $this->Form->create(null, ['url' => ['action' => 'index']]) ?>
    <?= $this->Form->control('enabled', ['type' => 'checkbox', 'checked' => $enabled, 'label' => __('Enable the second-hand page')]) ?>
    <?= $this->Form->control('expiry_months', ['type' => 'number', 'min' => 1, 'max' => 120, 'value' => $months, 'label' => __('Items expire after (months)')]) ?>
    <?= $this->Form->button(__('Save'), ['class' => 'btn btn-save']) ?>
    <?= $this->Form->end() ?>

    <h4 class="sh-section"><?= __('Categories') ?></h4>
    <table class="table table-sm table-hover">
        <thead>
            <tr>
                <th><?= __('Name') ?></th>
                <th><?= __('Items') ?></th>
                <th class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $row): ?>
                <tr>
                    <td><?= h($row->name) ?></td>
                    <td><?= (int)$row->items ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('Edit'), ['action' => 'editCategory', $row->id]) ?>
                        <?php if ($row->items == 0): ?>
                            <?= $this->Form->postLink(__('Delete'), ['action' => 'deleteCategory', $row->id], ['confirm' => __('Delete the category {0}?', $row->name)]) ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?= $this->Form->create($category, ['url' => ['action' => 'addCategory']]) ?>
    <div class="sh-inline-form">
        <?= $this->Form->control('name', ['label' => false, 'placeholder' => __('New category'), 'required' => true]) ?>
        <?= $this->Form->button(__('Add'), ['class' => 'btn btn-add']) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
