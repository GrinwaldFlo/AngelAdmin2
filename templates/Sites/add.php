<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Site $site
 */
?>
<div class="row">
    <aside class="col-4">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Sites'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="col-8">
        <div class="sites form content">
            <?= $this->Form->create($site) ?>
            <fieldset>
                <legend><?= __('Add Site') ?></legend>
                <?php
                    echo $this->Form->control('city');
                    echo $this->Form->control('address');
                    echo $this->Form->control('account_designation');
                    echo $this->Form->control('postcode');
                    echo $this->Form->control('iban');
                    echo $this->Form->control('bic');
                    echo $this->Form->control('feeMax');
                    echo $this->Form->control('reminder_penalty');
                    echo $this->Form->control('sender_email');
                    echo $this->Form->control('sender');
                    echo $this->Form->control('sender_phone');
                    echo $this->Form->control('add_invoice_num');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
