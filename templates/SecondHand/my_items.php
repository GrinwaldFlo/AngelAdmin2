<?php
/**
 * Items of the seller
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SecondHandItem[]|\Cake\Collection\CollectionInterface $items
 * @var \App\Model\Entity\SecondHandProfile $profile
 * @var int $months expiration duration
 * @var bool $isHashUser
 */
$this->Html->css('second-hand', ['block' => true]);
?>
<div class="content">
    <?= $this->element('SecondHand/nav', ['active' => 'myItems', 'isHashUser' => $isHashUser]) ?>
    <div class="sh-header">
        <h3><?= __('My items') ?></h3>
        <?php if ($profile->canSell()): ?>
            <?= $this->Html->link(__('Add an item'), ['action' => 'add'], ['class' => 'btn btn-primary']) ?>
        <?php endif; ?>
    </div>

    <?php if (!$profile->email_verified): ?>
        <div class="alert alert-warning">
            <?= __('Please confirm your email address {0} with the link we sent you. Your items are not shown to buyers until then.', h($profile->email)) ?>
            <?= $this->Html->link(__('Change my details or send the link again'), ['action' => 'profile']) ?>
        </div>
    <?php endif; ?>

    <p>
        <?= __('Shown to buyers as {0}, {1}', h($profile->name), h($profile->city)) ?>
        · <?= $this->Html->link(__('Change my details'), ['action' => 'profile']) ?>
    </p>
    <p class="sh-muted"><?= __('Items are removed from the list after {0} months.', $months) ?></p>

    <?php if ($items->count() == 0): ?>
        <p class="sh-muted"><?= __('You have no items for sale.') ?></p>
    <?php else: ?>
        <table class="table table-sm table-hover">
            <thead>
                <tr>
                    <th><?= __('Picture') ?></th>
                    <th><?= __('Item') ?></th>
                    <th><?= __('Price') ?></th>
                    <th><?= __('Status') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <?php $expired = $item->isExpired($months); ?>
                    <tr>
                        <td><?= $this->Html->image($item->pictureImage(), ['class' => 'sh-thumb', 'alt' => '', 'loading' => 'lazy']) ?></td>
                        <td>
                            <?= $this->Html->link($item->category->name, ['action' => 'view', $item->id]) ?>
                            <div class="sh-muted"><?= __('Size') ?> <?= h($item->size) ?></div>
                        </td>
                        <td><?= $this->Number->currency($item->price, 'CHF') ?></td>
                        <td>
                            <?php if ($item->sold !== null): ?>
                                <?= __('Sold') ?>
                            <?php elseif ($expired): ?>
                                <?= __('Expired') ?>
                            <?php else: ?>
                                <?= __('For sale') ?>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <?php if ($item->sold === null && !$expired): ?>
                                <?= $this->Form->postLink(__('Mark as sold'), ['action' => 'sold', $item->id], ['class' => 'btn btn-sm btn-primary']) ?>
                            <?php endif; ?>
                            <?= $this->Form->postLink(__('Remove'), ['action' => 'delete', $item->id], ['class' => 'btn btn-sm btn-danger', 'confirm' => __('Remove this item for good?')]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
