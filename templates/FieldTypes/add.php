<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FieldType $fieldType
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
        <div class="fieldTypes form content">
            <?= $this->Form->create($fieldType) ?>
            <fieldset>
                <legend><?= __('Add Field') ?></legend>
                <?php
                    echo $this->Form->control('sort', [ 'label' => __x('Like alphabetical order','Order')]);
                    echo $this->Form->control('label', [ 'label' => __x('noun','Label')]);
                    echo $this->Form->control('style', ['options' => $fieldType->StyleList, 'label' => __('Style')]);
                    echo $this->Form->control('member_edit', ['label' => __('Allow member to edit')]);
                    echo $this->Form->control('hidden', ['label' => __('Hidden')]);
                    echo $this->Form->control('mandatory', ['label' => __('Mandatory')]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
