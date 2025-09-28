<?php
/**
 * Member Details Element
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member $member
 * @var bool $allowEdit
 * @var bool $allowQuit
 * @var object $curRole
 * @var array $config
 */
?>
<div class="col-sm-12 col-md-8">
    <?php
    $this->my->respGrid(__('Gender'), h($member->GenderStr), true);
    if (empty($member->address) || empty($member->postcode) || empty($member->city))
        $this->my->respGrid(__('Adress'), "", true);
    else
        $this->my->respGrid(__('Adress'), ($this->Html->link(h($member->address . ", " . $member->postcode . " " . $member->city), 'https://maps.google.ch/maps?q=' . str_replace(" ", "+", $member->address . ", " . $member->postcode . ",+CH"), ['target' => '_blank'])));

    if ($member->phone_mobile)
        $this->my->respGrid(__('Mobile'), $this->Html->link(h($member->phone_mobile), 'tel:' . $member->phone_mobile));
    else
        $this->my->respGrid(__('Mobile'), "", true);

    if ($member->email)
        $this->my->respGrid(__('Email'), $this->Html->link(h($member->email) . ($member->email_valid ? ' ' . __('Valid') : ''), 'mailto:' . $member->email));
    else
        $this->my->respGrid(__('Email'), "", true);

    $this->my->respGrid(__('Nationality'), h($member->nationality), true);
    $this->my->fieldArray($member->fields, !$member->checked);

    if ($config['showCommMethod'])
        $this->my->respGrid(__('Communication'), $member->CommunicationMethodStr);
    if ($curRole->MemberEditAll)
        $this->my->respGrid(__('Membership paid'), $this->Number->currency($member->membership_fee_paid, 'CHF'));
    if ($member->discount)
        $this->my->respGrid(__('Family discount'), $this->Number->currency($member->discount, 'CHF'));
    $this->my->respGrid(__('Invoices (Open/Total)'), __('{0}/{1}', $this->Number->format($member->InvoicesOpen), $this->Number->format($member->InvoicesTotal)));
    $this->my->respGrid(__('Date of birth'), h($member->date_birth));
    $this->my->respGrid(__x('Payment splited in multiple installments', "Split payment"), $member->multi_payment);
    $this->my->respGrid(__('Date of joining'), h($member->date_arrival));
    if (!$member->active) {
        $this->my->respGrid(__('Date of leaving'), h($member->date_fin));
        $this->my->respGrid(__('Leaving comment'), h($member->leaving_comment));
    }
    if (!empty($member->user))
        $this->my->respGrid(__('Username'), $member->user->username);
    $this->my->respGrid(__(''), ($member->bvr) ? $this->my->tags("span", __('Payment slip'), ['class' => "badge bg-info"]) : '');
    ?>
    <br />
    <?= $allowEdit && !$member->checked ? $this->Html->link(__('The information is correct'), ['action' => 'checked', $member->id], ['class' => 'btn btn-primary btn-sm']) : '' ?>
    <?= $allowQuit && $member->active ? $this->Html->link(__('Quit membership'), ['action' => 'cancelRegistration', $member->id], ['class' => 'btn btn-primary btn-sm']) : '' ?>
    <?= $allowEdit ? $this->Html->link(__('Edit'), ['action' => 'edit', $member->id], ['class' => 'btn btn-primary btn-sm']) : '' ?>
</div>
