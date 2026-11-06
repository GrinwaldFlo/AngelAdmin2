<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\MemberOrder> $memberOrders
 * @var int $categoryFilter
 * @var int $deliveredFilter
 * @var int $paidFilter
 * @var int $itemFilter
 * @var array $categoryOptions
 * @var array $itemOptions
 * @var \App\Model\Entity\Site|null $shopSite
 */

use App\Model\Table\ShopItemsTable;

$this->Html->css('member-orders', ['block' => true]);
$this->Html->css('shop', ['block' => true]);

$filterGroups = [
    ['label' => __('Category:'), 'current' => $categoryFilter, 'options' => $categoryOptions],
    ['label' => __('Delivery:'), 'current' => $deliveredFilter, 'options' => [1 => __('Delivered'), 2 => __('Not Delivered')]],
    ['label' => __('Payment:'), 'current' => $paidFilter, 'options' => [1 => __('Paid'), 2 => __('Not Paid')]],
    ['label' => __('Item:'), 'current' => $itemFilter, 'options' => $itemOptions],
];
$filterValues = array_column($filterGroups, 'current');
$sortQuery = array_intersect_key($this->request->getQueryParams(), array_flip(['sort', 'direction']));
?>
<div class="memberOrders filter content">
    <?php foreach ($filterGroups as $position => $group): ?>
        <div class="mo-filter">
            <strong class="mo-filter-label"><?= $group['label'] ?></strong>
            <?php foreach ([0 => __('All')] + $group['options'] as $value => $label): ?>
                <?php
                $values = $filterValues;
                // Pressing the selected filter again removes it
                $values[$position] = $group['current'] == $value ? 0 : $value;
                ?>
                <?= $this->Html->link($label, ['action' => 'index', ...$values, '?' => $sortQuery], ['class' => 'btn btn-sm btn-filter' . ($group['current'] == $value ? ' pressed' : '')]) ?>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>

<div class="memberOrders index content">
    <?= $this->element('Shop/title', ['title' => __('Member Orders'), 'shopSite' => $shopSite, 'count' => $memberOrders->totalCount()]) ?>
    <p class="text-muted shop-intro"><?= __('Mark the items as given to the members. The orders are deleted on the orders page.') ?></p>
    <div class="table-responsive">
        <table class="table table-striped table-hover table-sm mo-table">
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('Members.first_name', __('Member')) ?></th>
                    <th><?= $this->Paginator->sort('ShopItems.label', __('Item')) ?></th>
                    <th><?= $this->Paginator->sort('attribute', __('Attribute')) ?></th>
                    <th><?= $this->Paginator->sort('ShopItems.category', __('Category')) ?></th>
                    <th><?= $this->Paginator->sort('quantity') ?></th>
                    <th><?= $this->Paginator->sort('delivered') ?></th>
                    <th><?= $this->Paginator->sort('Bills.id', __('Bill')) ?></th>
                    <th class="mo-th-text"><?= __('Bill Status') ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th class="mo-th-text actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($memberOrders as $memberOrder): ?>
                    <tr>
                        <td class="mo-id"><?= $this->Number->format($memberOrder->id) ?></td>
                        <td class="mo-member"><?= $this->element('Shop/member_link', ['member' => $memberOrder->member ?? null]) ?></td>
                        <td class="mo-item"><?= $memberOrder->hasValue('shop_item') ? h($memberOrder->shop_item->label) : '' ?></td>
                        <td class="mo-attribute"><?= $memberOrder->attribute === null ? '' : '<span class="badge bg-light text-dark shop-attribute">' . h($memberOrder->attribute) . '</span>' ?></td>
                        <td class="mo-category"><?= $memberOrder->hasValue('shop_item') ? h(ShopItemsTable::getCategoryLabel($memberOrder->shop_item->category)) : '' ?></td>
                        <td class="mo-quantity"><?= $this->Number->format($memberOrder->quantity) ?></td>
                        <td class="mo-delivered">
                            <?php if ($memberOrder->delivered): ?>
                                <span class="badge bg-success"><?= __('Delivered') ?></span>
                            <?php else: ?>
                                <span class="badge bg-warning"><?= __('Not Delivered') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="mo-bill"><?= $this->element('Shop/bill_link', ['bill' => $memberOrder->bill ?? null]) ?></td>
                        <td class="mo-status">
                            <?php if ($memberOrder->hasValue('bill')): ?>
                                <span class="badge bg-<?= h($memberOrder->bill->statusHtml) ?>"><?= h($memberOrder->bill->statusString) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="mo-created"><?= h($memberOrder->created) ?></td>
                        <td class="actions mo-actions">
                            <?php if (!$memberOrder->delivered): ?>
                                <?= $this->Form->postLink(__('Mark Delivered'), ['action' => 'markDelivered', $memberOrder->id], ['confirm' => __('Mark this order as delivered?'), 'class' => 'btn btn-sm btn-add mo-action-main']) ?>
                            <?php else: ?>
                                <?= $this->Form->postLink(__('Mark Not Delivered'), ['action' => 'markNotDelivered', $memberOrder->id], ['confirm' => __('Mark this order as not delivered?'), 'class' => 'btn btn-sm btn-delete mo-action-main']) ?>
                            <?php endif; ?>
                            <?= $this->Html->link('visibility', ['action' => 'view', $memberOrder->id], ['class' => 'material-icons mo-action-icon', 'title' => __('View')]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $this->element('Shop/paginator') ?>
</div>
