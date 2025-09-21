<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ShopItem $shopItem
 * @var array $categoryOptions
 */
?>
<div class="row">
    <aside class="col-md-3">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $shopItem->id], ['confirm' => __('Are you sure you want to delete # {0}?', $shopItem->id), 'class' => 'btn btn-danger']) ?>
            <?= $this->Html->link(__('List Shop Items'), ['action' => 'index'], ['class' => 'btn btn-primary']) ?>
        </div>
    </aside>
    <div class="col-md-9">
        <div class="shopItems form content">
            <?= $this->Form->create($shopItem) ?>
            <fieldset>
                <legend><?= __('Edit Shop Item') ?></legend>
                <?php
                echo $this->Form->control('label', ['class' => 'form-control']);
                echo $this->Form->control('price', ['type' => 'number', 'step' => '0.01', 'class' => 'form-control']);
                echo $this->Form->control('category', [
                    'type' => 'select',
                    'options' => $categoryOptions,
                    'empty' => __('-- Select Category --'),
                    'class' => 'form-control'
                ]);
                echo $this->Form->control('active', ['type' => 'checkbox']);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit'), ['class' => 'btn btn-primary']) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
