<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Role $role
 */
?>
<div class="row">
  <div class="col">
    <div class="roles form content">
      <div class="tags float-end" style="width: 100px">
        <?= $curRole->MemberViewAll ? $this->backButtonCtrl('Roles', 'index') : $this->BackButton('/') ?>
      </div>
      <?= $this->Form->create($role) ?>
      <fieldset>
        <legend><?= __('Edit Role') ?></legend>
        <?php
        echo $this->Form->control('name');
        echo $this->Form->control('MemberViewAll');
        echo $this->Form->control('MemberEditAll');
        echo $this->Form->control('MemberEditOwn');
        echo $this->Form->control('BillViewAll');
        echo $this->Form->control('BillEditAll');
        echo $this->Form->control('BillValidate');
        echo $this->Form->control('Editor');
        echo $this->Form->control('Admin');
        ?>
      </fieldset>
      <?= $this->Form->button(__('Submit')) ?>
      <?= $this->Form->end() ?>
    </div>
  </div>
</div>
