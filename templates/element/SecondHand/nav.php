<?php
/**
 * Menu of the second-hand pages
 * @var \App\View\AppView $this
 * @var bool $isHashUser member with its link, it has no main menu
 * @var string $active action of the current page
 */
$links = [
    'index' => __('Buy'),
    'myItems' => __('Sell'),
];
?>
<nav class="sh-nav">
    <?php foreach ($links as $action => $label) : ?>
        <?= $this->Html->link($label, ['controller' => 'SecondHand', 'action' => $action], ['class' => 'btn btn-filter btn-sm' . ($action === $active ? ' pressed' : '')]) ?>
    <?php endforeach; ?>
    <?php if ($isHashUser) : ?>
        <?= $this->Html->link(__('My page'), ['controller' => 'Members', 'action' => 'myPage'], ['class' => 'btn btn-sm btn-nav']) ?>
    <?php elseif (!empty($curRole->Admin)) : ?>
        <?= $this->Html->link(__('Admin'), ['controller' => 'SecondHandAdmin', 'action' => 'index'], ['class' => 'btn btn-sm btn-nav']) ?>
    <?php endif; ?>
</nav>
