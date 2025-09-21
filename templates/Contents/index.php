<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Content[]|\Cake\Collection\CollectionInterface $contents
 */
?>
<div class="contents index content">
  <?= $this->Html->link(__('New Content'), ['action' => 'add'], ['class' => 'button float-end']) ?>
  <h3><?= __('Contents') ?></h3>
  <table class="table table-striped table-hover table-sm">
    <thead>
      <tr>
        <th><?= $this->Paginator->sort('text') ?></th>
        <th><?= $this->Paginator->sort('location') ?></th>
        <th><?= $this->Paginator->sort('url') ?></th>
        <th><?= $this->Paginator->sort('team_id') ?></th>
        <th><?= $this->Paginator->sort('sort') ?></th>
        <th class="actions"><?= __('Actions') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($contents as $content): ?>
        <tr>
          <td><?= substr(strip_tags($content->text), 0, 20) ?></td>
          <td><?= h($content->locationStr) ?></td>
          <td><?= h($content->url) ?></td>
          <td><?= $content->has('team') ? $this->Html->link($content->team->name, ['controller' => 'Teams', 'action' => 'view', $content->team->id]) : '' ?></td>
          <td><?= $this->Number->format($content->sort) ?></td>
          <td class="actions">
            <?= $this->Html->link(__('View'), ['action' => 'view', $content->id]) ?>
            <?= $this->Html->link(__('Edit'), ['action' => 'edit', $content->id]) ?>
            <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $content->id], ['confirm' => __('Are you sure you want to delete # {0}?', $content->id)]) ?>
          </td>
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
