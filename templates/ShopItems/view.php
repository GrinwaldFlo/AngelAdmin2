<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ShopItem $shopItem
 */
?>
<div class="row">
    <aside class="col-md-3">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Shop Item'), ['action' => 'edit', $shopItem->id], ['class' => 'btn btn-secondary']) ?>
            <?= $this->Form->postLink(__('Delete Shop Item'), ['action' => 'delete', $shopItem->id], ['confirm' => __('Are you sure you want to delete # {0}?', $shopItem->id), 'class' => 'btn btn-danger']) ?>
            <?= $this->Html->link(__('List Shop Items'), ['action' => 'index'], ['class' => 'btn btn-primary']) ?>
            <?= $this->Html->link(__('New Shop Item'), ['action' => 'add'], ['class' => 'btn btn-primary']) ?>
        </div>
    </aside>
    <div class="col-md-9">
        <div class="shopItems view content">
            <h3><?= h($shopItem->label) ?></h3>
            <table class="table">
                <tr>
                    <th><?= __('Label') ?></th>
                    <td><?= h($shopItem->label) ?></td>
                </tr>
                <tr>
                    <th><?= __('Price') ?></th>
                    <td><?= $this->Number->currency($shopItem->price, 'CHF') ?></td>
                </tr>
                <tr>
                    <th><?= __('Category') ?></th>
                    <td><?= h($shopItem->category_label) ?></td>
                </tr>
                <tr>
                    <th><?= __('Active') ?></th>
                    <td><?= $shopItem->active ? __('Yes') : __('No') ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($shopItem->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($shopItem->modified) ?></td>
                </tr>
            </table>
            <?php if (!empty($shopItem->member_orders)): ?>
            <div class="related">
                <h4><?= __('Related Member Orders') ?></h4>
                <div class="table-responsive">
                    <table class="table">
                        <tr>
                            <th><?= __('Member') ?></th>
                            <th><?= __('Quantity') ?></th>
                            <th><?= __('Delivered') ?></th>
                            <th><?= __('Created') ?></th>
                        </tr>
                        <?php foreach ($shopItem->member_orders as $memberOrder): ?>
                        <tr>
                            <td><?= $memberOrder->hasValue('member') ? $this->Html->link($memberOrder->member->fullName, ['controller' => 'Members', 'action' => 'view', $memberOrder->member->id]) : '' ?></td>
                            <td><?= h($memberOrder->quantity) ?></td>
                            <td><?= $memberOrder->delivered ? __('Yes') : __('No') ?></td>
                            <td><?= h($memberOrder->created) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
