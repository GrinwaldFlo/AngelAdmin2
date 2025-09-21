<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member[]|\Cake\Collection\CollectionInterface $members
 */
?>
<div class="row">
  <div class="col">
    <div class="members view content">
      <?= $this->Html->link(__('Active'), ['action' => 'pictures', $teamId, $memberFilter == 1 ? 0 : 1, $teamId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 1 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Alumni'), ['action' => 'pictures', $teamId, $memberFilter == 2 ? 0 : 2, $teamId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 2 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Membership not paid'), ['action' => 'pictures', $teamId, $memberFilter == 3 ? 0 : 3, $teamId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 3 ? ' pressed' : '')]) ?>
      |
      <?php foreach ($teams as $key => $team): ?>
        <?= $this->Html->link(h($team), ['action' => 'pictures', $teamId == $key ? 0 : $key, $memberFilter], ['class' => 'btn btn-primary btn-sm' . ($key == $teamId ? ' pressed' : '')]) ?>      
      <?php endforeach; ?>
    </div>
  </div>
</div>
<div class="row">
  <div class="members index content">
    <h3><?= __('Pictures') ?></h3>
    <?php foreach ($members as $member): ?>
      <div class="photobox">
        <div class="card">
          <?php if ($member->ImgExists) : ?>
            <?= $this->Html->image($member->GetImgUrl(300) . '?' . rand(), ['alt' => 'Portrait', 'class' => 'card-img-top']); ?>
          <?php else: ?>
            <?= $this->Html->image('/img/cheerPlaceholder.jpg', ['alt' => 'Portrait', 'class' => 'card-img-top']); ?>
          <?php endif; ?>
          <div class="card-body">
            <?= $this->Html->link($member->fullName, ['action' => 'view', $member->id]) ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>



