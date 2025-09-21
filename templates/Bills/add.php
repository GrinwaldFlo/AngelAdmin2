<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Bill $bill
 */
$dateBill = new DateTime();
$interval = new DateInterval('P40D');
$dateBill->add($interval);
?>
<div class="row">
  <div class="col">
    <div class="bills form content">
      <?= $this->Form->create($bill) ?>
      <fieldset>
        <legend><?= __('Add invoice to {0}', $curMember->full_name) ?></legend>
        <?php
        echo $this->Form->control('member_id', ['type' => 'hidden', 'value' => $curMember->id]);
        echo $this->Form->control('label', ['label' => __x('Label on the invoice','Denomination'), 'id' => 'label']);
        echo $this->Form->control('amount', ['label' => __('Amount'), 'id' => 'amount']);
        echo $this->Form->control('printed', ['value' => 0, 'label' => __('Printed'), 'type' => 'hidden']);
        echo $this->Form->control('paid', ['value' => 0, 'label' => __('Paid'), 'type' => 'hidden']);
        echo $this->Form->control('reminder', ['value' => 0, 'type' => 'hidden']);
        echo $this->Form->control('due_date', ['value' => $dateBill, 'label' => __('Due date')]);
        echo $this->Form->control('due_date_ori', ['type' => 'hidden']);
        echo $this->Form->control('link_membership_fee', ['label' => __('Linked to a membership'), 'id' => 'membership_fee']);
        echo $this->Form->control('canceled', ['value' => 0, 'type' => 'hidden']);
        echo $this->Form->control('state_id', ['options' => $bill->StateList, 'type' => 'hidden']);
        echo $this->Form->control('status', ['value' => 0, 'type' => 'hidden']);
        echo $this->Form->control('tokenhash', ['type' => 'hidden']);
        echo $this->Form->control('confirmation', ['type' => 'hidden']);
        echo $this->Form->control('site_id', ['options' => $sites]);
        ?>
      </fieldset>
      <?= $this->Form->button(__('Submit')) ?>
      <?= $this->Form->end() ?>
      <hr>      
      <h3><?= __("Templates") ?></h3>
      <div>
        <div class="btn btn-primary btn-sm" id="clear"><?= __("Clear") ?></div>
        <div class="btn btn-primary btn-sm" id="Membership"><?= __("Membership fee") ?></div>
        <div class="btn btn-primary btn-sm" id="Membership1_2"><?= __("Membership fee") . " 1/2" ?></div>
        <div class="btn btn-primary btn-sm" id="Membership2_2"><?= __("Membership fee") . " 2/2" ?></div>
      </div>
      <div>
        <?php foreach ($billTemplates as $value) : ?>
          <div class="btn btn-primary btn-sm" id="<?= $value->id ?>"><?= $value->label ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<script>
  $("#clear").click(function () {
    $("#label").val("");
    $("#amount").val("");
    $("#membership_fee").prop('checked', false);
  });
  $("#Membership").click(function () {
    $("#label").val("<?= $feeLabel ?>");
    $("#amount").val("<?= $curMember->MembershipFee($config['feeMax']) ?>");
    $("#membership_fee").prop('checked', true);
  });
  $("#Membership1_2").click(function () {
    $("#label").val("<?= $feeLabel ?> 1/2");
    $("#amount").val("<?= $curMember->MembershipFee($config['feeMax']) / 2 ?>");
    $("#membership_fee").prop('checked', true);
  });
  $("#Membership2_2").click(function () {
    $("#label").val("<?= $feeLabel ?> 2/2");
    $("#amount").val("<?= $curMember->MembershipFee($config['feeMax']) / 2 ?>");
    $("#membership_fee").prop('checked', true);
  });
<?php
foreach ($billTemplates as $value)
{
  echo <<<EOL
  $("#$value->id").click(function () {
    $("#label").val("$value->label");
    $("#amount").val("$value->amount");
    $("#membership_fee").prop('checked', false);
  });
EOL;
}
?>
</script>