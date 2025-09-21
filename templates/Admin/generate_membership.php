<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member[]|\Cake\Collection\CollectionInterface $members
 */
?>
<div class="row">
  <div class="col">
    <div class="members view content">
      <?php foreach ($teams as $key => $team): ?>
        <?= $this->Html->link(h($team), ['action' => 'generateMembership', $teamId == $key ? 0 : $key], ['class' => 'btn btn-primary btn-sm' . ($key == $teamId ? ' pressed' : '')]) ?>      
      <?php endforeach; ?>
    </div>
  </div>
</div>


<div class="members index content">
  <h3><?= __('Generate membership fees for year {0}-{1}', $config['year'], $config['year'] + 1) ?></h3>
  <?= $this->Form->create() ?>
  <table class="table table-striped table-hover table-sm">
    <thead>
      <tr>
        <th><?= __("Name") ?></th>
        <th><?= __("Age") ?></th>
        <th><?= __("Team") ?></th>
        <th><?= __x('Payment splited in multiple installments', "Split payment") ?></th>
        <th><?= __("Membership fee") ?></th>
        <th><?= __("Paid") ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($members as $member): ?>
        <tr>
          <td><?= $this->Html->link($member->fullName, ['controller' => 'Members', 'action' => 'view', $member->id]) ?></td>
          <td><?= $member->date_birth ? h($member->Age) : '' ?></td>
          <td><?= h($member->TeamString) ?></td>
          <td><?= $member->multi_payment ?></td>
          <td><?=
            h($member->MembershipFee($config['feeMax']))
            . ($member->coach ? ' ' . __('Coach') : '')
            ?></td>
          <td>
            <?php
            if ($member->HasMembershipFee($config))
            {
              echo $member->MembershipFeePaid($config);
            }
            else
            {
              echo $this->Form->control('MemberId.' . $member->id, ['type' => 'checkbox', 'label' => __("Create")]);
            }
            ?>
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