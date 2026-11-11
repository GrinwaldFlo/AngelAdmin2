<?php
/**
 * Item with the contact of the seller
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SecondHandItem $item
 * @var bool $isOwn
 * @var bool $isAdmin
 * @var bool $forSale
 * @var bool $isHashUser
 * @var int $categoryId category filter of the list the buyer comes from (0 = all)
 * @var array{prev: int|null, next: int|null} $neighbours
 */
$this->Html->css('second-hand', ['block' => true]);
$query = $categoryId > 0 ? ['category' => $categoryId] : [];
$backUrl = $categoryId > 0 ? ['action' => 'index', $categoryId] : ['action' => 'index'];
// Previous / next item, disabled at the ends of the list
$stepLink = function (string $label, ?int $id) use ($query): string {
    if ($id === null) {
        return $this->Html->tag('a', h($label), ['class' => 'btn btn-nav btn-sm disabled', 'aria-disabled' => 'true']);
    }

    return $this->Html->link($label, ['action' => 'view', $id, '?' => $query], ['class' => 'btn btn-nav btn-sm']);
};
$seller = $item->profile;
$price = $this->Number->currency($item->price, 'CHF');
$subject = __('Second-hand: {0}', $item->category->name . ' (' . __('Size') . ' ' . $item->size . ')');
$body = __("Hello {0},\n\nI am interested in your item on the second-hand page of {1}:\n{2}, size {3}, {4}\n\nIs it still available?\n\nThank you", $seller->name, $config['clubName'], $item->category->name, $item->size, $price);
$mailto = 'mailto:' . $seller->email . '?subject=' . rawurlencode($subject) . '&body=' . rawurlencode($body);
?>
<div class="content">
    <?= $this->element('SecondHand/nav', ['active' => '', 'isHashUser' => $isHashUser]) ?>
    <div class="sh-pager">
        <?= $this->Html->link('< ' . __('Back'), $backUrl, ['class' => 'btn btn-nav btn-sm']) ?>
        <span class="sh-pager-steps">
            <?= $stepLink('< ' . __('previous'), $neighbours['prev']) ?>
            <?= $stepLink(__('next') . ' >', $neighbours['next']) ?>
        </span>
    </div>
    <?php if (!$forSale) : ?>
        <div class="alert alert-warning"><?= __('This item is not shown to buyers (sold, expired or email not confirmed).') ?></div>
    <?php endif; ?>
    <div class="row">
        <div class="col-md-7">
            <?= $this->Html->image($item->pictureImage(), ['class' => 'sh-picture', 'alt' => $item->category->name]) ?>
        </div>
        <div class="col-md-5">
            <h3><?= h($item->category->name) ?></h3>
            <p class="sh-price sh-price-large"><?= $price ?></p>
            <p><strong><?= __('Size') ?>:</strong> <?= h($item->size) ?></p>
            <p class="sh-description"><?= nl2br(h($item->description)) ?></p>
            <p class="sh-muted"><?= __('For sale since {0}', $item->created->i18nFormat('dd.MM.yyyy')) ?></p>

            <h4><?= __('Seller') ?></h4>
            <p><?= h($seller->name) ?><br /><?= h($seller->city) ?></p>
            <div class="sh-contact">
                <?= $this->Html->link(__('Call {0}', $seller->phone), 'tel:' . $seller->phoneLink(), ['class' => 'btn btn-nav']) ?>
                <?php if ($seller->whatsapp) : ?>
                    <?= $this->Html->link(__('WhatsApp'), 'https://wa.me/' . $seller->whatsappNumber(), ['class' => 'btn btn-nav', 'target' => '_blank', 'rel' => 'noopener']) ?>
                <?php endif; ?>
                <?= $this->Html->link(__('Send an email'), $mailto, ['class' => 'btn btn-nav']) ?>
            </div>
            <p class="sh-muted"><?= __('No money goes through the club: you arrange the payment and the delivery with the seller.') ?></p>

            <?php if ($isOwn && $item->sold === null) : ?>
                <?= $this->Form->postLink(__('Mark as sold'), ['action' => 'sold', $item->id], ['class' => 'btn btn-add']) ?>
            <?php endif; ?>
            <?php if ($isOwn) : ?>
                <?= $this->Form->postLink(__('Remove'), ['action' => 'delete', $item->id], ['class' => 'btn btn-delete', 'confirm' => __('Remove this item for good?')]) ?>
            <?php elseif ($isAdmin) : ?>
                <?= $this->Form->postLink(__('Delete'), ['controller' => 'SecondHandAdmin', 'action' => 'deleteItem', $item->id], ['class' => 'btn btn-delete', 'confirm' => __('Delete this item for good?')]) ?>
            <?php endif; ?>
        </div>
    </div>
</div>
