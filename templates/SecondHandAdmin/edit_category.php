<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SecondHandCategory $category
 */
?>
<div class="content">
    <?= $this->backButtonCtrl('SecondHandAdmin', 'index') ?>
    <h3><?= __('Edit category') ?></h3>
    <?= $this->Form->create($category) ?>
    <?= $this->Form->control('name', ['label' => __('Name')]) ?>
    <?= $this->Form->button(__('Save'), ['class' => 'btn btn-save']) ?>
    <?= $this->Form->end() ?>
</div>
