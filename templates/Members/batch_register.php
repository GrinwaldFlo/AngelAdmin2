<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member[]|\Cake\Collection\CollectionInterface $members
 */

?>
<div class="row">
  <div class="col">
    <div class="members view content">
      <?= $this->my->siteLinks($pref, 'batchRegister') ?>
      |
      <?= $this->my->teamLinks($pref, 'batchRegister') ?>
    </div>
  </div>
</div>
<?= $this->Flash->render() ?>
<div class="row">
  <div class="col">
    <div class="members view content">
      <?php if ($curRole->MemberEditAll): ?>
        <?= $this->Html->link('', ['action' => 'add'], ['class' => 'float-end gg-add-r']) ?>
      <?php endif; ?>
      <h3><?= __('Members') ?></h3>
      <table class="table table-striped table-hover table-sm">
        <thead>
          <tr>
            <th><?= $this->Paginator->sort('first_name', __("Name")) ?></th>
            <th><?= __("Team") ?></th>
            <th><?= __("Upload inscription") ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($members as $member): ?>
            <tr>
              <td><?= $this->Html->link($member->FullName, ['action' => 'view', $member->id]) ?></td>
              <td><?= h($member->TeamString) ?></td>
              <td>
                <?= $this->Form->create(null, ['type' => 'file']) ?>
                <fieldset>
                  <?= $this->Form->control('memberId', ['type' => 'hidden', 'value' => $member->id]); ?>
                  <?= $this->Form->file('submittedfile1', ['accept' => 'application/pdf']); ?>
                </fieldset>
                <?= $this->Form->submit(__('Send')); ?>
                <?= $this->Form->end() ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<div class="paginator">
  <ul class="pagination">
    <?= $this->Paginator->first('<< ' . __('first')) ?>
    <?= $this->Paginator->prev('< ' . __('previous')) ?>
    <?= $this->Paginator->numbers() ?>
    <?= $this->Paginator->next(__('next') . ' >') ?>
    <?= $this->Paginator->last(__('last') . ' >>') ?>
  </ul>
  <p>
    <?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?>
  </p>
</div>
<?=
  ""//$this->Paginator->limitControl([25 => 25, 50 => 50, 100 => 100, 1000 => 1000]); ?>