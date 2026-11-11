<?php
/**
 * Log of all the second-hand items
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SecondHandLog[]|\Cake\Collection\CollectionInterface $logs
 * @var array<int, int> $existingIds items not deleted yet
 * @var int $months expiration duration
 */
$this->Html->css('second-hand', ['block' => true]);
?>
<div class="content">
    <?= $this->backButtonCtrl('SecondHandAdmin', 'index') ?>
    <h3><?= __('Log of the second-hand items') ?></h3>
    <table class="table table-sm table-striped table-hover">
        <thead>
            <tr>
                <th><?= __('Date') ?></th>
                <th><?= __('Seller') ?></th>
                <th><?= __('Category') ?></th>
                <th><?= __('Description') ?></th>
                <th><?= __('Size') ?></th>
                <th><?= __('Price') ?></th>
                <th><?= __('Status') ?></th>
                <th class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log) : ?>
                <tr>
                    <td><?= $log->created ? h($log->created->i18nFormat('dd.MM.yyyy')) : '' ?></td>
                    <td>
                        <?= $log->member_id
                            ? $this->Html->link($log->seller_name, ['controller' => 'Members', 'action' => 'view', $log->member_id])
                            : h($log->seller_name) ?>
                    </td>
                    <td><?= h($log->category) ?></td>
                    <td class="sh-log-description"><?= h($log->description) ?></td>
                    <td><?= h($log->size) ?></td>
                    <td><?= $this->Number->currency($log->price, 'CHF') ?></td>
                    <td>
                        <?= h($log->statusLabel($months)) ?>
                        <?php if ($log->closed) : ?>
                            <div class="sh-muted"><?= h($log->closed->i18nFormat('dd.MM.yyyy')) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <?php if (isset($existingIds[$log->item_id])) : ?>
                            <?= $this->Html->link(__('View'), ['controller' => 'SecondHand', 'action' => 'view', $log->item_id]) ?>
                            <?= $this->Form->postLink(__('Delete'), ['action' => 'deleteItem', $log->item_id], ['confirm' => __('Delete this item for good?')]) ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <ul class="pagination">
        <?= $this->Paginator->prev('< ' . __('previous')) ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next(__('next') . ' >') ?>
    </ul>
</div>
