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
      <?= $this->Html->link(__('Edit Site'), ['action' => 'edit', $site->id], ['class' => 'side-nav-item']) ?>
      <?= $this->Form->postLink(__('Delete Site'), ['action' => 'delete', $site->id], ['confirm' => __('Are you sure you want to delete # {0}?', $site->id), 'class' => 'side-nav-item']) ?>
      <?= $this->Html->link(__('List Sites'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
      <?= $this->Html->link(__('New Site'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
    </div>
  </aside>
  <div class="col-8">
    <div class="sites view content">
      <h3><?= h($site->id) ?></h3>
      <table class="table table-striped table-hover table-sm">
        <tr>
          <th><?= __('City') ?></th>
          <td><?= h($site->city) ?></td>
        </tr>
        <tr>
          <th><?= __('Address') ?></th>
          <td><?= h($site->address) ?></td>
        </tr>
        <tr>
          <th><?= __('Account Designation') ?></th>
          <td><?= h($site->account_designation) ?></td>
        </tr>
        <tr>
          <th><?= __('Postcode') ?></th>
          <td><?= h($site->postcode) ?></td>
        </tr>
        <tr>
          <th><?= __('IBAN') ?></th>
          <td><?= h($site->iban) ?></td>
        </tr>
        <tr>
          <th><?= __('BIC') ?></th>
          <td><?= h($site->bic) ?></td>
        </tr>
        <tr>
          <th><?= __('Sender Email') ?></th>
          <td><?= h($site->sender_email) ?></td>
        </tr>
        <tr>
          <th><?= __('Sender') ?></th>
          <td><?= h($site->sender) ?></td>
        </tr>
        <tr>
          <th><?= __('Sender Phone') ?></th>
          <td><?= h($site->sender_phone) ?></td>
        </tr>
        <tr>
          <th><?= __('Id') ?></th>
          <td><?= $this->Number->format($site->id) ?></td>
        </tr>
        <tr>
          <th><?= __('Max fees') ?></th>
          <td><?= $this->Number->format($site->feeMax) ?></td>
        </tr>
        <tr>
          <th><?= __('Reminder Penalty') ?></th>
          <td><?= $this->Number->format($site->reminder_penalty) ?></td>
        </tr>
        <tr>
          <th><?= __x('Reference like "invoice number"','Add to invoice reference') ?></th>
          <td><?= $this->Number->format($site->add_invoice_num) ?></td>
        </tr>
      </table>
    </div>
  </div>
</div>
