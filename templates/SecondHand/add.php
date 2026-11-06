<?php
/**
 * New item for sale
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SecondHandItem $item
 * @var array<int, string> $categories
 * @var string $accept accepted picture types
 * @var string $formats accepted picture formats, for the user
 * @var bool $isHashUser
 */
$this->Html->css('second-hand', ['block' => true]);
?>
<div class="content">
    <?= $this->element('SecondHand/nav', ['active' => 'myItems', 'isHashUser' => $isHashUser]) ?>
    <h3><?= __('Add an item') ?></h3>
    <p><?= __('All fields are required.') ?></p>
    <?= $this->Form->create($item, ['type' => 'file']) ?>
    <?= $this->Form->control('category_id', ['label' => __('Category'), 'options' => $categories, 'empty' => __('Choose a category'), 'required' => true]) ?>
    <?= $this->Form->control('description', ['label' => __('Description'), 'type' => 'textarea', 'rows' => 4, 'required' => true]) ?>
    <?= $this->Form->control('size', ['label' => __('Size'), 'required' => true]) ?>
    <?= $this->Form->control('price', ['label' => __('Price (CHF)'), 'type' => 'number', 'step' => '0.05', 'min' => 0, 'required' => true]) ?>
    <?= $this->Form->control('picture', ['label' => __('Picture'), 'type' => 'file', 'accept' => $accept, 'required' => true, 'help' => $formats]) ?>
    <?= $this->Form->button(__('Put up for sale'), ['class' => 'btn btn-save']) ?>
    <?= $this->Form->end() ?>
</div>
