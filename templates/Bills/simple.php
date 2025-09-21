<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Bill[]|\Cake\Collection\CollectionInterface $bills
 * 
 *     
 */
?>


<div class="row">
  <div class="col">
    <div class="members view content">
      <?php foreach ($sites as $key => $site): ?>
        <?= $this->Html->link(h($site), ['action' => 'simple', 0, 0, $teamId, $key], ['class' => 'btn btn-primary btn-sm' . ($key == $siteId ? ' pressed' : '')]) ?>      
      <?php endforeach; ?>
      |
      <?php foreach ($teams as $key => $team): ?>
        <?= $this->Html->link(h($team), ['action' => 'simple', 0, 0, $teamId == $key ? 0 : $key], ['class' => 'btn btn-primary btn-sm' . ($key == $teamId ? ' pressed' : '')]) ?>      
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php foreach ($result as $key => $m): ?>
  <?php echo $key ?>   
  <?php foreach ($m as $key2 => $b): ?>
    <span class="badge bg-<?= h($b['Status']) ?>"><?php echo $key2; ?></span>
  <?php endforeach; ?>
  <br />
<?php endforeach; ?>


