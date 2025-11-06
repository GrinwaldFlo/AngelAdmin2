<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member[]|\Cake\Collection\CollectionInterface $members
 */
?>
<div class="row">
  <div class="col">
    <div class="members view content">
      <?php foreach ($sites as $key => $site): ?>
        <?= $this->Html->link(h($site), ['action' => 'list', $teamId, $memberFilter, $key == $siteId ? 0 : $key], ['class' => 'btn btn-primary btn-sm' . ($key == $siteId ? ' pressed' : '')]) ?>      
      <?php endforeach; ?>
      |
      <?= $this->Html->link(__('Active'), ['action' => 'list', $teamId, $memberFilter == 1 ? 0 : 1, $teamId, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 1 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Alumni'), ['action' => 'list', $teamId, $memberFilter == 2 ? 0 : 2, $teamId, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 2 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Membership not paid'), ['action' => 'list', $teamId, $memberFilter == 3 ? 0 : 3, $teamId, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 3 ? ' pressed' : '')]) ?>
      |
      <?php foreach ($teams as $key => $team): ?>
        <?= $this->Html->link(h($team), ['action' => 'list', $teamId == $key ? 0 : $key, $memberFilter, $siteId], ['class' => 'btn btn-primary btn-sm' . ($key == $teamId ? ' pressed' : '')]) ?>      
      <?php endforeach; ?>
    </div>
  </div>
</div>
<div class="members index content">
  <?php if ($curRole->MemberEditAll) : ?>
    <?= $this->Html->link('', ['action' => 'add'], ['class' => 'float-end gg-add-r']) ?>
  <?php endif; ?>
  <h3><?= __('List') ?></h3>
  <table class="table table-striped table-hover table-sm">
    <thead>
      <tr>
        <th><?= $this->Paginator->sort('first_name', __("First name")) ?></th>
        <th><?= $this->Paginator->sort('last_name', __("Last name")) ?></th>
        <th><?= __("Team") ?></th>
        <th><?= __("Date of birth") ?></th>
        <th><?= __("Email") ?></th>
        <th><?= __("Address") ?></th>
        <th><?= __("Postal code") ?></th>
        <th><?= __("City") ?></th>
        <th><?= __("Mobile") ?></th>
        <th><?= __("Location") ?></th>
        <th><?= $this->Paginator->sort('modified', __("Modification")) ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($members as $member): ?>
        <tr> 
          <td><?= $this->Html->link($member->first_name, ['action' => 'view', $member->id]) ?></td>
          <td><?= $this->Html->link($member->last_name, ['action' => 'view', $member->id]) ?></td>
          <td><?= h($member->TeamString) ?></td>
          <td><?= h($member->date_birth) ?></td>
          <td><?= h($member->email) ?></td>
          <td><?= h($member->address) ?></td>
          <td><?= h($member->postcode) ?></td>
          <td><?= h($member->city) ?></td>
          <td><?= h($member->phone_mobile) ?></td>
          <td><?= h($member->SiteString) ?></td>
          <td><?= h($member->modified) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>


