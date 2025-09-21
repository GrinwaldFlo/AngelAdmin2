<?php
$dateBill = new DateTime();
$interval = new DateInterval('P40D');
$dateBill->add($interval);
?>

<div class="row">
  <div class="col">
    <div class="members view content">
      <?= $this->Html->link(__('Active'), ['action' => 'batchAdd', $teamId, $memberFilter == 1 ? 0 : 1, $teamId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 1 ? ' pressed' : '')]) ?>
      <?= $this->Html->link(__('Alumni'), ['action' => 'batchAdd', $teamId, $memberFilter == 2 ? 0 : 2, $teamId], ['class' => 'btn btn-primary btn-sm' . ($memberFilter == 2 ? ' pressed' : '')]) ?>
      |
      <?php foreach ($teams as $key => $team): ?>
        <?= $this->Html->link(h($team), ['action' => 'batchAdd', $teamId == $key ? 0 : $key, $memberFilter], ['class' => 'btn btn-primary btn-sm' . ($key == $teamId ? ' pressed' : '')]) ?>      
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
        echo $this->Form->control('site_id', ['options' => $sites]);
        ?>
      </fieldset>
    </div>
  </div>  
</div>  
<div class="row">
  <div class="col">
    <div class="bills form content">
      <h4><?= __('Members') ?></h4>
      <table class="table table-striped table-hover table-sm">
        <tbody>
          <?php foreach ($members as $member): ?>
            <tr>
              <td><?= $this->Form->control('MemberId.' . $member->id, ['type' => 'checkbox', 'label' => false, 'class' => 'checkable']); ?></td>
              <td><?= $this->Html->link($member->FullName, ['controller' => 'Members', 'action' => 'view', $member->id], ['target' => '_blank']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?= $this->Form->button(__('Submit')) ?>
      ¦ <div class="btn btn-primary btn-sm" id="CheckAll"><?= __("Check all") ?></div><div class="btn btn-primary btn-sm" id="CheckNone"><?= __("Check none") ?></div>
        <?= $this->Form->end() ?>
    </div>
  </div>  
</div>  



<script>
  $("#CheckAll").click(function () {
    $(".checkable").prop('checked', true);
  });
  $("#CheckNone").click(function () {
    $(".checkable").prop('checked', false);
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
