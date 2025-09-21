<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member $member
 */
?>
<div class="row">
  <div class="col">
    <div class="members form content">
      <div class="row">
        <div class="col">
          <?= $this->BackButton($refer, true) ?>
          <h3><?= __('Add Member') ?></h3>
        </div>
      </div>
      <?= $this->Form->create($member) ?>
      <fieldset>
        <?php
        echo $this->Form->control('first_name', ['label' => __('First name')]);
        echo $this->Form->control('last_name', ['label' => __('Last name')]);
        echo $this->Form->control('date_birth', ['label' => __('Date of birth')]);
        echo $this->Form->control('gender_id', ['options' => $member->GenderList, 'label' => __('Gender')]);
        echo $this->Form->control('address', ['label' => __('Address')]);
        echo $this->Form->control('postcode', ['label' => __('Postal code')]);
        echo $this->Form->control('city', ['label' => __('City')]);
        echo $this->Form->control('phone_mobile', ['label' => __('Mobile')]);
        echo $this->Form->control('phone_home', ['label' => __('Home phone'), 'type' => 'hidden']);
        echo $this->Form->control('email', ['label' => __('Email')]);
        echo $this->Form->control('email_valid', ['value' => 0, 'type' => 'hidden']);
        echo $this->Form->control('nationality', ['label' => __('Nationality')]);
        $this->my->InputFields($member, $curRole->MemberEditAll);
        echo $this->Form->control('date_arrival', ['type' => 'hidden', 'value' => new DateTime()]);
        echo $this->Form->control('multi_payment', ['options' => $this->my->multiPaymentList(), 'label' => __x('Payment splited in multiple installments', "Split payment")]);
        echo $this->Form->control('membership_fee_paid', ['value' => 0, 'type' => 'hidden']);
        echo $this->Form->control('discount', ['value' => 0, 'label' => __('Family discount')]);
        echo $this->Form->control('date_fin', ['type' => 'hidden']);
        echo $config['showCommMethod'] ? $this->Form->control('communication_method_id', ['options' => $member->CommunicationMethodList, 'label' => __('Communication Method')]) : "";
        echo $this->Form->control('active', ['value' => 1, 'type' => 'hidden']);
        echo $this->Form->control('coach', ['label' => __('Is coach')]);
        echo $this->Form->control('registered', ['value' => 0, 'type' => 'hidden']);
        echo $config['showBvr'] ? $this->Form->control('bvr', ['label' => __('Payment slip')]) : "";
        echo $this->Form->control('teams._ids', ['style' => "height:300px", 'label' => __('Team')]);
        ?>
      </fieldset>
      <?= $this->Form->button(__('Submit')) ?>
      <?= $this->Form->end() ?>
    </div>
  </div>
</div>
