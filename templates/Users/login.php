<!-- in /templates/Users/login.php -->
<?php
use Cake\Core\Configure;
?>

<div class="d-flex justify-content-center align-items-start min-vh-100" style="align-items: flex-start !important;">
    <div class="w-100" style="max-width: 600px;">

        <div class="row justify-content-center p-0 m-0">
            <div class="col-12 p-0 m-0">
                <hr />
                <div class="form content text-center">
                    <h3>
                        <?= __('Welcome to the registration platform of') ?><br />
                        <?php echo $this->Html->image(Configure::read('App.logo'), [
                            "alt" => $config['clubName'],
                            'class' => "navbar-item",
                            'height' => '50',
                            'loading' => 'lazy'
                        ]);
                        ?><br />
                    </h3>
                    <?= __("If this is your first year at the club, you need to start by ") ?>
                    <?= $this->Html->link(__("creating an account"), ['controller' => 'Members', 'action' => 'register']) ?>.
                    <br />
                    <?= __("Once logged in, you will see a link to register for the current season.") ?>
                    <br />
                    <a href="https://angelscheerleaders.ch/home/inscription/"><?= __('Help on how to register') ?></a>
                    <hr />
                    <?= __("If you've been in the club before, use your account. If you have any issues, you can write to ") ?>
                    <?= $this->Html->link(__("Florian"), "mailto:florian@angelscheerleaders.ch") ?>.
                    (Whatsapp: 076 384 14 05)
                </div>
            </div>
        </div>

        <div class="row justify-content-center p-0 m-0 mt-4">
            <div class="col-12 p-0 m-0">
                <div class="form content text-center">
                    <?= $this->Flash->render() ?>
                    <h3><?= __('Login') ?></h3>
                    
                    <!-- Tab Navigation -->
                    <ul class="nav nav-tabs justify-content-center mb-3" id="loginTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="email-login-tab" data-bs-toggle="tab" data-bs-target="#email-login" type="button" role="tab" aria-controls="email-login" aria-selected="true">
                                <?= __('With email') ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="username-login-tab" data-bs-toggle="tab" data-bs-target="#username-login" type="button" role="tab" aria-controls="username-login" aria-selected="false">
                                <?= __('With login') ?>
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="loginTabsContent">
                        <!-- Email Login Tab -->
                        <div class="tab-pane fade show active" id="email-login" role="tabpanel" aria-labelledby="email-login-tab">
                            <?= $this->Form->create(null, [
                                'id' => 'email-login-form',
                                'url' => ['action' => 'emailLogin'],
                                'autocomplete' => 'on'
                            ]) ?>
                            <fieldset>
                                <legend><?= __('Enter your full name to receive a login link by email') ?></legend>
                                <i><?= __('We will send you a secure link to access your account') ?></i>

                                <?= $this->Form->control('full_name', [
                                    'required' => true, 
                                    'label' => "",
                                    'placeholder' => __('Full Name (First Name Last Name)'),
                                    'autocomplete' => 'name',
                                    'id' => 'full-name-input',
                                    'name' => 'full_name'
                                ]) ?>
                            </fieldset>
                            <?= $this->Form->submit(__('Send login link')); ?>
                            <?= $this->Form->end() ?>
                        </div>

                        <!-- Username Login Tab -->
                        <div class="tab-pane fade" id="username-login" role="tabpanel" aria-labelledby="username-login-tab">
                            <?= $this->Form->create(null, [
                                'id' => 'login-form',
                                'autocomplete' => 'on'
                            ]) ?>
                            <fieldset>
                                <legend><?= __('Please enter your username and password') ?></legend>
                                <i><?= __('You need one account per athlete') ?></i>

                                <?= $this->Form->control('username', [
                                    'required' => true, 
                                    'label' => __('Username'),
                                    'autocomplete' => 'username',
                                    'id' => 'username-input',
                                    'name' => 'username'
                                ]) ?>
                                <?= $this->Form->control('password', [
                                    'required' => true, 
                                    'label' => __('Password'),
                                    'autocomplete' => 'current-password',
                                    'id' => 'password-input',
                                    'name' => 'password'
                                ]) ?>
                            </fieldset>
                            <?= $this->Form->submit(__('Login')); ?>
                            <?= $this->Form->end() ?>
                        </div>
                    </div>

                    <hr />

                    <?= $this->Html->link(__("Create an account"), ['controller' => 'Members', 'action' => 'register']) ?>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <?= $this->Html->link(__("Password forgotten"), ['action' => 'forgotPassword']) ?>
                </div>
            </div>
        </div>
        <div class="row justify-content-center p-0 m-0 mt-4">
            <div class="col-12 p-0 m-0">
                <hr />
                <div class="form content text-center">
                    Retourner sur le site des <a href="https://angelscheerleaders.ch">Angels Cheerleaders</a>
                    <br />
                    <?= $this->my->LanguageLinks($config) ?>
                </div>
            </div>
        </div>
    </div>
</div>










