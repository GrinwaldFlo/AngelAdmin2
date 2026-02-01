<?php
/**
 * Member Error Page Element - For invalid hash access
 * @var \App\View\AppView $this
 * @var string|null $contactEmail
 */
?>
<div class="row p-0 m-0 mb-1">
    <div class="col p-0 m-0">
        <div class="members view content">
            <div class="text-center">
                <h1 class="text-danger"><?= __('Access Denied') ?></h1>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                    <h4><?= __('Invalid or Expired Link') ?></h4>
                    <p><?= __('The link you used to access this page is either invalid or has expired.') ?></p>
                    <p><?= __('This could happen if:') ?></p>
                    <ul class="text-start">
                        <li><?= __('The link was copied incorrectly') ?></li>
                        <li><?= __('Your membership information has been updated') ?></li>
                        <li><?= __('The link has expired') ?></li>
                    </ul>
                </div>

                <div class="mt-4">
                    <h5><?= __('What can you do?') ?></h5>
                    <div class="d-grid gap-2 d-md-block">
                        <?= $this->Html->link(__('Go to Homepage'), ['controller' => 'Pages', 'action' => 'Home'], ['class' => 'btn btn-primary']) ?>
                        <?php if (isset($contactEmail)): ?>
                            <a href="mailto:<?= h($contactEmail) ?>?subject=<?= urlencode(__('Problem accessing personal page')) ?>" class="btn btn-secondary">
                                <?= __('Contact Support') ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-4 text-muted">
                    <small>
                        <?= __('If you believe this is an error, please contact the administrator with the details of how you accessed this page.') ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
