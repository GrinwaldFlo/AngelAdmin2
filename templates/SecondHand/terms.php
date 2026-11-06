<?php
/**
 * @var \App\View\AppView $this
 * @var bool $isHashUser
 */
$this->Html->css('second-hand', ['block' => true]);
?>
<div class="content">
    <h3><?= __('Second-hand') ?></h3>
    <p><?= __('Buy and sell used items (uniforms, shoes, ...) with other members.') ?></p>
    <h4><?= __('Rules') ?></h4>
    <ul class="sh-rules">
        <li><?= __('This platform only puts buyers and sellers in contact.') ?></li>
        <li><?= __('No money goes through the club.') ?></li>
        <li><?= __('Buyers and sellers arrange to meet or ship the item on their own.') ?></li>
        <li><?= __('All communication happens outside this website.') ?></li>
        <li><?= __('We recommend meeting in person, so you can check the item and handle the payment safely.') ?></li>
    </ul>
    <?= $this->Form->create(null) ?>
    <?= $this->Form->control('accept', ['type' => 'checkbox', 'label' => __('I accept these rules'), 'required' => true]) ?>
    <?= $this->Form->button(__('Continue'), ['class' => 'btn btn-save']) ?>
    <?= $this->Form->end() ?>
</div>
