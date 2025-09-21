<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member[]|\Cake\Collection\CollectionInterface $members
 */

$action = 'invite';
$teamId = $pref->teamId;
$memberFilter = $pref->memberFilter;
$siteId = $pref->siteId;
$teams = $pref->teams;

?>

<div class="row">
  <div class="col">
    <div class="members view content">
      <?= $this->my->siteLinks($pref, $action) ?>
      |
      <?= $this->Html->link(__('Active'), ['action' => $action, $teamId, $memberFilter == 1 ? 0 : 1, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 1 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Alumni'), ['action' => $action, $teamId, $memberFilter == 2 ? 0 : 2, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 2 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Membership not paid'), ['action' => $action, $teamId, $memberFilter == 3 ? 0 : 3, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 3 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Non inscrit'), ['action' => $action, $teamId, $memberFilter == 4 ? 0 : 4, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 4 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('No team'), ['action' => $action, $teamId, $memberFilter == 5 ? 0 : 5, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 5 ? ' pressed' : '')]) ?>
      <br />
      <?= $this->my->teamLinks($pref, $action) ?>
    </div>
  </div>
</div>




<div class="members index content">
  <h3><?= __('Invite') ?></h3>
  <?= $this->Form->create() ?>
  <table class="table table-striped table-hover table-sm">
    <thead>
      <tr>
        <th><?= __("Name") ?></th>
        <th><?= __("Registered") ?></th>
        <th><?= __("Age") ?></th>
        <th><?= __("Team") ?></th>
        <th><?= __("Username") ?></th>
        <th><?= __("Last login") ?></th>
        <th><?= __("Role") ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($members as $member): ?>
        <tr>
          <td><?= $this->Html->link($member->fullName, ['action' => 'view', $member->id]) ?></td>
          <td><?= $member->registered ? __("Yes") : $this->Form->control('MemberId.' . $member->id, ['type' => 'checkbox', 'label' => __("Invite")])  ?></td>
          <td><?= $member->date_birth ? h($member->Age) : '' ?></td>
          <td><?= h($member->TeamString) ?></td>
          <?php if (empty($member->user->username)) : ?>
            <td>
            </td>
            <td>
            </td>
            <td>
            </td>
          <?php else : ?>
            <td>
              <?= h($member->user->username) ?>
            </td>
            <td>
              <?= h($member->user->lastLogin) ?>
            </td>
            <td>
              <?= h($member->user->Role->name) ?>
            </td>
          <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?= $this->Form->button(__('Submit')) ?>
  ¦ <div class="btn btn-primary btn-sm" id="CheckAll"><?= __("Check all") ?></div><div class="btn btn-primary btn-sm" id="CheckNone"><?= __("Check none") ?></div>
    <?= $this->Form->end() ?>
</div>

<script>
  $("#CheckAll").click(function () {
    $("input:checkbox").prop('checked', true);
  });
  $("#CheckNone").click(function () {
    $("input:checkbox").prop('checked', false);
  });
</script>
