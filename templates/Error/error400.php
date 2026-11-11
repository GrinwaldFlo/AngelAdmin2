<?php
/**
 * @var \App\View\AppView $this
 * @var string $message
 * @var string $url
 * @var \Throwable $error
 */
use Authorization\Exception\ForbiddenException;
use Cake\Core\Configure;
use Cake\Http\Exception\InvalidCsrfTokenException;

$this->setLayout('error');

if (Configure::read('debug')):
    $this->setLayout('dev_error');

    $this->assign('title', $message);
    $this->assign('templateName', 'error400.php');

    $this->start('file');
    echo $this->element('auto_table_warning');
    $this->end();
endif;

// The message for the user depends on what went wrong, not only on the status code
if ($error instanceof ForbiddenException) {
    $title = __('Access denied');
    $heading = __('You cannot open this page');
    $text = __('Your account does not have the rights to see this page. If you think it is a mistake, contact the club.');
    $suggestions = [__('Return to the homepage and use the menu'), __('Check that you are logged in with the right account')];
} elseif ($error instanceof InvalidCsrfTokenException) {
    $title = __('Page expired');
    $heading = __('The page has expired');
    $text = __('The page was open for too long or your browser blocked a cookie, so the form could not be sent.');
    $suggestions = [__('Go back, reload the page and try again')];
} else {
    $title = __('Page Not Found');
    $heading = __('Error 404 - Page Not Found');
    $text = __('The requested address {0} was not found on this server.', "<code class='text-primary'>'{$url}'</code>");
    $suggestions = [__('Check the URL in the address bar'), __('Return to the homepage and start over')];
}

$this->assign('title', $title);
?>

<div class="d-flex align-items-center justify-content-center mb-3">
    <i class="bi bi-exclamation-triangle-fill text-warning me-2 error-heading-icon"></i>
    <h2 class="mb-0 text-danger fw-bold"><?= h($title) ?></h2>
</div>

<div class="alert alert-danger border-0 shadow-sm" role="alert">
    <div class="d-flex align-items-start">
        <i class="bi bi-info-circle-fill text-danger me-3 mt-1"></i>
        <div>
            <strong class="d-block mb-2"><?= h($heading) ?></strong>
            <p class="mb-0"><?= $text ?></p>
        </div>
    </div>
</div>

<div class="mt-4 p-3 bg-light rounded-3 border">
    <h6 class="text-muted mb-2">
        <i class="bi bi-lightbulb me-1"></i>
        <?= __('Suggestions:') ?>
    </h6>
    <ul class="list-unstyled mb-0 small text-muted">
        <?php foreach ($suggestions as $suggestion): ?>
            <li class="mb-1">
                <i class="bi bi-check2 text-success me-2"></i>
                <?= h($suggestion) ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
