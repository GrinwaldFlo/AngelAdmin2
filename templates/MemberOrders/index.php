<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\MemberOrder> $memberOrders
 * @var int $categoryFilter
 * @var int $deliveredFilter
 * @var int $paidFilter
 * @var array $categoryOptions
 */
?>
<div class="row">
    <div class="col">
        <div class="memberOrders filter content">
            <!-- Category Filter -->
            <div class="mb-3">
                <strong><?= __('Category:') ?></strong>
                <?= $this->Html->link(__('All'), ['action' => 'index', 0, $deliveredFilter, $paidFilter], ['class' => 'btn btn-primary btn-sm' . ($categoryFilter == 0 ? ' pressed' : '')]) ?>
                <?php foreach ($categoryOptions as $catId => $catLabel): ?>
                    <?= $this->Html->link($catLabel, ['action' => 'index', $categoryFilter == $catId ? 0 : $catId, $deliveredFilter, $paidFilter], ['class' => 'btn btn-primary btn-sm' . ($categoryFilter == $catId ? ' pressed' : '')]) ?>
                <?php endforeach; ?>
            </div>
            
            <!-- Delivery Status Filter -->
            <div class="mb-3">
                <strong><?= __('Delivery:') ?></strong>
                <?= $this->Html->link(__('All'), ['action' => 'index', $categoryFilter, 0, $paidFilter], ['class' => 'btn btn-secondary btn-sm' . ($deliveredFilter == 0 ? ' pressed' : '')]) ?>
                <?= $this->Html->link(__('Delivered'), ['action' => 'index', $categoryFilter, $deliveredFilter == 1 ? 0 : 1, $paidFilter], ['class' => 'btn btn-secondary btn-sm' . ($deliveredFilter == 1 ? ' pressed' : '')]) ?>
                <?= $this->Html->link(__('Not Delivered'), ['action' => 'index', $categoryFilter, $deliveredFilter == 2 ? 0 : 2, $paidFilter], ['class' => 'btn btn-secondary btn-sm' . ($deliveredFilter == 2 ? ' pressed' : '')]) ?>
            </div>
            
            <!-- Payment Status Filter -->
            <div class="mb-3">
                <strong><?= __('Payment:') ?></strong>
                <?= $this->Html->link(__('All'), ['action' => 'index', $categoryFilter, $deliveredFilter, 0], ['class' => 'btn btn-info btn-sm' . ($paidFilter == 0 ? ' pressed' : '')]) ?>
                <?= $this->Html->link(__('Paid'), ['action' => 'index', $categoryFilter, $deliveredFilter, $paidFilter == 1 ? 0 : 1], ['class' => 'btn btn-info btn-sm' . ($paidFilter == 1 ? ' pressed' : '')]) ?>
                <?= $this->Html->link(__('Not Paid'), ['action' => 'index', $categoryFilter, $deliveredFilter, $paidFilter == 2 ? 0 : 2], ['class' => 'btn btn-info btn-sm' . ($paidFilter == 2 ? ' pressed' : '')]) ?>
            </div>
        </div>
    </div>
</div>

<div class="memberOrders index content">
    <h3><?= __('Member Orders') ?></h3>
    <div class="d-inline"><?= '(' . count($memberOrders) . ')' ?></div>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('Members.first_name', __('Member')) ?></th>
                    <th><?= $this->Paginator->sort('ShopItems.label', __('Item')) ?></th>
                    <th><?= $this->Paginator->sort('ShopItems.category', __('Category')) ?></th>
                    <th><?= $this->Paginator->sort('quantity') ?></th>
                    <th><?= $this->Paginator->sort('delivered') ?></th>
                    <th><?= $this->Paginator->sort('Bills.id', __('Bill')) ?></th>
                    <th><?= __('Bill Status') ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($memberOrders as $memberOrder): ?>
                    <tr>
                        <td><?= $this->Number->format($memberOrder->id) ?></td>
                        <td>
                            <?= $memberOrder->hasValue('member') ? $this->Html->link($memberOrder->member->fullName, ['controller' => 'Members', 'action' => 'view', $memberOrder->member->id]) : '' ?>
                        </td>
                        <td><?= $memberOrder->hasValue('shop_item') ? h($memberOrder->shop_item->label) : '' ?></td>
                        <td>
                            <?php if ($memberOrder->hasValue('shop_item')): ?>
                                <?php 
                                $shopItemsTable = \Cake\ORM\TableRegistry::getTableLocator()->get('ShopItems');
                                echo h($shopItemsTable->getCategoryLabel($memberOrder->shop_item->category));
                                ?>
                            <?php endif; ?>
                        </td>
                        <td><?= $this->Number->format($memberOrder->quantity) ?></td>
                        <td>
                            <?php if ($memberOrder->delivered): ?>
                                <span class="badge bg-success"><?= __('Yes') ?></span>
                            <?php else: ?>
                                <span class="badge bg-warning"><?= __('No') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($memberOrder->hasValue('bill')): ?>
                                <?= $this->Html->link($memberOrder->bill->reference, ['controller' => 'Bills', 'action' => 'view', $memberOrder->bill->id]) ?>
                            <?php else: ?>
                                <span class="text-muted"><?= __('No Bill') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($memberOrder->hasValue('bill')): ?>
                                <span class="badge bg-<?= h($memberOrder->bill->statusHtml) ?>"><?= h($memberOrder->bill->statusString) ?></span>
                            <?php else: ?>
                                                    -
                            <?php endif; ?>
                        </td>
                        <td><?= h($memberOrder->created) ?></td>
                        <td class="actions">
                            <?= $this->Html->link('visibility', ['action' => 'view', $memberOrder->id], ['class' => 'material-icons']) ?>
                            <?php if (!$memberOrder->delivered): ?>
                                <?= $this->Form->postLink(__('Mark Delivered'), ['action' => 'markDelivered', $memberOrder->id], ['confirm' => __('Mark this order as delivered?'), 'class' => 'btn btn-sm btn-success']) ?>
                            <?php else: ?>
                                <?= $this->Form->postLink(__('Mark Not Delivered'), ['action' => 'markNotDelivered', $memberOrder->id], ['confirm' => __('Mark this order as not delivered?'), 'class' => 'btn btn-sm btn-warning']) ?>
                            <?php endif; ?>
                            <?= $this->Form->postLink('delete', ['action' => 'delete', $memberOrder->id], ['confirm' => __('Are you sure you want to delete # {0}?', $memberOrder->id), 'class' => 'material-icons-outlined btn-outline-danger']) ?>

                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
</div>
