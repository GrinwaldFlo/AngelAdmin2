<?php ?>


<div class="content">
  <div class="row">
   <div class="col">
      <h3><?= __('Teams') ?></h3>
      <?php foreach ($teams as $value) : ?>
        <h5><?= h($value->name) ?></h5>
        <div><?= h($value->NbMembers) . " " . __("Members") ?></div>
        <div>
          <?php foreach ($value->Coachs as $c) : ?>
            <?= h($c->first_name) . " " ?>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
   <div class="col">
      <h3><?= __('Finances') ?></h3>
      <?php $this->my->InfoTable(null, $finances) ?>
    </div>
   <div class="col">
      <h3><?= __('Members') ?></h3>
      <?php $this->my->InfoTable(null, $members) ?>
    </div>
   <div class="col">
      <h3><?= __('Configuration') ?></h3>
      <?php $this->my->InfoTable(null, $configuration) ?>
    </div>

  </div>
  <div class="row">
  </div>
</div>
