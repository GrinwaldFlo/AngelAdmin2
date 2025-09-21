<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ShopItem> $shopItems
 */
?>
<div class="shopItems index content">
    <?= $this->Html->link('', ['action' => 'add'], ['class' => 'float-end gg-add-r']) ?>
    <h3><?= __('Shop Items') ?></h3>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('label') ?></th>
                    <th><?= $this->Paginator->sort('price') ?></th>
                    <th><?= $this->Paginator->sort('category') ?></th>
                    <th><?= $this->Paginator->sort('active') ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($shopItems as $shopItem): ?>
                    <tr>
                        <td><?= $this->Number->format($shopItem->id) ?></td>
                        <td><?= h($shopItem->label) ?></td>
                        <td><?= $this->Number->currency($shopItem->price, 'CHF') ?></td>
                        <td><?= h($shopItem->category_label) ?></td>
                        <td><?= $shopItem->active ? __('Yes') : __('No'); ?></td>
                        <td><?= h($shopItem->created) ?></td>
                        <td class="actions">
                            <?= $this->Html->link('visibility', ['action' => 'view', $shopItem->id], ['class' => 'material-icons']) ?>
                            <?= $this->Html->link('edit', ['action' => 'edit', $shopItem->id], ['class' => 'material-icons']) ?>
                            <?= $this->Form->postLink('delete', ['action' => 'delete', $shopItem->id], ['confirm' => __('Are you sure you want to delete # {0}?', $shopItem->id), 'class' => 'material-icons-outlined btn-outline-danger']) ?>
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

