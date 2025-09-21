<?php
// Calculate totals
$totalMembers = count($members);
$totalAmount = 0;
$totalBills = 0;
foreach ($members as $member) {
    $totalAmount += $member->total_amount;
    $totalBills += $member->count;
}
?>

<div class="row">
  <div class="col">
    <div class="members view content">
      <?php foreach ($sites as $key => $site): ?>
        <?= $this->Html->link(h($site), ['action' => 'send_reminder', $memberStatus, $key], ['class' => 'btn btn-primary btn-sm' . ($key == $siteId ? ' pressed' : '')]) ?>      
      <?php endforeach; ?>
      |
      <?= $this->Html->link(__('Active'), ['action' => 'send_reminder', $memberStatus == 1 ? 0 : 1, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberStatus == 1 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Alumni'), ['action' => 'send_reminder', $memberStatus == 2 ? 0 : 2, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberStatus == 2 ? ' pressed' : '')]) ?>
    </div>
  </div>
</div>
<?= $this->Form->create() ?>
<div class="bills index content">

<table class="table table-striped table-hover table-sm">
    <thead>
      <tr>
        <th><?= __('Member') ?></th>
        <th><?= __('Amount') ?></th>
        <th><?= __('Number') ?></th>
        <th><?= __('Label') ?></th>
        <th><?= __('Last sent') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($members as $member): ?>
        <tr>
          <td><?= $this->Html->link($member->first_name." ".$member->last_name, ['controller' => 'Members', 'action' => 'view', $member->member_id], ['target' => '_blank'])  ?></td>
          <td><?= $this->Number->currency($member->total_amount, 'CHF') ?></td>
          <td><?= h($member->count) ?></td>
          <td><?= h($member->labels) ?></td>
          <td><?= h($member->reminder_sent) ?></td>
          <td><?= h($member->email) ?></td>
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

<!-- Summary Section -->
<div class="row mt-3">
  <div class="col">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title"><?= __('Summary') ?></h5>
        <div class="row">
          <div class="col-md-4">
            <strong><?= __('Total Members') ?>:</strong> <?= h($totalMembers) ?>
          </div>
          <div class="col-md-4">
            <strong><?= __('Total Bills') ?>:</strong> <?= h($totalBills) ?>
          </div>
          <div class="col-md-4">
            <strong><?= __('Total Amount') ?>:</strong> <?= $this->Number->currency($totalAmount, 'CHF') ?>
          </div>
        </div>
      </div>
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
