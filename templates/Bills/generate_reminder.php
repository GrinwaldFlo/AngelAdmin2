<div class="row">
  <div class="col">
    <div class="members view content">
      <?php foreach ($sites as $key => $site): ?>
        <?= $this->Html->link(h($site), ['action' => 'generate_reminder', $memberStatus, $teamId, $key], ['class' => 'btn btn-primary btn-sm' . ($key == $siteId ? ' pressed' : '')]) ?>      
      <?php endforeach; ?>
      |
      <?= $this->Html->link(__('Active'), ['action' => 'generate_reminder', $memberStatus == 1 ? 0 : 1, $teamId, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberStatus == 1 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Alumni'), ['action' => 'generate_reminder', $memberStatus == 2 ? 0 : 2, $teamId, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberStatus == 2 ? ' pressed' : '')]) ?>
      |
      <?php foreach ($teams as $key => $team): ?>
        <?= $this->Html->link(h($team), ['action' => 'generate_reminder', $memberStatus, $teamId == $key ? 0 : $key, $siteId], ['class' => 'btn btn-primary btn-sm' . ($key == $teamId ? ' pressed' : '')]) ?>      
      <?php endforeach; ?>
    </div>
  </div>
</div>
<div class="bills index content">
  <h3><?= __('Generate reminders') ?></h3>
  <div class="tags float-end" style="width: 300px">
    <?= $this->Html->link(__('Update PDF invoices'), ['action' => 'pdf', -1]) ?>
  </div>
  <?= $this->Form->create() ?>
  <table class="table table-striped table-hover table-sm">
    <tbody>
      <?php foreach ($bills as $bill): ?>
        <tr>
          <td><?= $this->Form->control('BillId.' . $bill->id, ['type' => 'checkbox', 'label' => false, 'checked' => true]); ?></td>
          <td><?= $bill->has('member') ? $this->Html->link($bill->member->FullName, ['controller' => 'Members', 'action' => 'view', $bill->member->id], ['target' => '_blank']) : '' ?></td>
          <td><?= $this->Html->link(h($bill->Reference), ['controller' => 'Bills', 'action' => 'view', $bill->id], ['target' => '_blank']) ?></td>
          <td><?= h($bill->label) ?></td>
          <td><?= $this->Number->currency($bill->amount, 'CHF') ?></td>
          <td><?= h($bill->member->address) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?= $this->Form->control('Penalty', ['type' => 'checkbox', 'label' => __('Add penalty fee')]); ?>
  <?= $this->Form->button(__('Submit')) ?>
  <?= $this->Form->end() ?>
</div>
