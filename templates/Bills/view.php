<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Bill $bill
 */
?>
<div class="row">
  <div class="col">
    <div class="bills view content">
      <h3><?= h($bill->Reference) ?></h3>
      <table class="table table-striped table-hover table-sm">
        <tr>
          <th><?= __('Member') ?></th>
          <td><?= $bill->has('member') ? $this->Html->link($bill->member->FullName, ['controller' => 'Members', 'action' => 'view', $bill->member->id]) : '' ?></td>
        </tr>
        <tr>
          <th><?= __('Location') ?></th>
          <td><?= h($bill->site->city) ?></td>
        </tr>
        <tr>
          <th><?= __x('Label on the invoice', 'Denomination') ?></th>
          <td><?= h($bill->label) ?></td>
        </tr>
        <tr>
          <th><?= __x('Status of the invoice (send, draft,...)', 'State') ?></th>
          <td><?= $bill->has('state') ? $bill->state->name : '' ?></td>
        </tr>
        <tr>
          <th><?= __('Amount') ?></th>
          <td><?= $this->Number->currency($bill->amount, 'CHF') ?></td>
        </tr>
        <tr>
          <th><?= __('No. of reminders') ?></th>
          <td><?= $this->Number->format($bill->reminder) ?></td>
        </tr>
        <tr>
          <th><?= __('Due date') ?></th>
          <td><?= h($bill->due_date) ?></td>
        </tr>
        <tr>
          <th><?= __('Original due date') ?></th>
          <td><?= h($bill->due_date_ori) ?></td>
        </tr>
        <tr>
          <th><?= __('Created') ?></th>
          <td><?= h($bill->created) ?></td>
        </tr>
        <tr>
          <th><?= __('Modified') ?></th>
          <td><?= h($bill->modified) ?></td>
        </tr>
        <tr>
          <th><?= __('Confirmation') ?></th>
          <td><?= h($bill->confirmation) ?></td>
        </tr>
        <tr>
          <th><?= __('Printed') ?></th>
          <td><?= $bill->printed ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
          <th><?= __('Paid') ?></th>
          <td><?= $bill->paid ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
          <th><?= __('Linked to a membership') ?></th>
          <td><?= $bill->link_membership_fee ? __('Yes') : __('No'); ?></td>
        </tr>
        <tr>
          <th><?= __('Canceled') ?></th>
          <td><?= $bill->canceled ? __('Yes') : __('No'); ?></td>
        </tr>
      </table>
    </div>
  </div>
</div>
