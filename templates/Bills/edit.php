<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Bill $bill
 */
//$disabled = curRole // 'disabled' => 'disabled'
?>
<div class="row">
  <div class="col">
    <div class="bills form content">
      <?= $this->Form->create($bill) ?>
      <fieldset>
        <legend><?= __('Edit invoice') ?></legend>
        <?php
//                    echo $this->Form->control('member_id', ['options' => $members]);
        echo $this->Form->control('label', ['label' => __x('Label on the invoice', 'Denomination')]);
        echo $this->Form->control('amount', ['label' => __('Amount')]);
        echo $this->Form->control('due_date', ['label' => __('Due date')]);
//                    echo $this->Form->control('due_date_ori');
        echo $this->Form->control('link_membership_fee', ['label' => __('Linked to a membership')]);

        if ($curRole->BillValidate)
        {
          echo $this->Form->control('printed', ['label' => __('Printed'), 'disabled' => 'disabled']);
          echo $this->Form->control('paid', ['label' => __('Paid')]);
          echo $this->Form->control('reminder', ['label' => __('No. of reminders')]);
          echo $this->Form->control('canceled', ['label' => __('Canceled')]);
          echo $this->Form->control('state_id', ['options' => $bill->StateList, 'label' => __x('Status of the invoice (send, draft,...)', 'State')]);
        }
//                    echo $this->Form->control('tokenhash');
//                    echo $this->Form->control('confirmation', ['empty' => true]);
        echo $this->Form->control('site_id', ['options' => $sites]);
        ?>
      </fieldset>
      <?= $this->Form->button(__('Submit')) ?>
      <?= $this->Form->end() ?>
    </div>
  </div>
</div>
