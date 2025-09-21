<?php ?>
<div class="row">
  <div class="col">
    <div class="members form content">
      <div class="row">
        <div class="col">
          <?= $this->BackButton($refer, true) ?>
          <h3><?= __('Quit membership') ?></h3>
        </div>
      </div>
      <?= $this->Form->create($member) ?>
      <fieldset>
        <?php
        echo $this->Form->control('leaving_comment', ['label' => __('Comments')]);
        if ($openBills->count() > 0)
        {
          echo $this->Form->control('validation', ['type' => 'checkbox', 'required' => true, 'label' => __('All open invoices are still due after leaving')]);
        }
        ?>
      </fieldset>
      <?= $this->Form->button(__('Leave the club')) ?>
      <?= $this->Form->end() ?>
    </div>
  </div>
</div>
<?php if ($openBills->count() > 0): ?>
  <div class="row">
    <div class="col">
      <div class="members from content">
        <table class="table table-striped table-hover table-sm">
          <tr>
            <th><?= __('Id') ?></th>
            <th><?= __x('Label on the invoice', 'Denomination') ?></th>
            <th><?= __('Amount') ?></th>
            <th><?= __('Remin.') ?></th>
            <th><?= __('Status') ?></th>
            <th><?= __('Due date') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
          </tr>
          <?php foreach ($openBills as $bill) : ?>
            <tr>
              <td><?= $this->Html->link(h($bill->Reference), ['controller' => 'Bills', 'action' => 'view', $bill->id]) ?></td>
              <td><?= h($bill->label) ?></td>
              <td><?= $this->Number->currency($bill->amount, 'CHF') ?></td>
              <td><?= h($bill->reminder) ?></td>
              <td><span class="badge bg-<?= h($bill->statusHtml) ?>"><?= h($bill->statusString) ?></span></td>
              <td><?= h($bill->due_date) ?></td>
              <td class="actions">
                <div class="tags" style="width: 90px">
                  <?= $this->Html->link('', $bill->BillUrl, ['target' => '_blank', 'class' => 'gg-file-document']) ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>
