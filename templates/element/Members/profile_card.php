<?php
/**
 * Member Profile Card Element
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Member $member
 * @var bool $allowEdit
 * @var string $imgUrl
 * @var string $imgIdUrl
 */
?>
<div class="col-sm-12 col-md-4 px-0 mx-0">
    <div class="card">
        <div class="d-flex justify-content-between align-items-center" style="position: relative;">
            <?= $allowEdit ? $this->my->butPhotoAddEdit($member->ImgExists, 'addPhoto', $member->id) : "" ?>
            <?= $this->Html->image($imgUrl, ['alt' => 'Portrait', 'class' => 'card-img-top']); ?>
        </div>
        <br />
        <?= __("Identity card") ?>
        <div class="d-flex justify-content-between align-items-center" style="position: relative;">
            <?= $allowEdit ? $this->my->butPhotoAddEdit($member->ImgIdExists, 'addPhotoId', $member->id) : "" ?>
            <?= $this->Html->image($imgIdUrl, ['alt' => 'Id card', 'class' => 'card-img-top']); ?>
        </div>

        <div class="card-body">
            <?php if ($member->active): ?>
                <span class="badge bg-info"><?= __('Active') ?></span>
            <?php else: ?>
                <span class="badge bg-danger"><?= __('Past') ?></span>
            <?php endif; ?>
            <?php if ($member->registered): ?>
                <span class="badge bg-info"><?= __('Registered') ?></span>
            <?php else: ?>
                <span class="badge bg-danger"><?= __('Not registered') ?></span>
            <?php endif; ?>
            <?php if (!$member->checked): ?>
                <span class="badge bg-danger"><?= __('Not reviewed') ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>
