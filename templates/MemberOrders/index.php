<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\MemberOrder> $memberOrders
 * @var int $categoryFilter
 * @var int $deliveredFilter
 * @var int $paidFilter
 * @var array $categoryOptions
 */

use App\Model\Table\ShopItemsTable;

$this->Html->css('member-orders', ['block' => true]);

$filterGroups = [
    ['label' => __('Category:'), 'current' => $categoryFilter, 'class' => 'btn-primary', 'options' => $categoryOptions],
    ['label' => __('Delivery:'), 'current' => $deliveredFilter, 'class' => 'btn-secondary', 'options' => [1 => __('Delivered'), 2 => __('Not Delivered')]],
    ['label' => __('Payment:'), 'current' => $paidFilter, 'class' => 'btn-info', 'options' => [1 => __('Paid'), 2 => __('Not Paid')]],
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
                <?= $this->Html->link($label, ['action' => 'index', ...$values, '?' => $sortQuery], ['class' => 'btn btn-sm ' . $group['class'] . ($group['current'] == $value ? ' pressed' : '')]) ?>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>

<div class="memberOrders index content">
    <div class="mo-header">
        <h3><?= __('Member Orders') ?></h3>
        <span class="mo-count">(<?= $this->Number->format($memberOrders->totalCount()) ?>)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-striped mo-table">
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('Members.first_name', __('Member')) ?></th>
                    <th><?= $this->Paginator->sort('ShopItems.label', __('Item')) ?></th>
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
                        <td class="mo-member">
                            <?= $memberOrder->hasValue('member') ? $this->Html->link($memberOrder->member->fullName, ['controller' => 'Members', 'action' => 'view', $memberOrder->member->id]) : '' ?>
                        </td>
                        <td class="mo-item"><?= $memberOrder->hasValue('shop_item') ? h($memberOrder->shop_item->label) : '' ?></td>
                        <td class="mo-category"><?= $memberOrder->hasValue('shop_item') ? h(ShopItemsTable::getCategoryLabel($memberOrder->shop_item->category)) : '' ?></td>
                        <td class="mo-quantity"><?= $this->Number->format($memberOrder->quantity) ?></td>
                        <td class="mo-delivered">
                            <?php if ($memberOrder->delivered): ?>
                                <span class="badge bg-success"><?= __('Delivered') ?></span>
                            <?php else: ?>
                                <span class="badge bg-warning"><?= __('Not Delivered') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="mo-bill">
                            <?php if ($memberOrder->hasValue('bill')): ?>
                                <span class="mo-bill-label"><?= __('Bill') ?></span>
                                <?= $this->Html->link($memberOrder->bill->reference, ['controller' => 'Bills', 'action' => 'view', $memberOrder->bill->id]) ?>
                            <?php else: ?>
                                <span class="text-muted"><?= __('No Bill') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="mo-status">
                            <?php if ($memberOrder->hasValue('bill')): ?>
                                <span class="badge bg-<?= h($memberOrder->bill->statusHtml) ?>"><?= h($memberOrder->bill->statusString) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="mo-created"><?= h($memberOrder->created) ?></td>
                        <td class="actions mo-actions">
                            <?php if (!$memberOrder->delivered): ?>
                                <?= $this->Form->postLink(__('Mark Delivered'), ['action' => 'markDelivered', $memberOrder->id], ['confirm' => __('Mark this order as delivered?'), 'class' => 'btn btn-sm btn-success mo-action-main']) ?>
                            <?php else: ?>
                                <?= $this->Form->postLink(__('Mark Not Delivered'), ['action' => 'markNotDelivered', $memberOrder->id], ['confirm' => __('Mark this order as not delivered?'), 'class' => 'btn btn-sm btn-warning mo-action-main']) ?>
                            <?php endif; ?>
                            <?= $this->Html->link('visibility', ['action' => 'view', $memberOrder->id], ['class' => 'material-icons mo-action-icon', 'title' => __('View')]) ?>
                            <?= $this->Form->postLink('delete', ['action' => 'delete', $memberOrder->id], ['confirm' => __('Are you sure you want to delete # {0}?', $memberOrder->id), 'class' => 'material-icons-outlined btn-outline-danger mo-action-icon', 'title' => __('Delete')]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <ul class="pagination mo-pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
</div>
