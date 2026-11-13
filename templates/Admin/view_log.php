<?php
$this->assign('title', ucfirst($type) . ' Log');
?>
<h1><?= h(ucfirst($type)) ?> Log</h1>
<nav>
    <?= implode(' | ', array_map(
        fn(string $logType) => $this->Html->link(ucfirst($logType) . ' Log', ['action' => 'view_log', $logType]),
        $logTypes
    )) ?>
    <?= $this->Form->postLink(__('Delete Logs'), ['action' => 'viewLog', $type], [
        'class' => 'btn btn-delete btn-sm',
        'confirm' => __('Delete all log files?'),
    ]) ?>
</nav>
<style>
    .log-lines { background: #222; color: #eee; padding: 1em; font-family: monospace; font-size: 1em; }
    .log-line { line-height: 1; padding: 0; margin: 0; }
    .log-line .level-error { color: #ff6b6b; font-weight: bold; }
    .log-line .level-warning { color: #ffd166; font-weight: bold; }
    .log-line .level-info { color: #6ec6ff; }
    .log-line .level-debug { color: #b2f7ef; }
    .log-line-number { color: #888; margin-right: 0.5em; }
</style>
<div class="log-lines">
<?php foreach ($lines as $i => $line) :
    $level = '';
    if (stripos($line, 'error') !== false) {
        $level = 'level-error';
    } elseif (stripos($line, 'warning') !== false) {
        $level = 'level-warning';
    } elseif (stripos($line, 'info') !== false) {
        $level = 'level-info';
    } elseif (stripos($line, 'debug') !== false) {
        $level = 'level-debug';
    }
    ?>
    <div class="log-line">
       <span class="<?= $level ?>"><?= h($line) ?></span>
    </div>
<?php endforeach; ?>
</div>
