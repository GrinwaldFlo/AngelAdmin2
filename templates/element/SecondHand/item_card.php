<?php
/**
 * Item in a list: picture, price, size
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SecondHandItem $item
 */
?>
<a class="sh-card" href="<?= $this->Url->build(['controller' => 'SecondHand', 'action' => 'view', $item->id]) ?>">
    <img class="sh-card-picture" src="<?= h($item->pictureUrl()) ?>" alt="<?= h($item->category->name) ?>" loading="lazy" />
    <span class="sh-card-body">
        <span class="sh-price"><?= $this->Number->currency($item->price, 'CHF') ?></span>
        <span class="sh-muted"><?= h($item->category->name) ?> · <?= __('Size') ?> <?= h($item->size) ?></span>
    </span>
</a>
