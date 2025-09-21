<div class="row">
  <div class="col">
    <div class="icon float-end">
      <?= $this->BackButton('/', true) ?>
    </div>
    <div class="form content">
      <?= $this->Flash->render() ?>
      <h3><?= __('Password forgotten') ?></h3>
      <?= $this->Form->create() ?>
      <fieldset>
        <?= $this->Form->control('email_or_username', ['required' => true, 'label' => __('Please enter your email or username')]) ?>
      </fieldset>
      <?= $this->Form->submit(__('Send')); ?>
      <?= $this->Form->end() ?>

      <?= $this->Html->link(__("Register"), ['controller' => 'Members', 'action' => 'register']) ?>
      <br>
      <?= $this->Html->link(__("Login"), ['action' => 'login']) ?>
    </div>
  </div>
</div>
