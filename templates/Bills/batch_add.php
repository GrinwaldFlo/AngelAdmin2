<?php
$dateBill = new DateTime();
$interval = new DateInterval('P40D');
$dateBill->add($interval);
?>

<div class="row">
  <div class="col">
    <div class="members view content">
      <?php foreach ($sites as $key => $site): ?>
        <?= $this->Html->link(h($site), ['action' => 'batchAdd', $teamId, $memberFilter, $key], ['class' => 'btn btn-primary btn-sm' . ($key == $siteId ? ' pressed' : '')]) ?>      
      <?php endforeach; ?>
      |
      <?= $this->Html->link(__('Active'), ['action' => 'batchAdd', $teamId, $memberFilter == 1 ? 0 : 1, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 1 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Alumni'), ['action' => 'batchAdd', $teamId, $memberFilter == 2 ? 0 : 2, $siteId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 2 ? ' pressed' : '')]) ?>
      |
      <?php foreach ($teams as $key => $team): ?>
        <?= $this->Html->link(h($team), ['action' => 'batchAdd', $teamId == $key ? 0 : $key, $memberFilter, $siteId], ['class' => 'btn btn-primary btn-sm' . ($key == $teamId ? ' pressed' : '')]) ?>      
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?= $this->Form->create() ?>
<div class="row">
  <div class="col">
    <div class="bills form content">
      <h3><?= __("Templates") ?></h3>
      <div>
        <div class="btn btn-primary btn-sm" id="clear"><?= __("Clear") ?></div>
        <?php foreach ($billTemplates as $value) : ?>
          <div class="btn btn-primary btn-sm" id="<?= $value->id ?>"><?= $value->label ?></div>
        <?php endforeach; ?>
      </div>
      <hr>  
      <fieldset>
        <legend><?= __('Invoice detail') ?></legend>
        <?php
        echo $this->Form->control('label', ['label' => __x('Label on the invoice', 'Denomination'), 'required' => true, 'id' => 'label']);
        echo $this->Form->control('amount', ['type' => 'number', 'label' => __('Amount'), 'required' => true, 'id' => 'amount']);
        echo $this->Form->control('link_membership_fee', ['type' => 'checkbox', 'label' => __('Linked to a membership'), 'id' => 'membership_fee']);
        echo $this->Form->control('due_date', ['type' => 'date', 'label' => __('Due date'), 'value' => $dateBill, 'required' => true]);
        ?>
      </fieldset>
    </div>
  </div>  
</div>  
<div class="row">
<div class="col">
  <div class="bills form content">
    <h4><?= __('Members') ?></h4>
    <div class="mb-3">
      <input type="text" id="memberFilter" class="form-control" placeholder="<?= __('Search members...') ?>">
    </div>
    <div id="membersList" style="display: flex; flex-wrap: wrap; gap: 10px;">
      <?php foreach ($members as $member): ?>
        <div class="member-item" data-fullname="<?= strtolower(h($member->FullName)) ?>" style="display: flex; align-items: center; border: 1px solid #ddd; padding: 6px 8px 5px 30px; border-radius: 4px; background-color: #f8f9fa;">
          <?= $this->Form->control('MemberId.' . $member->id, ['type' => 'checkbox', 'label' => false, 'class' => 'checkable', 'style' => 'margin-right: 5px;', 'templates' => ['formGroup' => '{{input}}']]); ?>
          <?= $this->Html->link($member->FullName, ['controller' => 'Members', 'action' => 'view', $member->id], ['target' => '_blank']) ?>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="mt-3">
      <?= $this->Form->button(__('Submit')) ?>
      ¦ <div class="btn btn-primary btn-sm" id="CheckAll"><?= __("Check all") ?></div><div class="btn btn-primary btn-sm" id="CheckNone"><?= __("Check none") ?></div>
    </div>
      <?= $this->Form->end() ?>
    </div>
  </div>  
</div>  



<script>
  $("#CheckAll").click(function () {
    $(".checkable:visible").prop('checked', true);
  });
  $("#CheckNone").click(function () {
    $(".checkable:visible").prop('checked', false);
  });

  $("#memberFilter").on("input", function() {
    var filterValue = $(this).val().toLowerCase();
    
    $(".member-item").each(function() {
      var fullName = $(this).data("fullname");
      
      if (fullName.indexOf(filterValue) > -1) {
        $(this).show();
      } else {
        $(this).hide();
      }
    });
  });

  $("#clear").click(function () {
    $("#label").val("");
    $("#amount").val("");
    $("#membership_fee").prop('checked', false);
  });

<?php
foreach ($billTemplates as $value)
{
  echo <<<EOL
  $("#$value->id").click(function () {
    $("#label").val("$value->label");
    $("#amount").val("$value->amount");
    $("#membership_fee").prop('checked', false);
    $("#site-id").val($value->site_id);
  });
EOL;
}
?>
</script>

<style>
  .member-item .form-check {
    margin-bottom: 0 !important;
    padding: 0 !important;
    display: contents;
  }
  
  .member-item .form-check-input {
    margin-top: 0 !important;
    margin-bottom: 0 !important;
  }
</style>
