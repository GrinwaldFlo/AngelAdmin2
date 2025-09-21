<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */
?>
<div class="row">
    <div class="col">
        <div class="users form content">
            <?= $this->Form->create($user) ?>
            <fieldset>
                <legend><?= __('Create your login for').' '.$member->FullName ?></legend>
                <?php
                    echo $this->Form->control('username', ['label' => __('New username')]);
                    echo $this->Form->control('password', ['label' => __('New password')]);
                    echo __('Confirm password');
                    echo $this->Form->password('passwordConfirmation', ['label' => __('Confirm password')]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
