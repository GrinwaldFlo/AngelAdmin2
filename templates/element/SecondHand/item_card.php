<?php

/**
 * Item in a list: picture, price, size
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SecondHandItem $item
 * @var int $categoryId category filter of the list, kept to browse it from the item (0 = all)
 */
$categoryId ??= 0;
$query = $categoryId > 0 ? ['category' => $categoryId] : [];
?>
<a class="sh-card" href="<?= $this->Url->build(['controller' => 'SecondHand', 'action' => 'view', $item->id, '?' => $query]) ?>">
    <?= $this->Html->image($item->pictureImage(), ['class' => 'sh-card-picture', 'alt' => $item->category->name, 'loading' => 'lazy']) ?>
    <span class="sh-card-body">
        <span class="sh-price"><?= $this->Number->currency($item->price, 'CHF') ?></span>
        <span class="sh-muted"><?= h($item->category->name) ?> · <?= __('Size') ?> <?= h($item->size) ?></span>
    </span>
</a>
