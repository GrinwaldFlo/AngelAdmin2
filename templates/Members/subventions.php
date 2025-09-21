<?php 
use App\Controller\PrefResult;

if ($grid): 
?>
  <?php $this->layout = "empty"; ?>

  <table class="table table-striped table-hover table-sm">
    <thead>
      <tr>
        <th><?= "City" ?></th>
        <th><?= __("Name") ?></th>
        <th><?= __("Date of birth") ?></th>
        <th><?= __("Age") ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($membersCityList as $city): ?>
        <?php foreach ($membersCity[$city] as $member): ?>
          <tr>
            <td><?= $city ?></td>
            <td><?= $this->Html->link($member->fullName, ['action' => 'view', $member->id]) ?></td>
            <td><?= h($member->date_birth) ?></td>
            <td><?= $member->Age ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php return; ?>
<?php endif ?>

<div class="row">
  <div class="col">
    <div class="members view content">
      <?php foreach (PrefResult::$sites as $site): ?>
        <?= $this->Html->link(h($site->city), ['action' => 'subventions', $site->id, $ageFilter], ['class' => 'btn btn-primary btn-sm' . ($site->id == $pref->siteId ? ' pressed' : '')]) ?>
      <?php endforeach; ?>
      |
      <?= $this->Html->link(__("All"), ['action' => 'subventions', $pref->siteId, 0], ['class' => 'btn btn-primary btn-sm' . ($ageFilter == 0 ? ' pressed' : '')]) ?>   
      <?= $this->Html->link("<21", ['action' => 'subventions', $pref->siteId, 21], ['class' => 'btn btn-primary btn-sm' . ($ageFilter == 21 ? ' pressed' : '')]) ?>   
      |
      <?= $this->Html->link(__("PDF"), ['action' => 'subventions_pdf', $pref->siteId, $ageFilter], ['class' => 'btn btn-primary btn-sm']) ?>
      <?= $this->Html->link(__("Grid"), ['action' => 'subventions', $pref->siteId, $ageFilter, 1], ['class' => 'btn btn-primary btn-sm']) ?>
    </div>
  </div>
</div>

<?php foreach ($membersCityList as $city): ?>
  <div class="row">
    <div class="col">
      <div class="members view content">
        <h3><?php echo $city ?></h3>
        <table class="table table-striped table-hover table-sm">
          <thead>
            <tr>
              <th><?= __("Name") ?></th>
              <th><?= __("Date of birth") ?></th>
              <th><?= __("Age") ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($membersCity[$city] as $member): ?>
              <tr>
                <td><?= $this->Html->link($member->fullName, ['action' => 'view', $member->id]) ?></td>
                <td><?= h($member->date_birth) ?></td>
                <td><?= $member->Age ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endforeach; ?>

