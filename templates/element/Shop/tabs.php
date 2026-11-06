<?php
/**
 * Tabs of the orders page: detail and summary
 * @var \App\View\AppView $this
 * @var string $active 'index' or 'summary'
 */
$tabs = ['index' => __('Detail'), 'summary' => __('Summary and stock')];
?>
<ul class="nav nav-tabs shop-tabs">
    <?php foreach ($tabs as $action => $label): ?>
        <li class="nav-item">
            <?= $this->Html->link($label, ['controller' => 'ShopOrders', 'action' => $action], ['class' => 'nav-link' . ($active === $action ? ' active' : '')]) ?>
        </li>
    <?php endforeach; ?>
</ul>
