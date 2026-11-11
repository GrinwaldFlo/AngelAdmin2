<?php

/**
 * Orders page, summary: ordered items by item and attribute, with the stock
 * The member orders are deducted from the stock when placed: a negative stock is the quantity to order
 * @var \App\View\AppView $this
 * @var array $rows see ShopStocksTable::summary()
 * @var bool $onlyToOrder
 * @var \App\Model\Entity\Site|null $shopSite
 */
use App\Controller\ShopOrdersController;

$this->Html->css('shop', ['block' => true]);
?>
<?= $this->element('Shop/tabs', ['active' => 'summary']) ?>
<div class="content shop-summary">
    <?= $this->element('Shop/title', ['title' => __('Summary and stock'), 'shopSite' => $shopSite]) ?>
    <p class="text-muted shop-intro">
        <?= __('The member orders are deducted from the stock when they are placed: a negative stock is the quantity to order from the supplier.') ?>
    </p>
    <div class="mo-filter">
        <?= $this->Html->link(__('All'), ['action' => 'summary'], ['class' => 'btn btn-sm btn-filter' . ($onlyToOrder ? '' : ' pressed')]) ?>
        <?= $this->Html->link(__('Only to order'), ['action' => 'summary', '?' => ['toOrder' => 1]], ['class' => 'btn btn-sm btn-filter' . ($onlyToOrder ? ' pressed' : '')]) ?>
    </div>

    <?php if ($rows === []) : ?>
        <p class="shop-empty"><?= __('Nothing to show.') ?></p>
    <?php else : ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm shop-summary-table">
                <thead>
                    <tr>
                        <th><?= __('Item') ?></th>
                        <th><?= __('Attribute') ?></th>
                        <th class="shop-number"><?= __('Not given') ?></th>
                        <th class="shop-number"><?= __('Stock') ?></th>
                        <th class="shop-number"><?= __('On order') ?></th>
                        <th class="shop-number"><?= __('To order') ?></th>
                        <th class="actions"><?= __('Stock update') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <?php
                        $stock = $row['stock'];
                        $toOrder = $stock->toOrder();
                        ?>
                        <tr class="<?= $toOrder > 0 ? 'shop-row-to-order' : '' ?>">
                            <td class="shop-summary-item"><?= h($row['item']->label) ?></td>
                            <td class="shop-summary-attribute">
                                <?php if ($row['attribute'] !== '') : ?>
                                    <span class="badge bg-light text-dark shop-attribute"><?= h($row['attribute']) ?></span>
                                <?php endif; ?>
                                <?php if (!$row['offered']) : ?>
                                    <span class="badge bg-secondary"><?= __('No longer offered') ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="shop-number" data-label="<?= h(__('Not given')) ?>"><?= $this->Number->format($row['pending']) ?></td>
                            <td class="shop-number <?= $stock->quantity < 0 ? 'shop-negative' : '' ?>" data-label="<?= h(__('Stock')) ?>"><?= $this->Number->format($stock->quantity) ?></td>
                            <td class="shop-number" data-label="<?= h(__('On order')) ?>"><?= $this->Number->format($stock->on_order) ?></td>
                            <td class="shop-number shop-to-order" data-label="<?= h(__('To order')) ?>"><?= $this->Number->format($toOrder) ?></td>
                            <td class="shop-stock-actions">
                                <?= $this->Form->create(null, ['url' => ['action' => 'updateStock'], 'class' => 'shop-stock-form']) ?>
                                <?= $this->Form->hidden('shop_item_id', ['value' => $row['item']->id]) ?>
                                <?= $this->Form->hidden('attribute', ['value' => $row['attribute']]) ?>
                                <?= $this->Form->hidden('toOrder', ['value' => $onlyToOrder ? 1 : 0]) ?>
                                <?= $this->Form->number('quantity', [
                                    'value' => $toOrder > 0 ? $toOrder : '',
                                    'required' => true,
                                    'step' => 1,
                                    'inputmode' => 'numeric',
                                    'class' => 'form-control form-control-sm shop-stock-quantity',
                                    'aria-label' => __('Quantity'),
                                ]) ?>
                                <?= $this->Form->button(__('Ordered'), ['name' => 'operation', 'value' => ShopOrdersController::STOCK_ORDERED, 'class' => 'btn btn-sm btn-save', 'title' => __('Ordered from the supplier')]) ?>
                                <?= $this->Form->button(__('Received'), ['name' => 'operation', 'value' => ShopOrdersController::STOCK_RECEIVED, 'class' => 'btn btn-sm btn-add', 'title' => __('Received from the supplier, added to the stock')]) ?>
                                <?= $this->Form->button(__('Set stock'), ['name' => 'operation', 'value' => ShopOrdersController::STOCK_ADJUST, 'class' => 'btn btn-sm btn-edit', 'title' => __('Counted stock, replaces the current one')]) ?>
                                <?= $this->Form->end() ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
