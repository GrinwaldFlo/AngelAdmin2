<?php
/**
 * Items for sale
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SecondHandItem[]|\Cake\Collection\CollectionInterface $items
 * @var array<int, string> $categories
 * @var int $categoryId 0 = all
 * @var bool $isHashUser
 */
$this->Html->css('second-hand', ['block' => true]);
?>
<div class="content">
    <h3><?= __('Second-hand') ?></h3>
    <?= $this->element('SecondHand/nav', ['active' => 'index', 'isHashUser' => $isHashUser]) ?>

    <div class="sh-filters">
        <?= $this->Html->link(__('All'), ['action' => 'index'], ['class' => 'btn btn-primary btn-sm' . ($categoryId == 0 ? ' pressed' : '')]) ?>
        <?php foreach ($categories as $id => $name): ?>
            <?= $this->Html->link($name, ['action' => 'index', $id], ['class' => 'btn btn-primary btn-sm' . ($categoryId == $id ? ' pressed' : '')]) ?>
        <?php endforeach; ?>
    </div>

    <?php if ($items->count() == 0): ?>
        <p class="sh-muted"><?= __('No items for sale right now.') ?></p>
    <?php else: ?>
        <div class="sh-grid">
            <?php foreach ($items as $item): ?>
                <?= $this->element('SecondHand/item_card', compact('item', 'categoryId')) ?>
            <?php endforeach; ?>
        </div>
        <?php if ($this->Paginator->total() > 1): ?>
            <ul class="pagination">
                <?= $this->Paginator->prev('< ' . __('previous')) ?>
                <?= $this->Paginator->numbers() ?>
                <?= $this->Paginator->next(__('next') . ' >') ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>
</div>
