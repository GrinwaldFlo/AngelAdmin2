<?php
/**
 * Side menu of the mailing forms
 * @var \App\View\AppView $this
 */
?>
<aside class="col-md-3">
  <div class="side-nav">
    <h4 class="heading"><?= __('Actions') ?></h4>
    <?= $this->Html->link(__('Emails to teams'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
    <?php if ($curRole->Admin) : ?>
      <?= $this->Html->link(__('New email by bill'), ['action' => 'addByBill'], ['class' => 'side-nav-item']) ?>
    <?php endif; ?>
  </div>
</aside>
