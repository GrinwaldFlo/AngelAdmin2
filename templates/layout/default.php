<?php
/**
 * @var \Cake\View\View $this
 */
use Cake\Core\Configure;
/**
 * Default `html` block.
 */
if (!$this->fetch('html')) {
    $this->start('html');
    if (Configure::check('App.language')) {
        printf('<html lang="%s">', Configure::read('App.language'));
    } else {
        echo '<html>';
    }
    $this->end();
}

if (Configure::read('App.isBeta')) {
    $this->prepend('css', '<style>body { background-color:rgb(233, 156, 156) !important; }</style>');
}

/**
 * Default `title` block.
 */
$this->start('title');
echo $config['clubName'] . ': ';
echo (!empty($title) ? $title : $this->fetch('title'));
$this->end();


/**
 * Default `footer` block.
 */
if (!$this->fetch('tb_footer')) {
    $this->start('tb_footer');
    printf('&copy; 2012-%s Angels Cheerleaders', date('Y'));
    $this->end();
}

/**
 * Default `body` block.
 */
$this->prepend('tb_body_attrs', ' class="' . implode(' ', [$this->request->getParam('controller'), $this->request->getParam('action')]) . '" ');
if (!$this->fetch('tb_body_start')) {
    $this->start('tb_body_start');
    echo '<body' . $this->fetch('tb_body_attrs') . '>';
    $this->end();
}
/**
 * Default `flash` block.
 */
if (!$this->fetch('tb_flash')) {
    $this->start('tb_flash');
    echo $this->fetch('flash');
    $this->end();
}
if (!$this->fetch('tb_body_end')) {
    $this->start('tb_body_end');
    echo '</body>';
    $this->end();
}
/**
 * Prepend `meta` block with `author` and `favicon`.
 */
if (Configure::check('App.author')) {
    $this->prepend('meta', $this->Html->meta('author', null, ['name' => 'author', 'content' => Configure::read('App.author')]));
}

// Enhanced favicon setup for better browser support
$this->prepend('meta', $this->Html->meta('favicon.ico', '/favicon.ico', ['type' => 'icon']));
$this->prepend('meta', $this->Html->meta('icon', '/favicon-32x32.png', ['type' => 'image/png', 'sizes' => '32x32']));
$this->prepend('meta', $this->Html->meta('icon', '/favicon-16x16.png', ['type' => 'image/png', 'sizes' => '16x16']));
$this->prepend('meta', $this->Html->meta('apple-touch-icon', '/apple-touch-icon.png', ['type' => 'image/png', 'sizes' => '180x180']));
$this->prepend('meta', '<link rel="manifest" href="/site.webmanifest">');

/**
 * Prepend `css` block with Bootstrap stylesheets
 * Change to bootstrap.min to use the compressed version
 */
$this->prepend('css', $this->Html->css(['cake.css']));
$this->prepend('css', $this->Html->css(['icon.css']));
$this->prepend('css', $this->Html->css(['mobile-nav']));
//$this->prepend('css', $this->Html->css(['icon/icons.css']));

$this->prepend('script', $this->Html->script(['jquery.min']));

if (Configure::read('debug')) {
    $this->prepend('css', $this->Html->css(['BootstrapUI.bootstrap']));
} else {
    $this->prepend('css', $this->Html->css(['BootstrapUI.bootstrap.min']));
}


/**
 * Prepend `script` block with jQuery, Popper and Bootstrap scripts
 * Change jquery.min and bootstrap.min to use the compressed version
 */
if (Configure::read('debug')) {
    $this->prepend('script', $this->Html->script(['BootstrapUI.bootstrap.bundle']));
} else {
    $this->prepend('script', $this->Html->script(['BootstrapUI.bootstrap.bundle.min']));
}
?>
<!doctype html>
<head>
  <?= $this->Html->charset() ?>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?= h($this->fetch('title')) ?></title>
  <?= $this->fetch('meta') ?>
  <?= $this->fetch('css') ?>  
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
</head>

<?php if ($this->getRequest()->getAttribute('maintenanceBanner')): ?>
            <div style="background: orange; color: #fff; padding: 10px; text-align: center;">
                <strong><?= __("Maintenance Mode Enabled") ?></strong>
            </div>
<?php endif; ?>

<?php if ($isLogged): ?>
          <nav class="navbar navbar-expand-lg navbar-light bg-white">
            <a class="navbar-brand" href="#">
              <?=
                  $this->Html->image(Configure::read('App.logo'), [
                      "alt" => "Home",
                      'url' => ['controller' => 'Pages', 'action' => 'Home'],
                      'class' => "navbar-item",
                      'height' => '50',
                      'loading' => 'lazy'
                  ]);
              ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
              <span class="navbar-toggler-icon"></span>
            </button>


            <div class="collapse navbar-collapse" id="navbarSupportedContent">

              <ul class="navbar-nav me-auto">
                <li class="nav-item">
                  <?= $this->Html->link(__('Home'), ['controller' => 'Pages', 'action' => 'home'], ['class' => 'nav-link']) ?>
                </li>
                <li class="nav-item">
                  <?= $this->Html->link(__('My account'), ['controller' => 'Members', 'action' => 'view', $curUser->member_id], ['class' => 'nav-link']) ?>
                </li>
                <?php if ($curRole->MemberViewAll): ?>
                          <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                              <?= __('Members') ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdownMembers">
                              <li><?= $this->Html->link(__('List'), ['controller' => 'Members', 'action' => 'index'], ['class' => 'dropdown-item']); ?></li>
                              <li><?= $this->Html->link(__('Signatures validation'), ['controller' => 'Members', 'action' => 'signatures'], ['class' => 'dropdown-item']); ?></li>
                              <li><?= $this->Html->link(__('Invite'), ['controller' => 'Members', 'action' => 'invite'], ['class' => 'dropdown-item']); ?></li>
                              <li><?= $this->Html->link(__('Ordered items'), ['controller' => 'MemberOrders', 'action' => 'index'], ['class' => 'dropdown-item']); ?></li>
                            </ul>
                          </li>
                <?php endif; ?>
                <?php if ($curRole->MemberViewAll): ?>
                          <li class="nav-item">
                            <?= $this->Html->link(__('Attendance'), ['controller' => 'Meetings', 'action' => 'index'], ['class' => 'nav-link']); ?>
                          </li>
                <?php endif; ?>
                <?php if ($curRole->BillViewAll): ?>
                          <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                              <?= __('Invoices') ?>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                              <?= $curRole->BillViewAll ? $this->Html->link(__('List'), ['controller' => 'Bills', 'action' => 'index'], ['class' => 'dropdown-item']) : '' ?>
                              <?= $curRole->BillViewAll ? $this->Html->link(__('Simple'), ['controller' => 'Bills', 'action' => 'simple'], ['class' => 'dropdown-item']) : '' ?>
                              <?= $curRole->BillViewAll ? $this->Html->link(__('Year Overview'), ['controller' => 'Bills', 'action' => 'overview'], ['class' => 'dropdown-item']) : '' ?>
                              <?= $curRole->BillValidate ? $this->Html->link(__('Batch validation'), ['controller' => 'Bills', 'action' => 'batchValidation'], ['class' => 'dropdown-item']) : '' ?>
                              <?= $curRole->BillValidate ? $this->Html->link(__('Send reminders'), ['controller' => 'Bills', 'action' => 'sendReminder'], ['class' => 'dropdown-item']) : '' ?>
                              <?= $curRole->BillEditAll ? $this->Html->link(__('Batch add'), ['controller' => 'Bills', 'action' => 'batchAdd'], ['class' => 'dropdown-item']) : '' ?>
                              <?= $curRole->BillEditAll ? $this->Html->link(__('Send email'), ['controller' => 'Bills', 'action' => 'mailSend'], ['class' => 'dropdown-item']) : '' ?>
                              <?= $curRole->BillEditAll ? $this->Html->link(__('Print'), ['controller' => 'Bills', 'action' => 'mailPrint'], ['class' => 'dropdown-item']) : '' ?>
                            </div>
                          </li>
                <?php endif; ?>
                <?php if ($curRole->Admin || $curRole->MemberViewAll): ?>
                          <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                              <?= __('Admin') ?>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                              <?= $this->Html->link(__('Teams'), ['controller' => 'Teams', 'action' => 'index'], ['class' => 'dropdown-item']); ?>
                              <?= $this->Html->link(__('Shop items'), ['controller' => 'ShopItems', 'action' => 'index'], ['class' => 'dropdown-item']); ?>
                              <?= $curRole->Admin ? $this->Html->link(__('Dashboard'), ['controller' => 'Admin', 'action' => 'dashboard'], ['class' => 'dropdown-item']) : '' ?>
                              <?= $curRole->Admin ? $this->Html->link(__('Users'), ['controller' => 'Users', 'action' => 'index'], ['class' => 'dropdown-item']) : '' ?>
                              <?= $curRole->Admin ? $this->Html->link(__('Roles'), ['controller' => 'Roles', 'action' => 'index'], ['class' => 'dropdown-item']) : '' ?>
                              <?= $curRole->Admin ? $this->Html->link(__('Contents'), ['controller' => 'Contents', 'action' => 'index'], ['class' => 'dropdown-item']) : '' ?>
                              <?= $curRole->Admin ? $this->Html->link(__('Configuration'), ['controller' => 'Configurations', 'action' => 'index'], ['class' => 'dropdown-item']) : '' ?>
                              <?= $curRole->Admin ? $this->Html->link(__('New year'), ['controller' => 'Admin', 'action' => 'new_year'], ['class' => 'dropdown-item']) : '' ?>
                              <?= $curRole->Admin ? $this->Html->link(__('Invoice Templates'), ['controller' => 'BillTemplates', 'action' => 'index'], ['class' => 'dropdown-item']) : '' ?>
                              <?= $this->Html->link(__('Subsidies'), ['controller' => 'Members', 'action' => 'subventions'], ['class' => 'dropdown-item']); ?>
                              <?= $this->Html->link(__('List'), ['controller' => 'Members', 'action' => 'list'], ['class' => 'dropdown-item']); ?>
                              <?= $this->Html->link(__('Familly discount'), ['controller' => 'Members', 'action' => 'familyReduction'], ['class' => 'dropdown-item']); ?>
                              <?= $this->Html->link(__('Birthdays'), ['controller' => 'Members', 'action' => 'birthdays'], ['class' => 'dropdown-item']); ?>
                              <?= $this->Html->link(__('Pictures'), ['controller' => 'Members', 'action' => 'pictures'], ['class' => 'dropdown-item']); ?>
                              <?= $curRole->Admin ? $this->Html->link(__('Log'), ['controller' => 'Admin', 'action' => 'view_log'], ['class' => 'dropdown-item']) : '' ?>
                            </div>
                          </li>
                <?php endif; ?>
                <?php if (!empty($externalLinks)): ?>
                          <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                              <?= __('External links') ?>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                              <?php foreach ($externalLinks as $link): ?>
                                        <?= $this->Html->link(strip_tags($link['text']), $link['url'], ['class' => 'dropdown-item', 'target' => '_blank']); ?>
                              <?php endforeach; ?>
                            </div>
                          </li>
                <?php endif; ?>
                <li class="nav-item">
                  <?= $config['showInfo'] ? $this->Html->link(__('Info'), ['controller' => 'Contents', 'action' => 'info'], ['class' => 'nav-link']) : ""; ?>
                </li>
                <?php $lngList = $this->my->Languages($config); ?>
                <?php if (sizeof($lngList) > 0): ?>
                          <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle flag" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                              <?= ''; //__('Language') ?>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                              <?php foreach ($lngList as $lng): ?>
                                        <?= $this->Html->link($lng[0], ['controller' => 'Members', 'action' => 'setLanguage', $lng[1]], ['class' => 'dropdown-item']) ?>
                              <?php endforeach; ?>
                            </div>
                          </li>
                <?php endif; ?>
                <li class="nav-item">
                  <?= $this->Html->link(__('Logout'), ['controller' => 'Users', 'action' => 'logout'], ['class' => 'nav-link']) ?>
                </li>
              </ul>
              <?php if ($curRole->Admin || $curRole->MemberViewAll): ?>
                        <!--<form class="form-inline my-2 my-lg-0">
          <input class="form-control mr-sm-2" type="search" placeholder="Search" aria-label="Search">
        </form>-->
              <?php endif; ?>
            </div>
          </nav>
<?php endif; ?>

<main class="main">
  <div class="container p-0 <?= $wide ?>">
    <?= $this->Flash->render() ?>

    <?php
    echo $this->fetch('script');
    echo $this->fetch('tb_body_start');
    echo $this->fetch('tb_flash');
    echo $this->fetch('content');
    echo $this->fetch('tb_footer');
    echo $this->fetch('tb_body_end');
    ?>
  </div>
</main>


</html>
