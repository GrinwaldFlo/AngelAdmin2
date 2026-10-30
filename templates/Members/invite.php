<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member[]|\Cake\Collection\CollectionInterface $members
 */

$action = 'invite';
$teamId = $pref->teamId;
$memberFilter = $pref->memberFilter;
$teams = $pref->teams;

?>

<div class="row">
  <div class="col">
    <div class="members view content">
      <?= $this->my->memberFilterLinks(['action' => $action, $teamId], $memberFilter, [1, 2, 3, 4, 5]) ?>
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
