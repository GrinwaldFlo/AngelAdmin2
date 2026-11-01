<?php
/**
 * Seller details
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SecondHandProfile $profile
 * @var bool $isHashUser
 */
$this->Html->css('second-hand', ['block' => true]);
?>
<div class="content">
    <h3><?= __('Second-hand') ?></h3>
    <?= $this->element('SecondHand/nav', ['active' => 'myItems', 'isHashUser' => $isHashUser]) ?>
    <h3><?= __('My seller details') ?></h3>
    <p><?= __('Buyers see these details to contact you.') ?></p>
    <?= $this->Form->create($profile) ?>
    <?= $this->Form->control('name', ['label' => __('Name shown to buyers'), 'required' => true]) ?>
    <?= $this->Form->control('phone', ['label' => __('Phone number'), 'type' => 'tel', 'required' => true]) ?>
    <?= $this->Form->control('whatsapp', ['type' => 'checkbox', 'label' => __('Prefer to be contacted by WhatsApp')]) ?>
    <?= $this->Form->control('email', ['label' => __('Email'), 'type' => 'email', 'required' => true,
        'help' => __('If you use another email than the one of your member account, you will get a link to confirm it.')]) ?>
    <?= $this->Form->control('city', ['label' => __('City'), 'required' => true]) ?>
    <?= $this->Form->button(__('Save'), ['class' => 'btn btn-primary']) ?>
    <?= $this->Form->end() ?>
</div>
