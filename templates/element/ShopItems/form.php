<?php
/**
 * Shop item form, shared by add and edit
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ShopItem $shopItem
 * @var array $categoryOptions
 * @var string $legend
 */
$this->Html->css('shop', ['block' => true]);
?>
<div class="row">
    <aside class="col-md-3">
        <div class="side-nav shop-side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?php if (!$shopItem->isNew()): ?>
                <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $shopItem->id], ['confirm' => __('Are you sure you want to delete # {0}?', $shopItem->id), 'class' => 'btn btn-delete']) ?>
            <?php endif; ?>
            <?= $this->Html->link(__('List Shop Items'), ['action' => 'index'], ['class' => 'btn btn-nav']) ?>
        </div>
    </aside>
    <div class="col-md-9">
        <div class="shopItems form content">
            <?= $this->Form->create($shopItem) ?>
            <fieldset>
                <legend><?= h($legend) ?></legend>
                <?php
                echo $this->Form->control('label', ['class' => 'form-control']);
                echo $this->Form->control('price', ['type' => 'number', 'step' => '0.01', 'class' => 'form-control']);
                echo $this->Form->control('category', [
                    'type' => 'select',
                    'options' => $categoryOptions,
                    'empty' => __('-- Select Category --'),
                    'class' => 'form-control',
                ]);
                echo $this->Form->control('attributes', [
                    'type' => 'textarea',
                    'rows' => 5,
                    'class' => 'form-control',
                    'label' => __('Attributes'),
                    'placeholder' => "S\nM\nL\nXL",
                    'help' => __('One choice per line (e.g. sizes). The member must choose one. Leave empty if there is nothing to choose.'),
                ]);
                echo $this->Form->control('active', ['type' => 'checkbox']);
                echo $this->Form->control('allow_out_of_stock', [
                    'type' => 'checkbox',
                    'label' => __('Can be ordered when out of stock'),
                    'help' => __('Otherwise the attributes without stock are hidden from the members.'),
                ]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit'), ['class' => 'btn btn-save']) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
