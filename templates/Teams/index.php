<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Team[]|\Cake\Collection\CollectionInterface $teams
 */
?>
<div class="teams index content">
  <?= $curRole->Admin ? $this->Html->link(__('New Team'), ['action' => 'add'], ['class' => 'button float-end']) : '' ?>
  <h3><?= __('Teams') ?></h3>
  <table class="table table-striped table-hover table-sm">
    <thead>
      <tr>
        <th>Id</th>
        <th><?= __('Name') ?></th>
        <th><?= __('Fees') ?></th>
        <th><?= __('Active') ?></th>
        <th><?= __('Location') ?></th>
        <th class="actions"><?= __('Actions') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($teams as $team): ?>
        <tr>
          <td><?= $this->Number->format($team->id) ?></td>
          <td><?= $this->Html->link($team->name, ['action' => 'view', $team->id]) ?></td>
          <td><?= $this->Number->currency($team->membership_fee, 'CHF') ?></td>
          <td><?= h($team->active) ?></td>
          <td><?= h($team->site->city) ?></td>
          <td class="actions">
            <div class="icon">
              <?= $curRole->MemberEditAll ? $this->Html->link('<i class="gg-pen"></i>', ['controller' => 'Teams', 'action' => 'edit', $team->id], ['escape' => false]) : '' ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
