<?php
/**
 * @var \App\View\AppView $this
 */
use Cake\Core\Configure;
use Cake\Error\Debugger;

$this->setLayout('error');

if (Configure::read('debug')):
    $this->setLayout('dev_error');

    $this->assign('title', $message);
    $this->assign('templateName', 'errorXXX.php');

    $this->start('file');
    ?>
<?php if (!empty($error->queryString)): ?>
    <p class="notice">
        <strong>SQL Query: </strong>
        <?= h($error->queryString) ?>
    </p>
<?php endif; ?>
<?php if (!empty($error->params)): ?>
    <strong>SQL Query Params: </strong>
    <?php Debugger::dump($error->params) ?>
<?php endif; ?>
<?php if ($error instanceof Error): ?>
    <strong>Error in: </strong>
    <?= sprintf('%s, line %s', str_replace(ROOT, 'ROOT', $error->getFile()), $error->getLine()) ?>
<?php endif; ?>
<?php
        echo $this->element('auto_table_warning');

        $this->end();
endif;

$this->assign('title', __('System Error'));
?>

<div class="d-flex align-items-center justify-content-center mb-3">
    <span class="status-indicator status-error"></span>
    <i class="bi bi-exclamation-diamond-fill text-danger me-2" style="font-size: 2rem;"></i>
    <h2 class="mb-0 text-danger fw-bold"><?= __('A system error has occurred') ?></h2>
</div>

<div class="error-code text-center">
    <?= __('System Error') ?>
</div>

<div class="alert alert-danger border-0 shadow-sm" role="alert">
    <div class="d-flex align-items-start">
        <i class="bi bi-gear-fill text-danger me-3 mt-1"></i>
        <div>
            <strong class="d-block mb-2"><?= __('Technical error detected') ?></strong>
            <p class="mb-2">
                <strong><?= __('Error message:') ?></strong>
                <code class="d-block mt-1 p-2 bg-light rounded"><?= h($message) ?></code>
            </p>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="p-3 bg-light rounded-3 border h-100">
            <h6 class="text-muted mb-2">
                <i class="bi bi-person-gear me-1"></i>
                <?= __('For users:') ?>
            </h6>
            <ul class="list-unstyled mb-0 small text-muted">
                <li class="mb-1">
                    <i class="bi bi-arrow-clockwise text-info me-2"></i>
                    <?= __('Wait a few minutes and try again') ?>
                </li>
                <li class="mb-1">
                    <i class="bi bi-house-door text-info me-2"></i>
                    <?= __('Return to the homepage') ?>
                </li>
                <li class="mb-1">
                    <i class="bi bi-phone text-info me-2"></i>
                    <?= __('Contact support if necessary') ?>
                </li>
            </ul>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="p-3 bg-warning bg-opacity-10 rounded-3 border border-warning h-100">
            <h6 class="text-warning mb-2">
                <i class="bi bi-shield-exclamation me-1"></i>
                <?= __('Technical information:') ?>
            </h6>
            <ul class="list-unstyled mb-0 small text-muted">
                <li class="mb-1">
                    <i class="bi bi-clock text-warning me-2"></i>
                    <?= __('Error automatically reported') ?>
                </li>
                <li class="mb-1">
                    <i class="bi bi-bell text-warning me-2"></i>
                    <?= __('Our team has been notified') ?>
                </li>
                <li class="mb-1">
                    <i class="bi bi-tools text-warning me-2"></i>
                    <?= __('Resolution in progress...') ?>
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="mt-4 text-center">
    <small class="text-muted">
        <i class="bi bi-shield-check me-1"></i>
        <?= __('This error does not compromise the security of your data') ?>
    </small>
</div>
