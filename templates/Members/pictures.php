<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member[]|\Cake\Collection\CollectionInterface $members
 */
?>
<div class="row">
  <div class="col">
    <div class="members view content">
      <?= $this->my->memberFilterLinks(['action' => 'pictures', $teamId], $memberFilter, [1, 2]) ?>
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



