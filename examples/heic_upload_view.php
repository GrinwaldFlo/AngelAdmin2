<?php
/**
 * Example view helper usage for HEIC-aware file uploads
 * Place this in your view file where photo uploads are handled
 */

// Load the ImageSupport component to get current capabilities
$this->loadHelper('ImageSupport');
$imageSupport = $this->ImageSupport->getSupportedFormats();
?>

<!-- Photo Upload Form Example -->
<div class="photo-upload-section">
    <h3><?= __('Upload Photo') ?></h3>
    
    <?= $this->Form->create(null, ['type' => 'file', 'id' => 'photo-upload-form']) ?>
    
    <div class="form-group">
        <label for="photo-file"><?= __('Select Photo') ?></label>
        
        <?= $this->Form->control('submittedfile', [
            'type' => 'file',
            'label' => false,
            'accept' => $imageSupport['accepted_types'],
            'id' => 'photo-file',
            'class' => 'form-control',
            'data-max-size' => '10485760' // 10MB
        ]) ?>
        
        <small class="form-text text-muted">
            <?php if ($imageSupport['heic']): ?>
                <i class="fas fa-check-circle text-success"></i>
                <?= __('Accepts: JPEG and HEIC images (up to 10MB)') ?>
                <br>
                <em><?= __('HEIC images from iOS devices will be automatically converted to JPEG') ?></em>
            <?php else: ?>
                <i class="fas fa-info-circle text-info"></i>
                <?= __('Accepts: JPEG images only (up to 10MB)') ?>
                <br>
                <em><?= __('If you have HEIC images from iOS, please convert them to JPEG first') ?></em>
            <?php endif; ?>
        </small>
    </div>
    
    <?= $this->Form->button(__('Upload Photo'), [
        'type' => 'submit',
        'class' => 'btn btn-primary'
    ]) ?>
    
    <?= $this->Form->end() ?>
</div>

<!-- JavaScript for enhanced UX -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('photo-file');
    const form = document.getElementById('photo-upload-form');
    
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        // Check file size
        const maxSize = parseInt(fileInput.dataset.maxSize);
        if (file.size > maxSize) {
            alert('<?= __("File is too large. Maximum size is 10MB.") ?>');
            fileInput.value = '';
            return;
        }
        
        // Show file info
        const fileName = file.name;
        const fileSize = (file.size / 1024 / 1024).toFixed(2);
        const fileType = file.type;
        
        console.log(`Selected: ${fileName} (${fileSize}MB, ${fileType})`);
        
        // Show HEIC conversion notice
        <?php if ($imageSupport['heic']): ?>
        if (fileType.includes('heic') || fileType.includes('heif')) {
            const notice = document.createElement('div');
            notice.className = 'alert alert-info mt-2';
            notice.innerHTML = '<i class="fas fa-magic"></i> <?= __("HEIC image selected - will be converted to JPEG during upload") ?>';
            
            // Remove any existing notices
            const existingNotice = form.querySelector('.alert-info');
            if (existingNotice) existingNotice.remove();
            
            fileInput.parentNode.appendChild(notice);
        }
        <?php endif; ?>
    });
});
</script>
