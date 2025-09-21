<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Meeting[]|\Cake\Collection\CollectionInterface $meetings
 */
?>
<div class="row">
  <div class="col">
    <div class="members view content">
      <?= $this->Html->link(__('Past'), ['action' => 'index', $teamId, $meetingFilter == 1 ? 0 : 1, $teamId], ['class' => 'btn btn-primary btn-sm' . ($meetingFilter == 1 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Today'), ['action' => 'index', $teamId, $meetingFilter == 2 ? 0 : 2, $teamId], ['class' => 'btn btn-primary btn-sm' . ($meetingFilter == 2 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Future'), ['action' => 'index', $teamId, $meetingFilter == 3 ? 0 : 3, $teamId], ['class' => 'btn btn-primary btn-sm' . ($meetingFilter == 3 ? ' pressed' : '')]) ?>
      |
      <?php foreach ($teams as $key => $team): ?>
        <?= $this->Html->link(h($team), ['action' => 'index', $teamId == $key ? 0 : $key, $meetingFilter], ['class' => 'btn btn-primary btn-sm' . ($key == $teamId ? ' pressed' : '')]) ?>      
      <?php endforeach; ?>
    </div>
  </div>
</div>
<div class="meetings index content">
  <?= $curRole->MemberEditAll ? $this->Html->link('', ['action' => 'addMultiple'], ['class' => 'float-end gg-add-r']) : '' ?>
  <h3><?= __('Meetings') ?></h3>
  <table class="table table-striped table-hover table-sm">
    <thead>
      <tr>
        <th><?= $this->Paginator->sort('meeting_date') ?></th>
        <th><?= $this->Paginator->sort('team_id') ?></th>
        <th><?= $this->Paginator->sort('name') ?></th>
        <th><?= __('Pre/Abs/Exc/Late') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($meetings as $meeting): ?>
        <tr>
          <td><?= h($meeting->meeting_date->i18nFormat($config['dateEvent'])) ?></td>
          <td><?= $meeting->has('team') ? $this->Html->link($meeting->team->name, ['controller' => 'Teams', 'action' => 'view', $meeting->team->id]) : '' ?></td>
          <td><?= $this->Html->link(h($meeting->name), ['action' => 'view', $meeting->id]) ?></td>
          <td><?= h($meeting->PresencesStr) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="paginator">
    <ul class="pagination">
      <?= $this->Paginator->first('<< ' . __('first')) ?>
      <?= $this->Paginator->prev('< ' . __('previous')) ?>
      <?= $this->Paginator->numbers() ?>
      <?= $this->Paginator->next(__('next') . ' >') ?>
      <?= $this->Paginator->last(__('last') . ' >>') ?>
    </ul>
    <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
  </div>
</div>
