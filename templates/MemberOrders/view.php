<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\MemberOrder $memberOrder
 */
?>
<?php $this->Html->css('shop', ['block' => true]); ?>
<div class="row">
    <aside class="col-md-3">
        <div class="side-nav shop-side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?php if (!$memberOrder->delivered) : ?>
                <?= $this->Form->postLink(__('Mark Delivered'), ['action' => 'markDelivered', $memberOrder->id], ['confirm' => __('Mark this order as delivered?'), 'class' => 'btn btn-add']) ?>
            <?php else : ?>
                <?= $this->Form->postLink(__('Mark Not Delivered'), ['action' => 'markNotDelivered', $memberOrder->id], ['confirm' => __('Mark this order as not delivered?'), 'class' => 'btn btn-delete']) ?>
            <?php endif; ?>
            <?= $this->Html->link(__('List Member Orders'), ['action' => 'index'], ['class' => 'btn btn-nav']) ?>
        </div>
    </aside>
    <div class="col-md-9">
        <div class="memberOrders view content">
            <h3><?= __('Member Order #{0}', $memberOrder->id) ?></h3>
            <table class="table">
                <tr>
                    <th><?= __('Member') ?></th>
                    <td><?= $this->element('Shop/member_link', ['member' => $memberOrder->member ?? null]) ?></td>
                </tr>
                <tr>
                    <th><?= __('Shop Item') ?></th>
                    <td><?= $memberOrder->hasValue('shop_item') ? $this->Html->link($memberOrder->shop_item->label, ['controller' => 'ShopItems', 'action' => 'view', $memberOrder->shop_item->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Attribute') ?></th>
                    <td><?= h($memberOrder->attribute ?? '-') ?></td>
                </tr>
                <tr>
                    <th><?= __('Item Price') ?></th>
                    <td><?= $memberOrder->hasValue('shop_item') ? $this->Number->currency($memberOrder->shop_item->price, 'CHF') : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Quantity') ?></th>
                    <td><?= $this->Number->format($memberOrder->quantity) ?></td>
                </tr>
                <tr>
                    <th><?= __('Total Amount') ?></th>
                    <td><?= $memberOrder->hasValue('shop_item') ? $this->Number->currency($memberOrder->shop_item->price * $memberOrder->quantity, 'CHF') : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Delivered') ?></th>
                    <td>
                        <?php if ($memberOrder->delivered) : ?>
                            <span class="badge bg-success"><?= __('Yes') ?></span>
                        <?php else : ?>
                            <span class="badge bg-warning"><?= __('No') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?= __('Bill') ?></th>
                    <td>
                        <?= $this->element('Shop/bill_link', ['bill' => $memberOrder->bill ?? null, 'withStatus' => true]) ?>
                    </td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($memberOrder->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($memberOrder->modified) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
