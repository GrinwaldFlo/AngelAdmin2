<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member[]|\Cake\Collection\CollectionInterface $members
 */
?>



<div class="members index content">
  <h3><?= __('Members to sort') ?></h3>
  <table class="table table-striped table-hover table-sm">
    <thead>
      <tr>
        <th><?= __("Name") ?></th>
        <th><?= __("Age") ?></th>          
        <?php if ($curRole->MemberEditAll) : ?>
          <th class="actions"><?= __('Actions') ?></th>
        <?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($members as $member): ?>
        <tr>
          <td><?= $this->Html->link($member->fullName, ['action' => 'view', $member->id]) ?></td>
          <td><?= $member->date_birth ? h(date_diff($member->date_birth->toDateTimeImmutable(), new DateTime())->format('%y')) : '' ?></td>
          <?php if ($curRole->MemberEditAll) : ?>
            <td class="actions">
              <?= $this->Html->link(__('Edit'), ['action' => 'edit', $member->id]) ?>
            </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
