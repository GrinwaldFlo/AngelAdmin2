<?php
/**
 * Orders page, detail: every order line, delete an order or change its attribute
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\MemberOrder> $memberOrders
 * @var int $itemFilter
 * @var int $deliveredFilter
 * @var array $itemOptions
 * @var \App\Model\Entity\Site|null $shopSite
 */
$this->Html->css('member-orders', ['block' => true]);
$this->Html->css('shop', ['block' => true]);

$filterGroups = [
    ['label' => __('Item:'), 'current' => $itemFilter, 'options' => $itemOptions],
    ['label' => __('Delivery:'), 'current' => $deliveredFilter, 'options' => [1 => __('Delivered'), 2 => __('Not Delivered')]],
];
$filterValues = array_column($filterGroups, 'current');
$sortQuery = array_intersect_key($this->request->getQueryParams(), array_flip(['sort', 'direction']));
?>
<?= $this->element('Shop/tabs', ['active' => 'index']) ?>
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
    <?= $this->element('Shop/title', ['title' => __('Orders'), 'shopSite' => $shopSite, 'count' => $memberOrders->totalCount()]) ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover table-sm mo-table">
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('Members.first_name', __('Member')) ?></th>
                    <th><?= $this->Paginator->sort('ShopItems.label', __('Item')) ?></th>
                    <th><?= $this->Paginator->sort('attribute', __('Attribute')) ?></th>
                    <th><?= $this->Paginator->sort('quantity') ?></th>
                    <th><?= $this->Paginator->sort('delivered') ?></th>
                    <th><?= $this->Paginator->sort('Bills.id', __('Bill')) ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th class="mo-th-text actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($memberOrders as $memberOrder): ?>
                    <?php $item = $memberOrder->shop_item; ?>
                    <tr>
                        <td class="mo-id"><?= $this->Number->format($memberOrder->id) ?></td>
                        <td class="mo-member"><?= $this->element('Shop/member_link', ['member' => $memberOrder->member ?? null]) ?></td>
                        <td class="mo-item"><?= h($item->label) ?></td>
                        <td class="mo-attribute">
                            <?php if (!$memberOrder->delivered && $item->hasAttributes()): ?>
                                <?php
                                $choices = array_combine($item->getAttributeList(), $item->getAttributeList());
                                // A removed attribute stays the current value of the order
                                if ($memberOrder->attribute !== null && !isset($choices[$memberOrder->attribute])) {
                                    $choices = [$memberOrder->attribute => $memberOrder->attribute] + $choices;
                                }
                                ?>
                                <?= $this->Form->create(null, ['url' => ['action' => 'changeAttribute', $memberOrder->id], 'class' => 'shop-change-attribute']) ?>
                                <?= $this->Form->select('attribute', $choices, [
                                    'value' => $memberOrder->attribute,
                                    'empty' => $memberOrder->attribute === null ? __('-- Choose --') : false,
                                    'class' => 'form-select form-select-sm',
                                    'aria-label' => __('Attribute'),
                                ]) ?>
                                <?= $this->Form->button(__('Change'), ['class' => 'btn btn-sm btn-save']) ?>
                                <?= $this->Form->end() ?>
                            <?php elseif ($memberOrder->attribute !== null): ?>
                                <span class="badge bg-light text-dark shop-attribute"><?= h($memberOrder->attribute) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="mo-quantity"><?= $this->Number->format($memberOrder->quantity) ?></td>
                        <td class="mo-delivered">
                            <?php if ($memberOrder->delivered): ?>
                                <span class="badge bg-success"><?= __('Delivered') ?></span>
                            <?php else: ?>
                                <span class="badge bg-warning"><?= __('Not Delivered') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="mo-bill"><?= $this->element('Shop/bill_link', ['bill' => $memberOrder->bill ?? null, 'withStatus' => true]) ?></td>
                        <td class="mo-created"><?= h($memberOrder->created) ?></td>
                        <td class="actions mo-actions">
                            <?php
                            $confirm = $memberOrder->hasValue('bill')
                                ? __('Delete this order? Its bill {0} will not be changed: an email will be sent to correct it.', $memberOrder->bill->reference)
                                : __('Are you sure you want to delete # {0}?', $memberOrder->id);
                            ?>
                            <?= $this->my->iconPostLink('delete', ['action' => 'delete', $memberOrder->id], __('Delete'), ['confirm' => $confirm, 'class' => 'icon-btn-delete']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $this->element('Shop/paginator') ?>
</div>
