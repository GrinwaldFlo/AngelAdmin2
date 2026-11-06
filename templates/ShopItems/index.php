<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ShopItem> $shopItems
 * @var \App\Model\Entity\Site|null $shopSite
 */
$this->Html->css('shop', ['block' => true]);
?>
<div class="shopItems index content">
    <?= $this->Html->link('', ['action' => 'add'], ['class' => 'float-end gg-add-r', 'title' => __('New Shop Item')]) ?>
    <?= $this->element('Shop/title', ['title' => __('Shop Items'), 'shopSite' => $shopSite]) ?>
    <div class="table-responsive">
        <table class="table table-striped shop-table">
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('label') ?></th>
                    <th><?= $this->Paginator->sort('price') ?></th>
                    <th><?= $this->Paginator->sort('category') ?></th>
                    <th class="shop-th-text"><?= __('Attributes') ?></th>
                    <th><?= $this->Paginator->sort('active') ?></th>
                    <th><?= $this->Paginator->sort('allow_out_of_stock', __('Out of stock')) ?></th>
                    <th class="shop-th-text actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($shopItems as $shopItem): ?>
                    <tr>
                        <td class="shop-cell-muted"><?= $this->Number->format($shopItem->id) ?></td>
                        <td class="shop-cell-main"><?= h($shopItem->label) ?></td>
                        <td><?= $this->Number->currency($shopItem->price, 'CHF') ?></td>
                        <td class="shop-cell-muted"><?= h($shopItem->category_label) ?></td>
                        <td><?= $this->element('Shop/attributes', ['attributes' => $shopItem->getAttributeList()]) ?></td>
                        <td>
                            <span class="badge <?= $shopItem->active ? 'bg-success' : 'bg-secondary' ?>"><?= $shopItem->active ? __('Active') : __('Inactive') ?></span>
                        </td>
                        <td>
                            <?php if ($shopItem->allow_out_of_stock): ?>
                                <span class="badge bg-info"><?= __('Can be ordered when out of stock') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="actions shop-actions">
                            <?= $this->Html->link('visibility', ['action' => 'view', $shopItem->id], ['class' => 'material-icons shop-action-icon', 'title' => __('View')]) ?>
                            <?= $this->Html->link('edit', ['action' => 'edit', $shopItem->id], ['class' => 'material-icons shop-action-icon', 'title' => __('Edit')]) ?>
                            <?= $this->Form->postLink('delete', ['action' => 'delete', $shopItem->id], ['confirm' => __('Are you sure you want to delete # {0}?', $shopItem->id), 'class' => 'material-icons-outlined shop-action-icon shop-action-delete', 'title' => __('Delete')]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $this->element('Shop/paginator') ?>
</div>
