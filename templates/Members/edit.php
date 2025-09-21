<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member $member
 */
$canEdit = $curRole->MemberEditAll;

?>
<div class="row">
  <div class="col">
    <div class="members form content">
      <div class="row">
        <div class="col">
          <?= $this->BackButton($refer, true) ?>
          <h3><?= __('Edit Member') ?></h3>
        </div>
      </div>
      <?= $this->Form->create($member) ?>
      <fieldset>
        <?php
        echo $this->Form->control('first_name', ['label' => __('First name'), 'required' => !$canEdit]);
        echo $this->Form->control('last_name', ['label' => __('Last name'), 'required' => !$canEdit]);
        echo $this->Form->control('date_birth', ['label' => __('Date of birth'), 'required' => !$canEdit]);
        echo $this->Form->control('gender_id', ['options' => $member->GenderList, 'label' => __('Gender'), 'required' => !$canEdit]);
        echo $this->Form->control('address', ['label' => __('Address'), 'required' => !$canEdit]);
        echo $this->Form->control('postcode', ['label' => __('Postal code'), 'required' => !$canEdit]);
        echo $this->Form->control('city', ['label' => __('City'), 'required' => !$canEdit]);
        echo $this->Form->control('phone_mobile', ['label' => __('Mobile'), 'required' => !$canEdit]);
        //echo $this->Form->control('phone_home', ['label' => __('Home phone')]);
        echo $this->Form->control('email', ['label' => __('Email'), 'required' => !$canEdit]);
        echo $this->Form->control('nationality', ['label' => __('Nationality'), 'required' => !$canEdit]);
        $this->my->InputFields($member, $curRole->MemberEditAll);
        echo $canEdit ? $this->Form->control('date_arrival', ['label' => __('Date of joining')]) : '';
        echo $this->Form->control('multi_payment', ['options' => $this->my->multiPaymentList(), 'label' => __x('Payment splited in multiple installments', "Split payment")]);
        echo $canEdit ? $this->Form->control('membership_fee_paid', ['label' => __('Membership paid')]) : '';
        echo $canEdit ? $this->Form->control('discount', ['label' => __('Family discount')]) : '';
        echo $canEdit ? $this->Form->control('date_fin', ['label' => __('Date of leaving')]) : '';
        echo $config['showCommMethod'] ? $this->Form->control('communication_method_id', ['options' => $member->CommunicationMethodList, 'label' => __('Communication Method')]) : "";
        echo $canEdit ? $this->Form->control('active', ['label' => __('Active')]) : '';
        echo $canEdit ? $this->Form->control('coach', ['label' => __('Is coach')]) : '';
        echo $canEdit ? $this->Form->control('registered', ['label' => __('Registered')]) : '';
        echo $config['showBvr'] ? $this->Form->control('bvr', ['label' => __('Payment slip')]) : "";
        echo $canEdit ? $this->Form->control('teams._ids', ['style' => "height:300px", 'label' => __('Team')]) : '';
        ?>
      </fieldset>
      <?= $this->Form->button(__('Submit')) ?>
      <?= $this->Form->end() ?>
    </div>
  </div>
</div>
