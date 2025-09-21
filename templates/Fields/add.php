<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Field $field
 */
?>
<div class="row">
    <aside class="col-4">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Fields'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="col-8">
        <div class="fields form content">
            <?= $this->Form->create($field) ?>
            <fieldset>
                <legend><?= __('Add Field') ?></legend>
                <?php
                    echo $this->Form->control('value');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
