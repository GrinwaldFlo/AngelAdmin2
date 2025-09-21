<div class="row">
  <div class="col">
    <div class="members view content">
      <?php foreach ($sites as $key => $site): ?>
        <?= $this->Html->link(h($site), ['action' => 'mail_send', $memberStatus, $teamId, $key], ['class' => 'btn btn-primary btn-sm' . ($key == $siteId ? ' pressed' : '')]) ?>      
      <?php endforeach; ?>
      |
      <?= $this->Html->link(__('Active'), ['action' => 'mail_send', $memberStatus == 1 ? 0 : 1, $teamId, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberStatus == 1 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Alumni'), ['action' => 'mail_send', $memberStatus == 2 ? 0 : 2, $teamId, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberStatus == 2 ? ' pressed' : '')]) ?>
      |
      <?php foreach ($teams as $key => $team): ?>
        <?= $this->Html->link(h($team), ['action' => 'mail_send', $memberStatus, $teamId == $key ? 0 : $key, $siteId], ['class' => 'btn btn-primary btn-sm' . ($key == $teamId ? ' pressed' : '')]) ?>      
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?= $this->Form->create() ?>
<div class="bills index content">
  <h3><?= __('Draft invoices') . ' (' . sizeof($bills) . ' ' . __x('Elements in a list', 'items') . ')' ?></h3>
  <table class="table table-striped table-hover table-sm">
    <thead>
      <tr>
        <th><?= __('Send') ?></th>
        <th><?= 'Id' ?></th>
        <th><?= __('Member') ?></th>
        <th><?= __x('Label on the invoice', 'Denomination') ?></th>
        <th><?= __('Amount') ?></th>
        <th><?= __('No. of reminders') ?></th>
        <th><?= __('Due date') ?></th>
        <th><?= __('Email') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($bills as $bill): ?>
        <?php //debug($bill); break; ?>
        <tr>
          <td><?= $this->Form->control('BillId.' . $bill->id, ['type' => 'checkbox', 'checked' => true, 'label' => false, 'class' => 'checkable']); ?></td>
          <td><?= $this->Html->link(h($bill->Reference), ['controller' => 'Bills', 'action' => 'view', $bill->id], ['target' => '_blank']) ?></td>
          <td><?= $bill->has('member') ? $this->Html->link($bill->member->FullName, ['controller' => 'Members', 'action' => 'view', $bill->member->id], ['target' => '_blank']) : '' ?></td>
          <td><?= h($bill->label) ?></td>
          <td><?= $this->Number->currency($bill->amount, 'CHF') ?></td>
          <td><?= $this->Number->format($bill->reminder) ?></td>
          <td><?= h($bill->due_date) ?></td>
          <td><?= $this->Html->link(h($bill->member->email) . ($bill->member->email_valid ? ' ' . __('Valid') : ''), 'mailto:' . $bill->member->email) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<div class="row">
  <div class="col">
    <div class="members view content">
      <?= $this->Form->control('AcceptSend', ['type' => 'checkbox', 'required' => true, 'label' => __('I am sending emails to all checked members above')]); ?>
      <?= $this->Form->button(__('Send all emails')) ?>
      ¦ <div class="btn btn-primary btn-sm" id="CheckAll"><?= __("Check all") ?></div><div class="btn btn-primary btn-sm" id="CheckNone"><?= __("Check none") ?></div>
        <?= $this->Form->end() ?>
    </div>
  </div>
</div>

<script>
  $("#CheckAll").click(function () {
    $(".checkable").prop('checked', true);
  });
  $("#CheckNone").click(function () {
    $(".checkable").prop('checked', false);
  });
</script>
