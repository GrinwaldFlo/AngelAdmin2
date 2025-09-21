<?php
/**
 * @var \App\View\AppView $this
 * @var string $message
 * @var string $url
 */
use Cake\Core\Configure;
use Cake\Error\Debugger;

$this->setLayout('error');

if (Configure::read('debug')):
    $this->setLayout('dev_error');

    $this->assign('title', $message);
    $this->assign('templateName', 'error500.php');

    $this->start('file');
    ?>
<?php if ($error instanceof Error): ?>
    <?php $file = $error->getFile() ?>
    <?php $line = $error->getLine() ?>
    <strong>Error in: </strong>
    <?= $this->Html->link(sprintf('%s, line %s', Debugger::trimPath($file), $line), Debugger::editorUrl($file, $line)); ?>
<?php endif; ?>
<?php
        echo $this->element('auto_table_warning');

        $this->end();
endif;

$this->assign('title', __('Internal Server Error'));
?>

<div class="d-flex align-items-center justify-content-center mb-3">
    <i class="bi bi-exclamation-octagon-fill text-danger me-2" style="font-size: 2rem;"></i>
    <h2 class="mb-0 text-danger fw-bold"><?= __('An internal error has occurred') ?></h2>
</div>

<div class="alert alert-danger border-0 shadow-sm" role="alert">
    <div class="d-flex align-items-start">
        <i class="bi bi-bug-fill text-danger me-3 mt-1"></i>
        <div>
            <strong class="d-block mb-2"><?= __('Error 500 - Server Problem') ?></strong>
            <p class="mb-0">
                <strong><?= __('Details:') ?></strong>
                <?= h($message) ?>
            </p>
        </div>
    </div>
</div>

<div class="mt-4 p-3 bg-light rounded-3 border">
    <h6 class="text-muted mb-2">
        <i class="bi bi-tools me-1"></i>
        <?= __('What to do now?') ?>
    </h6>
    <ul class="list-unstyled mb-0 small text-muted">
        <li class="mb-1">
            <i class="bi bi-arrow-clockwise text-primary me-2"></i>
            <?= __('Try refreshing the page in a few moments') ?>
        </li>
        <li class="mb-1">
            <i class="bi bi-house text-primary me-2"></i>
            <?= __('Return to the homepage') ?>
        </li>
        <li class="mb-1">
            <i class="bi bi-envelope text-primary me-2"></i>
            <?= __('If the problem persists, contact our technical team') ?>
        </li>
    </ul>
</div>

<div class="mt-3 text-center">
    <small class="text-muted">
        <i class="bi bi-clock me-1"></i>
        <?= __('Error automatically reported') ?>
    </small>
</div>
