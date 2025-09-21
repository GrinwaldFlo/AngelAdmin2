<?php
/**
 * @var \App\View\AppView $this
 * @var string $message
 * @var string $url
 */
use Cake\Core\Configure;

$this->setLayout('error');

if (Configure::read('debug')):
    $this->setLayout('dev_error');

    $this->assign('title', $message);
    $this->assign('templateName', 'error400.php');

    $this->start('file');
    echo $this->element('auto_table_warning');
    $this->end();
endif;

$this->assign('title', __('Page Not Found'));
?>

<div class="d-flex align-items-center justify-content-center mb-3">
    <i class="bi bi-exclamation-triangle-fill text-warning me-2" style="font-size: 2rem;"></i>
    <h2 class="mb-0 text-danger fw-bold"><?= h($message) ?></h2>
</div>

<div class="alert alert-danger border-0 shadow-sm" role="alert">
    <div class="d-flex align-items-start">
        <i class="bi bi-info-circle-fill text-danger me-3 mt-1"></i>
        <div>
            <strong class="d-block mb-2"><?= __('Error 404 - Page Not Found') ?></strong>
            <p class="mb-0">
                <?= __('The requested address {0} was not found on this server.', "<code class='text-primary'>'{$url}'</code>") ?>
            </p>
        </div>
    </div>
</div>

<div class="mt-4 p-3 bg-light rounded-3 border">
    <h6 class="text-muted mb-2">
        <i class="bi bi-lightbulb me-1"></i>
        <?= __('Suggestions:') ?>
    </h6>
    <ul class="list-unstyled mb-0 small text-muted">
        <li class="mb-1">
            <i class="bi bi-check2 text-success me-2"></i>
            <?= __('Check the URL in the address bar') ?>
        </li>
        <li class="mb-1">
            <i class="bi bi-check2 text-success me-2"></i>
            <?= __('Return to the homepage and start over') ?>
        </li>
    </ul>
</div>
