<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.10.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @var \App\View\AppView $this
 */
use Cake\Core\Configure;
use Cake\Error\Debugger;
//$this->disableAutoLayout();
?>


<div class="content">
    <?php if ($curMember->RegistrationStep($config['year']) > 0): ?>
        <div class="col-12">
            <div class="alert alert-danger" role="alert">
                <h4><?= __('Registration for the year {0}-{1}', $config['year'], $config['year'] + 1) ?></h4>
                <i><?= __('Each year you need to register for the current season, please follow the steps below:') ?></i>

                <br />
                <?php if ($curMember->RegistrationStep($config['year']) == 1): ?>
                    <strong><?= __('Please check your contact information') ?></strong>
                    <?= $this->Html->link(__('The information is correct'), ['controller' => 'Members', 'action' => 'checked', $curMember->id], ['class' => 'btn btn-primary btn-sm']) ?>

                    <?php if (isset($contactEmail)): ?>
                        <a href="mailto:<?= h($contactEmail) ?>?subject=<?= urlencode(__('Ask for modifications')) ?>" class="btn btn-secondary">
                            <?= __('Ask for modifications') ?>
                        </a>
                    <?php endif; ?>
                <?php elseif ($curMember->RegistrationStep($config['year']) == 2): ?>
                    <?= $this->Html->link(__('You are a past member, click here if you want to start again'), ['controller' => 'Members', 'action' => 'active', $curMember->id]) ?>
                <?php elseif ($curMember->RegistrationStep($config['year']) == 3): ?>
                    <?php if (!$isMobile): ?>
                        <?= __('You will need to sign the aggreement for this year. A mobile phone is recomended') ?>
                    <?php endif; ?>
                    <br />
                    <?= $this->Html->link(__('You are not registered for this season, please fill this form'), ['controller' => 'Members', 'action' => 'agreement', $curMember->id]) ?>
                <?php elseif ($curMember->RegistrationStep($config['year']) == 4): ?>
                    <?= __('You have to be approved by a coach, please wait') ?>
                <?php elseif ($curMember->RegistrationStep($config['year']) == 6): ?>
                    <?= __('You have no teams, wait a while or check with your coach') ?>
                <?php else: ?>
                    <?= __('This should not append') ?>
                <?php endif; ?>

                <br /><br />
                <?php if ($curMember->active): ?>
                    <?= __('But if you would not like to continue') ?>
                    <?= $this->Html->link(__('Quit membership'), ['controller' => 'Members', 'action' => 'cancelRegistration', $curMember->id], ['class' => 'btn btn-primary btn-sm']); ?>
                <?php endif; ?>

            </div>
        </div>
    <?php endif; ?>



    <div class="row">
        <?php if (!$isMobile): ?>
            <div class="col">
                <?= $this->Html->image(($birthday ? 'happybirthday.gif' : Configure::read('App.logo')), ['alt' => 'Logo', 'width' => '250']); ?>
            </div>
        <?php endif; ?>
        <div class="col">
            <h1><?= h(($curMember->coach ? __('Coach') . ' ' : '') . $curMember->fullName) ?></h1>
            <h4><?= h($curMember->teamString) ?></h4>
        </div>
    </div>
    <hr />
    <?php if (!empty($messages)): ?>
        <div class="row">
            <div class="col-12">
                <?php foreach ($messages as $message): ?>
                    <div class="alert alert-info" role="alert">
                        <?= $message['text'] ?>
                    </div>
                <?php endforeach; ?>
                <?php Debugger::checkSecurityKeys(); ?>
            </div>
        </div>
        <hr />
    <?php endif; ?>
    <?php if (!empty($slackMessages)): ?>
        <div class="row">
            <div class="col-12">
                <h4>
                    <a href="https://angelscheerleaders.slack.com/archives/CEP6BSVRR" target="_blank"> Slack, général
                    </a>
                </h4>
                <?php foreach ($slackMessages as $slackMessage): ?>
                    <div class="alert alert-success" role="alert">
                        <?= $slackMessage ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <hr />
    <?php endif; ?>
    <?php if (!empty($contents)): ?>
        <div class="row">
            <div class="col">
                <ul>
                    <?php foreach ($contents as $key => $content): ?>
                        <?php
                        if ($key % 2 == 0)
                            echo $content['text'];
                        ?>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col">
                <ul>
                    <?php foreach ($contents as $key => $content): ?>
                        <?php
                        if ($key % 2 == 1)
                            echo $content['text'];
                        ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <hr class="animated fadeInLeft" style="visibility: visible;" />
    <?php endif; ?>

    <div class="row">
        <?= $this->my->showEvent($contentEvent[5], $contentEvent[8], $smallMeetings, $curRole, $config); ?>
        <?= $this->my->showEvent($contentEvent[7], $contentEvent[10], $doodleMeetings, $curRole, $config); ?>
        <?= $this->my->showEvent($contentEvent[6], $contentEvent[9], $bigMeetings, $curRole, $config); ?>
    </div>
    <hr class="animated fadeInLeft" style="visibility: visible;" />
    <div class="row">
        <div class="col"></div>
        <div class="col">
            <h4><?= __('Open invoices') ?></h4>
            <ul>
                <?php if ($lateBills->isEmpty()): ?>
                    <li class="bullet success"><?= __('You are up to date with your invoices') ?></li>
                <?php else: ?>
                    <?php foreach ($lateBills as $bill): ?>
                        <li class="bullet arrowRight"><?= $this->Html->link(__('{0} - {1} - {2}CHF', $bill->Reference, $bill->label, $bill->amount), $bill->BillUrl, ['target' => '_blank']) ?></li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>
