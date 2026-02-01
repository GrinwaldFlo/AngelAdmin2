<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member $member
 */

if (isset($curUser)) {
    $allowEdit = $curRole->MemberEditAll || ($curRole->MemberEditOwn && $member->id == $curUser->member_id);
    $allowQuit = $curRole->MemberEditAll || ($curRole->MemberEditOwn && $member->id == $curUser->member_id && $config['allowResiliation']);
    $allowViewBills = $curRole->BillViewAll || $member->id == $curUser->member_id;
    $isOwn = ($curRole->MemberEditOwn && $member->id == $curUser->member_id);

    $imgUrl = $member->ImgExists ? $member->GetImgUrl(500) . '?' . rand() : '/img/cheerPlaceholder.jpg';
    $imgIdUrl = $member->ImgIdExists ? $member->GetImgIdUrl(500) . '?' . rand() : '/img/carteId.jpg';
} else {
    $allowEdit = false;
    $allowQuit = false;
    $allowViewBills = false;
    $isOwn = false;
}

$contactEmail = $config['email'];
?>

<?php if (isset($hashError) && $hashError): ?>
    <?= $this->element('Members/error', compact('contactEmail')) ?>
<?php else: ?>
    <div class="row p-0 m-0 mb-1">
        <div class="col p-0 m-0">
            <div class="members view content">
                <?= $this->element('Members/header', compact('member', 'isLogged', 'curRole', 'isOwn')) ?>

                <?= $this->element('Members/registration_alert', compact('member', 'isOwn', 'config', 'isMobile', 'contactEmail')) ?>

                <div class="row px-0 mx-0">
                    <?= $this->element('Members/profile_card', compact('member', 'allowEdit', 'imgUrl', 'imgIdUrl')) ?>
                    <?= $this->element('Members/details', compact('member', 'allowEdit', 'allowQuit', 'curRole', 'config')) ?>
                </div>
            </div>
        <hr />        
        <?php if (sizeof($files) > 0 || $member->RegExists($config['year'])): ?>
            <?= $this->element('Members/files', compact('member', 'files', 'config')) ?>
        <?php endif; ?>

        <?php if (!empty($shopItems) && $isOwn && sizeof($shopItems) > 0 && $member->registered): ?>
            <?= $this->element('Members/shop', compact('member', 'shopItems', 'isOwn')) ?>
            <hr />
        <?php endif; ?>

        <?= $this->element('Members/bills', compact('member', 'allowViewBills', 'curRole')) ?>
        
        <?= $this->element('Members/attendance', compact('member', 'attendance')) ?>
    </div>
<?php endif; ?>

