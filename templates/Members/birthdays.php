<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member[]|\Cake\Collection\CollectionInterface $members
 */
$curMonth = "";
$monthStr = [0 => '', 1 => __('January'), 2 => __('February'), 3 => __('March'), 4 => __('April'), 5 => __('May'), 6 => __('June'), 7 => __('July'), 8 => __('August'), 9 => __('October'), 10 => __('September'), 11 => __('November'), 12 => __('December')];
?>
<div class="row">
  <div class="col">
    <div class="members view content">
      <?= $this->Html->link(__('Active'), ['action' => 'birthdays', $teamId, $memberFilter == 1 ? 0 : 1, $teamId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 1 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Alumni'), ['action' => 'birthdays', $teamId, $memberFilter == 2 ? 0 : 2, $teamId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 2 ? ' pressed' : '')]) ?>
      |
      <?php foreach ($teams as $key => $team): ?>
        <?= $this->Html->link(h($team), ['action' => 'birthdays', $teamId == $key ? 0 : $key, $memberFilter], ['class' => 'btn btn-primary btn-sm' . ($key == $teamId ? ' pressed' : '')]) ?>      
      <?php endforeach; ?>
    </div>
  </div>
</div>


<div class="members index content">
  <h3><?= __('Members') ?></h3>
  <table class="table table-striped table-hover table-sm">
    <thead>
      <tr>
        <th><?= __("Name") ?></th>
        <th><?= __("Birthday") ?></th>
        <th><?= __("Age") ?></th>
        <th><?= __("Team") ?></th>
        <?php if (false && $curRole->MemberEditAll) : ?>
          <th class="actions"><?= __('Actions') ?></th>
        <?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($members as $member): ?>
        <?php if (!empty($member->date_birth) && $curMonth != $member->date_birth->month) : ?>
          <?php $curMonth = $member->date_birth->month; ?>
          <tr>
            <td><b><?= $monthStr[$curMonth] ?></b></td>
          </tr>
        <?php endif; ?>
        <tr>
          <td><?= $this->Html->link($member->fullName, ['action' => 'view', $member->id]) ?></td>
          <td><?= h($member->date_birth) ?></td>
          <td><?= $member->date_birth ? h($member->Age) : '' ?></td>
          <td><?= h($member->TeamString) ?></td>
          <?php if (false && $curRole->MemberEditAll) : ?>
            <td class="actions">
              <?= $this->Html->link(__('Edit'), ['action' => 'edit', $member->id]) ?>
            </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
