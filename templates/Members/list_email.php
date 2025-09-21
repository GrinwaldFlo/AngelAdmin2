<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member[]|\Cake\Collection\CollectionInterface $members
 */
?>
<div class="row">
  <div class="col">
    <div class="members view content">
      <?= $this->Html->link(__('Active'), ['action' => 'listEmail', $teamId, $memberFilter == 1 ? 0 : 1, $teamId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 1 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Alumni'), ['action' => 'listEmail', $teamId, $memberFilter == 2 ? 0 : 2, $teamId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 2 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Membership not paid'), ['action' => 'listEmail', $teamId, $memberFilter == 3 ? 0 : 3, $teamId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 3 ? ' pressed' : '')]) ?>
      |
      <?php foreach ($teams as $key => $team): ?>
        <?= $this->Html->link(h($team), ['action' => 'listEmail', $teamId == $key ? 0 : $key, $memberFilter], ['class' => 'btn btn-primary btn-sm' . ($key == $teamId ? ' pressed' : '')]) ?>      
      <?php endforeach; ?>
    </div>
  </div>
</div>
<div class="members index content">
  <h3><?= __('Email list') ?></h3>

  <?php foreach ($members as $member): ?>
            <?php foreach ($member->GetAllMails() as $email): ?>
            <?= h($email) ?><br>
            <?php endforeach; ?>
     <?php endforeach; ?>

  <table class="table table-striped table-hover table-sm">
    <thead>
      <tr>
        <th><?= $this->Paginator->sort('first_name', __("First name")) ?></th>
        <th><?= $this->Paginator->sort('last_name', __("Last name")) ?></th>
        <th><?= __("Team") ?></th>
        <th><?= __("Email") ?></th>
        <th><?= __("Email") ?></th>
        <th><?= __("Email") ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($members as $member): ?>
        <tr> 
          <td><?= $this->Html->link($member->first_name, ['action' => 'view', $member->id]) ?></td>
          <td><?= $this->Html->link($member->last_name, ['action' => 'view', $member->id]) ?></td>
          <td><?= h($member->TeamString) ?></td>
            <?php foreach ($member->GetAllMails() as $email): ?>
            <td><?= h($email) ?></td>
            <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>


