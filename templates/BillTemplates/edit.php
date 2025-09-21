<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\BillTemplate $billTemplate
 */
?>
<div class="row">
    <aside class="col-4">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $billTemplate->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $billTemplate->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List invoice Templates'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="col-8">
        <div class="billTemplates form content">
            <?= $this->Form->create($billTemplate) ?>
            <fieldset>
                <legend><?= __('Edit invoice Template') ?></legend>
                <?php
                    echo $this->Form->control('label');
                    echo $this->Form->control('amount');
                    echo $this->Form->control('membership_fee');
                    echo $this->Form->control('site_id', ['options' => $sites]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
