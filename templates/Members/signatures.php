

<?php foreach ($registration as $value) : ?>
  <div class="row">
    <div class="col">
      <div class="members form content">
        <h3><?= h($value->member->FullName) ?></h3>
        <?= $this->Form->create() ?>
        <fieldset>
          <?php
          $hasData = true;
          echo $this->my->ImageFromBlob($value->signature_member);
          echo $this->my->ImageFromBlob($value->signature_parent);
          echo $this->Form->control('member_id', ['value' => $value->member->id, 'type' => 'hidden']);
          ?>
        </fieldset>
        <?= $this->Form->button(__('Validate')) ?>
        <?= $this->Form->button(__('Delete'), ['name' => 'Delete', 'value' => 'Delete']) ?>
        <?= $this->Form->end() ?>
      </div>
    </div>
  </div>
  <hr>
<?php endforeach; ?>

<?php
if (!isset( $hasData))
{
  echo __('Nothing to validate');
}
?>