<?php
?>
<div class="row">
    <div class="col">
        <div class="users form content">
            <?= $this->Form->create($user) ?>
            <fieldset>
                <legend><?= __('Set new password') ?></legend>
                <?php
                    echo $this->Form->control('username', ['disabled' => 'disabled', 'label' => "Username"]);
                    echo $this->Form->control('password', ['label' => 'Password']);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
