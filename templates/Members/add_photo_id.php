<div class="row">
    <div class="col">
        <div class="form content">
            <div class="row">
                <div class="col">
                    <?= $this->BackButton($refer, true) ?>
                    <br />
                    <h3><?= __('Add your identity card') ?></h3>
                </div>
            </div>
            <?= $this->Flash->render() ?>
            <?= $this->Form->create(null, ['type' => 'file']) ?>
            <fieldset>
                <?php
                $acceptedFormats = 'image/jpeg';
                $heicSupported = \App\Utility\ImageHelper::isHeicSupported();
                if ($heicSupported) {
                    $acceptedFormats .= ',image/heic,image/heif';
                }
                ?>
                <?= $this->Form->file('submittedfile', ['accept' => $acceptedFormats]); ?>
            </fieldset>
            <br />
            <?= $this->Form->submit(__('Send')); ?>
            <?= $this->Form->end() ?>
            <br />

            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <?php if ($heicSupported): ?>
                    <strong><?= __('Supported formats:') ?></strong> 
                    <?= __('JPEG (.jpg, .jpeg) and HEIC (.heic, .heif)') ?><br>
                    <small class="text-muted">
                        <?= __('HEIC images from iOS devices will be automatically converted to JPEG.') ?>
                    </small>
                <?php else: ?>
                    <strong><?= __('Supported formats:') ?></strong> 
                    <?= __('JPEG (.jpg, .jpeg) only') ?><br>
                    <small class="text-muted">
                        <?= __('If you have an iPhone, please convert HEIC pictures to JPEG before uploading.') ?>
                    </small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
