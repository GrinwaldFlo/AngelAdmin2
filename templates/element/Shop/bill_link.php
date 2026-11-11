<?php
/**
 * Bill of an order, a link only for the roles seeing all the bills
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Bill|null $bill
 * @var \App\Model\Entity\Role $curRole
 * @var bool $withStatus also show the status badge
 */
$withStatus ??= false;
?>
<?php if (empty($bill)) : ?>
    <span class="text-muted"><?= __('No Bill') ?></span>
<?php else : ?>
    <span class="mo-bill-label"><?= __('Bill') ?></span>
    <?php if (!empty($curRole->BillViewAll)) : ?>
        <?= $this->Html->link($bill->reference, ['controller' => 'Bills', 'action' => 'view', $bill->id]) ?>
    <?php else : ?>
        <?= h($bill->reference) ?>
    <?php endif; ?>
    <?php if ($withStatus) : ?>
        <span class="badge bg-<?= h($bill->statusHtml) ?>"><?= h($bill->statusString) ?></span>
    <?php endif; ?>
<?php endif; ?>
