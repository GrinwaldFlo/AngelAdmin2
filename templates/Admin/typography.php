<?php
/**
 * Admin > Typography: style guide of the website.
 * Every example below uses the real classes of theme.css / cake.css / Bootstrap, so the page shows the current look.
 * Keep it in sync with webroot/css/theme.css and the "UI guidelines" section of CLAUDE.md.
 *
 * @var \App\View\AppView $this
 */
$this->Html->css('typography', ['block' => true]);
$this->Html->script('table-sort', ['block' => true]);

$sections = [
    'principles' => __('Principles'),
    'colors' => __('Colors'),
    'text' => __('Typography'),
    'buttons' => __('Buttons'),
    'icons' => __('Icons'),
    'badges' => __('Badges and status'),
    'messages' => __('Messages'),
    'tables' => __('Tables'),
    'forms' => __('Forms'),
    'layout' => __('Layout and mobile'),
    'review' => __('Don\'t'),
];

// [css token, swatch class, name, usage]
$colorGroups = [
    __('Brand') => [
        ['--aa-brand', 'tg-c-brand', '#9b4dca', __('Main brand color: save buttons, edit outline, section underline')],
        ['--aa-brand-hover', 'tg-c-brand-hover', '#7f3fb0', __('Hover of brand buttons, meetings primary')],
        ['--aa-brand-dark', 'tg-c-brand-dark', '#45006f', __('Pressed filter, list bullets, icons')],
        ['--aa-link', 'tg-c-link', '#500080', __('Text links')],
    ],
    __('Neutrals') => [
        ['--aa-bg', 'tg-c-bg', '#f5f7fa', __('Page background, alternate grid rows')],
        ['--aa-card', 'tg-c-card', '#ffffff', __('Content cards (.content)')],
        ['--aa-border', 'tg-c-border', '#e3e5e8', __('Borders, separators')],
        ['--aa-text', 'tg-c-text', '#1f2328', __('Main text')],
        ['--aa-muted', 'tg-c-muted', '#6b717a', __('Secondary text, hints (.text-muted)')],
    ],
    __('Semantic') => [
        ['--aa-success', 'tg-c-success', '#2e9d5b', __('Add / create, paid, present, success')],
        ['--aa-danger', 'tg-c-danger', '#d64545', __('Delete, late, absent, errors, missing data')],
        ['--aa-warning', 'tg-c-warning', '#e0a106', __('Bulk operations, draft, late arrival, warnings')],
        ['--aa-info', 'tg-c-info', '#3b6fd8', __('Export / download, excused, information')],
        ['--aa-nav', 'tg-c-nav', '#6c757d', __('Navigation: back, cancel, list')],
    ],
];

// [class, sample label, when to use, example labels]
$buttonTypes = [
    ['btn-save', __('Save'), __('Submit a form or confirm the main action of the page. Only one per form.'), __('Submit, Save, Validate, Send, Put up for sale')],
    ['btn-add', __('New'), __('Create a record, or a positive state change.'), __('New team, Add, Join, Mark delivered, Received, Mark paid')],
    ['btn-edit', __('Edit'), __('Open an existing record for modification.'), __('Edit, Change, Modify my data')],
    ['btn-delete', __('Delete'), __('Delete, remove, negative state change. Always a postLink with confirm.'), __('Delete, Remove, Quit membership, Mark not delivered')],
    ['btn-nav', __('Back'), __('Navigation to another page, without changing data.'), __('Back, Cancel, List, My page, Go to homepage')],
    ['btn-export', __('PDF'), __('Produce a file or a printable view.'), __('PDF, Download, Print, Grid, Email pack, CSV')],
    ['btn-bulk', __('Send all emails'), __('Operation on many records or hard to undo. Confirm or validation step required.'), __('Send all emails, Reset membership fees, Generate fees, Batch add')],
    ['btn-filter', __('Team A'), __('Filter or toggle the current list. Add .pressed on the active one.'), __('Teams, Active / Alumni, Open / Late / Paid, This year')],
];

// [markup, name, usage]
$icons = [
    ['<i class="gg-pen"></i>', 'gg-pen', __('Edit (table actions)')],
    ['<i class="gg-add-r"></i>', 'gg-add-r', __('Add (table actions)')],
    ['<i class="gg-file-document"></i>', 'gg-file-document', __('View / document')],
    ['<i class="gg-arrow-left-r"></i>', 'gg-arrow-left-r', __('Back ($this->backButton())')],
    [$this->my->icon('delete'), "my->icon('delete')", __('Material icon')],
    [$this->my->icon('arrow_back'), "my->icon('arrow_back')", __('Material icon')],
    [$this->my->symbol('check_circle'), "my->symbol('check_circle')", __('Material symbol')],
];

// [bootstrap color, meaning]
$statusBadges = [
    ['warning', __('Draft')],
    ['info', __('Sent, not due')],
    ['danger', __('Late')],
    ['success', __('Paid')],
    ['dark', __('Canceled')],
];
?>
<div class="content tg-page">
  <h2><?= __('Typography') ?></h2>
  <p class="tg-intro">
    <?= __('Guidelines of the website. Every sample is rendered with the real CSS classes, so this page shows the current look. Use these classes in new pages and when touching existing ones.') ?>
  </p>

  <ul class="tg-toc">
    <?php foreach ($sections as $anchor => $title) : ?>
      <li><a href="#<?= $anchor ?>"><?= h($title) ?></a></li>
    <?php endforeach; ?>
  </ul>

  <!-- ******************************************************************************************* -->
  <section class="tg-section" id="principles">
    <h3><?= h($sections['principles']) ?></h3>
    <ul class="tg-rules">
      <li><?= __('One color per type of action: the button class says what the button does, not how it looks.') ?></li>
      <li><?= __('Colors are CSS variables (--aa-*) in webroot/css/theme.css. Never hardcode a hex color in a template, reuse a token.') ?></li>
      <li><?= __('No inline style: no style="..." and no style option in helpers. Add a class in cake.css, theme.css or the domain CSS file.') ?></li>
      <li><?= __('Mobile first: every page works at phone width without horizontal scroll.') ?></li>
      <li><?= __('All texts in English in the source, wrapped in __() and translated in resources/locales.') ?></li>
      <li><?= __('Reuse helpers and elements (MyHelper, templates/element) instead of copying markup.') ?></li>
    </ul>
  </section>

  <!-- ******************************************************************************************* -->
  <section class="tg-section" id="colors">
    <h3><?= h($sections['colors']) ?></h3>
    <?php foreach ($colorGroups as $groupName => $colors) : ?>
      <h4><?= h($groupName) ?></h4>
      <div class="tg-swatches">
        <?php foreach ($colors as [$token, $swatchClass, $hex, $usage]) : ?>
          <div class="tg-swatch">
            <div class="tg-swatch-color <?= $swatchClass ?>"></div>
            <div class="tg-swatch-info">
              <code>var(<?= $token ?>)</code>
              <strong><?= $hex ?></strong><br>
              <span class="text-muted"><?= h($usage) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
    <p><?= __('Domain CSS files (meetings.css, admin-dashboard.css, ...) may define their own variables, but should take their values from the --aa-* tokens.') ?></p>
  </section>

  <!-- ******************************************************************************************* -->
  <section class="tg-section" id="text">
    <h3><?= h($sections['text']) ?></h3>
    <div class="tg-specimen">
      <h2><?= __('Page title') ?> <small class="text-muted">h2</small></h2>
      <h3><?= __('Main title of a list or form') ?> <small class="text-muted">h3</small></h3>
      <h4><?= __('Section of a page') ?> <small class="text-muted">h4</small></h4>
      <h5><?= __('Card / sub-section') ?> <small class="text-muted">h5</small></h5>
      <p><?= __('Body text: Bootstrap system font, line-height 1.4, color black on a white card.') ?></p>
      <p><a href="#text"><?= __('A text link') ?></a> · <span class="text-muted"><?= __('Muted text for hints and dates') ?></span> · <small><?= __('Small text') ?></small></p>
      <p><strong><?= __('Bold for labels') ?></strong> · <span class="missing"><?= __('MISSING') ?></span> <span class="text-muted">(.missing)</span></p>
    </div>
    <ul class="tg-rules">
      <li><?= __('One h2 or h3 per page as title, then h4 for sections. Do not skip levels for the size, use classes instead.') ?></li>
      <li><?= __('Labels of a data grid use .divTableLabel (MyHelper::respGrid()).') ?></li>
      <li><?= __('Numbers: $this->Number->currency($value, \'CHF\'), dates: d.m.Y.') ?></li>
    </ul>
  </section>

  <!-- ******************************************************************************************* -->
  <section class="tg-section" id="buttons">
    <h3><?= h($sections['buttons']) ?></h3>
    <p><?= __('Always .btn + one action class. Add .btn-sm in toolbars, filters and table rows. Keep the default size for the submit button of a form.') ?></p>
    <p><?= __('Sizes are responsive: compact on desktop (26px, 22px for .btn-sm), touch size below 992px (44px, 40px for .btn-sm). Resize the window to see it.') ?></p>
    <div class="table-responsive">
      <table class="table table-sm table-hover">
        <thead>
          <tr>
            <th><?= __('Sample') ?></th>
            <th><?= __('Class') ?></th>
            <th><?= __('When') ?></th>
            <th class="d-none d-md-table-cell"><?= __('Examples') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($buttonTypes as [$class, $label, $when, $examples]) : ?>
            <tr>
              <td>
                <div class="tg-sample">
                  <button type="button" class="btn <?= $class ?>"><?= h($label) ?></button>
                  <button type="button" class="btn btn-sm <?= $class ?>"><?= h($label) ?></button>
                </div>
              </td>
              <td><code>btn <?= $class ?></code></td>
              <td><?= h($when) ?></td>
              <td class="text-muted d-none d-md-table-cell"><?= h($examples) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <h4><?= __('States') ?></h4>
    <div class="tg-specimen btn-bar">
      <button type="button" class="btn btn-save" disabled><?= __('Disabled') ?></button>
      <button type="button" class="btn btn-filter"><?= __('Filter') ?></button>
      <button type="button" class="btn btn-filter pressed"><?= __('Current filter') ?></button>
    </div>

    <h4><?= __('Filter bar') ?></h4>
    <div class="tg-specimen">
      <div class="btn-bar">
        <a href="#buttons" class="btn btn-sm btn-filter pressed"><?= __('Active') ?></a>
        <a href="#buttons" class="btn btn-sm btn-filter"><?= __('Alumni') ?></a>
        <a href="#buttons" class="btn btn-sm btn-filter"><?= __('Membership not paid') ?></a>
      </div>
      <div class="btn-bar">
        <a href="#buttons" class="btn btn-sm btn-filter">Mini</a>
        <a href="#buttons" class="btn btn-sm btn-filter pressed">Junior</a>
        <a href="#buttons" class="btn btn-sm btn-filter">Senior</a>
      </div>
    </div>
    <p><?= __('Filters are generated by MyHelper::teamLinks() and MyHelper::memberFilterLinks(): a pressed filter links back to 0 (no filter).') ?></p>

    <h4><?= __('Placement') ?></h4>
    <ul class="tg-rules">
      <li><?= __('Page action (New ...) at the top right of the card, before the title: class "btn btn-add btn-sm float-end".') ?></li>
      <li><?= __('Form buttons at the bottom of the form: save first, then cancel/back.') ?></li>
      <li><?= __('Wrap groups of buttons in .btn-bar so they wrap on mobile.') ?></li>
      <li><?= __('Anything that changes data is a POST: Form->postLink() or a form button, never a GET link. Delete and bulk actions have a confirm.') ?></li>
    </ul>

    <h4><?= __('Code') ?></h4>
    <pre><code><?= h(<<<'PHP'
<?= $this->Html->link(__('New Team'), ['action' => 'add'], ['class' => 'btn btn-add btn-sm float-end']) ?>
<?= $this->Html->link(__('Edit'), ['action' => 'edit', $id], ['class' => 'btn btn-edit btn-sm']) ?>
<?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $id], ['confirm' => __('Are you sure you want to delete # {0}?', $id), 'class' => 'btn btn-delete btn-sm']) ?>
<?= $this->Html->link(__('PDF'), ['action' => 'pdf', $id], ['class' => 'btn btn-export btn-sm']) ?>
<?= $this->Form->button(__('Submit'), ['class' => 'btn btn-save']) ?>
<?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'btn btn-nav']) ?>
PHP) ?></code></pre>
    <p><?= __('Form->button() without a class renders a grey btn-secondary (BootstrapUI default): always pass the class.') ?></p>
  </section>

  <!-- ******************************************************************************************* -->
  <section class="tg-section" id="icons">
    <h3><?= h($sections['icons']) ?></h3>
    <h4><?= __('Icon buttons (table row actions)') ?></h4>
    <div class="tg-specimen">
      <?= $this->my->iconLink('visibility', '#icons', __('View')) ?>
      <?= $this->my->iconLink('edit', '#icons', __('Edit')) ?>
      <?= $this->my->iconLink('download', '#icons', __('Download')) ?>
      <?= $this->my->iconLink('delete', '#icons', __('Delete'), ['class' => 'icon-btn-delete']) ?>
    </div>
    <pre><code><?= h(<<<'PHP'
<?= $this->my->iconLink('visibility', ['action' => 'view', $id], __('View')) ?>
<?= $this->my->iconLink('edit', ['action' => 'edit', $id], __('Edit')) ?>
<?= $this->my->iconPostLink('delete', ['action' => 'delete', $id], __('Delete'), ['confirm' => __('Are you sure you want to delete # {0}?', $id), 'class' => 'icon-btn-delete']) ?>
PHP) ?></code></pre>
    <p><?= __('Thin outlined Material Symbols (weight 300), round highlight on hover (red for delete). 28px on desktop, 44px touch size below 992px. Names: fonts.google.com/icons.') ?></p>
    <h4><?= __('Other icons') ?></h4>
    <div class="tg-specimen tg-icons">
      <?php foreach ($icons as [$markup, $name, $usage]) : ?>
        <div class="tg-icon-item">
          <div class="tg-icon-box"><?= $markup ?></div>
          <code><?= h($name) ?></code>
          <span class="text-muted"><?= h($usage) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <ul class="tg-rules">
      <li><?= __('Table row actions use $this->my->iconLink() / iconPostLink() in the "actions" column, never hand-written material-icons links. The older gg-* links in a div.icon are kept on the pages not migrated yet.') ?></li>
      <li><?= __('Other icons come from Material Icons: $this->my->icon(name) or $this->my->symbol(name). An icon only button needs a title / aria-label.') ?></li>
    </ul>
  </section>

  <!-- ******************************************************************************************* -->
  <section class="tg-section" id="badges">
    <h3><?= h($sections['badges']) ?></h3>
    <h4><?= __('Invoice status (Bill::$statusHtml)') ?></h4>
    <div class="tg-specimen">
      <?php foreach ($statusBadges as [$color, $meaning]) : ?>
        <span class="badge bg-<?= $color ?>"><?= h($meaning) ?></span>
      <?php endforeach; ?>
    </div>
    <h4><?= __('Other badges') ?></h4>
    <div class="tg-specimen">
      <span class="badge bg-info"><?= __('Registered') ?></span>
      <span class="badge bg-secondary"><?= __('Location') ?></span>
      <span class="badge bg-light text-dark"><?= __('Attribute') ?></span>
    </div>
    <ul class="tg-rules">
      <li><?= __('Status colors follow the semantic colors: success = done / OK, danger = late / problem, warning = waiting / draft, info = in progress, dark = canceled.') ?></li>
      <li><?= __('The color of a status is computed in the entity (e.g. Bill::_getStatusHtml()), not in the template.') ?></li>
      <li><?= __('Attendance uses the same logic: present = success, absent = danger, excused = info, late = warning, none = grey (meetings.css).') ?></li>
    </ul>
  </section>

  <!-- ******************************************************************************************* -->
  <section class="tg-section" id="messages">
    <h3><?= h($sections['messages']) ?></h3>
    <h4><?= __('Flash messages (after an action)') ?></h4>
    <div class="message success"><?= __('The content has been saved.') ?></div>
    <div class="message error"><?= __('The content could not be saved.') ?></div>
    <div class="message warning"><?= __('Warning message') ?></div>
    <div class="message"><?= __('Information message') ?></div>
    <pre><code><?= h("\$this->Flash->success(__('The team has been saved.'));\n\$this->Flash->error(__('The team could not be saved. Please, try again.'));") ?></code></pre>
    <h4><?= __('Alerts (inside a page)') ?></h4>
    <div class="alert alert-info" role="alert"><?= __('Information or help for the current page.') ?></div>
    <div class="alert alert-warning" role="alert"><?= __('Something needs attention before continuing.') ?></div>
    <div class="alert alert-danger" role="alert"><?= __('Blocking problem.') ?></div>
    <div class="alert alert-success" role="alert"><?= __('Everything is in order.') ?></div>
  </section>

  <!-- ******************************************************************************************* -->
  <section class="tg-section" id="tables">
    <h3><?= h($sections['tables']) ?></h3>
    <div class="table-responsive">
      <table class="table table-striped table-hover table-sm">
        <thead>
          <tr>
            <th><?= __('Name') ?></th>
            <th><?= __('Fees') ?></th>
            <th><?= __('Status') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><a href="#tables">Junior</a></td>
            <td><?= $this->Number->currency(350, 'CHF') ?></td>
            <td><span class="badge bg-success"><?= __('Paid') ?></span></td>
            <td class="actions"><div class="icon"><a href="#tables"><i class="gg-pen"></i></a></div></td>
          </tr>
          <tr>
            <td><a href="#tables">Senior</a></td>
            <td><?= $this->Number->currency(420, 'CHF') ?></td>
            <td><span class="badge bg-danger"><?= __('Late') ?></span></td>
            <td class="actions"><div class="icon"><a href="#tables"><i class="gg-pen"></i></a></div></td>
          </tr>
        </tbody>
      </table>
    </div>
    <pre><code><?= h('<div class="table-responsive">' . "\n" . '  <table class="table table-striped table-hover table-sm"> ... <th class="actions"> ...') ?></code></pre>
    <ul class="tg-rules">
      <li><?= __('Always .table .table-striped .table-hover .table-sm, inside .table-responsive.') ?></li>
      <li><?= __('Wide tables on mobile: hide secondary columns (d-none d-md-table-cell) or switch to stacked cards.') ?></li>
      <li><?= __('The first column links to the view page, the last column "actions" holds the icon links.') ?></li>
    </ul>

    <h4><?= __('Sortable columns (webroot/js/table-sort.js)') ?></h4>
    <div class="table-responsive">
      <table class="table table-striped table-hover table-sm js-sortable">
        <thead>
          <tr>
            <th data-sort-type="text" aria-sort="ascending"><button type="button" class="table-sort-btn"><?= __('Name') ?></button></th>
            <th data-sort-type="date"><button type="button" class="table-sort-btn"><?= __('Date') ?></button></th>
            <th data-sort-type="number"><button type="button" class="table-sort-btn"><?= __('Status') ?></button></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Junior</td>
            <td data-sort-value="2026-09-01">01.09.2026</td>
            <td data-sort-value="5"><span class="badge bg-success"><?= __('Paid') ?></span></td>
          </tr>
          <tr>
            <td>Senior</td>
            <td data-sort-value="2026-08-15">15.08.2026</td>
            <td data-sort-value="1"><span class="badge bg-danger"><?= __('Late') ?></span></td>
          </tr>
        </tbody>
      </table>
    </div>
    <pre><code><?= h('<table class="... js-sortable">' . "\n" . '  <th data-sort-type="text|number|date"><button type="button" class="table-sort-btn">Name</button></th>' . "\n" . '  <td data-sort-value="2026-09-01">01.09.2026</td>') ?></code></pre>
    <ul class="tg-rules">
      <li><?= __('Sorted in the browser: add .js-sortable to the table, a .table-sort-btn button in each sortable header, and load table-sort.js.') ?></li>
      <li><?= __('Use data-sort-value when the shown text does not sort well (ISO date, status rank). Empty values always go last.') ?></li>
      <li><?= __('Mark the column the server already sorted with aria-sort="ascending".') ?></li>
    </ul>
  </section>

  <!-- ******************************************************************************************* -->
  <section class="tg-section" id="forms">
    <h3><?= h($sections['forms']) ?></h3>
    <div class="tg-specimen">
      <?= $this->Form->create(null, ['url' => '#forms']) ?>
      <fieldset>
        <legend><?= __('Edit Team') ?></legend>
        <?= $this->Form->control('tg_name', ['label' => __('Name')]) ?>
        <?= $this->Form->control('tg_site', ['label' => __('Location'), 'options' => ['Lausanne', 'Genève']]) ?>
        <?= $this->Form->control('tg_active', ['type' => 'checkbox', 'label' => __('Active')]) ?>
      </fieldset>
      <div class="btn-bar">
        <button type="button" class="btn btn-save"><?= __('Submit') ?></button>
        <a href="#forms" class="btn btn-nav"><?= __('Cancel') ?></a>
      </div>
      <?= $this->Form->end() ?>
    </div>
    <ul class="tg-rules">
      <li><?= __('Use $this->Form->control(): BootstrapUI renders label, input and validation errors.') ?></li>
      <li><?= __('Checkboxes and their label stay on the same line (fix with classes, never inline style).') ?></li>
      <li><?= h(__('Group fields in a <fieldset> with a <legend>; one btn-save submit at the end.')) ?></li>
    </ul>
  </section>

  <!-- ******************************************************************************************* -->
  <section class="tg-section" id="layout">
    <h3><?= h($sections['layout']) ?></h3>
    <ul class="tg-rules">
      <li><?= h(__('Each page content is in a white card: <div class="[controller] [action] content">.')) ?></li>
      <li><?= __('Wide pages (dashboard, big lists) set $this->set(\'wide\', \'100\') in the controller.') ?></li>
      <li><?= __('Use the Bootstrap grid (row / col-12 col-md-*) and utilities (d-none d-md-block, gap, mb-*), never fixed widths.') ?></li>
      <li><?= __('Test at phone width (375px): no horizontal scroll, buttons grow automatically to touch size (44px, 40px for .btn-sm) below 992px, do not add page specific button heights.') ?></li>
      <li><?= h(__('Page specific CSS goes in a domain file (webroot/css/<domain>.css) loaded with $this->Html->css(\'<domain>\', [\'block\' => true]).')) ?></li>
      <li><?= __('JavaScript toggles classes, it does not set styles; prefer vanilla JS over jQuery.') ?></li>
    </ul>
  </section>

  <!-- ******************************************************************************************* -->
  <section class="tg-section" id="review">
    <h3><?= h($sections['review']) ?></h3>
    <p><?= __('Patterns removed from the website when it was migrated to this guide. Do not reintroduce them:') ?></p>
    <ul class="tg-rules">
      <li><span class="tg-dont"><?= __('Don\'t') ?></span> <?= __('btn-primary, btn-secondary, btn-success, btn-danger, btn-warning, btn-info or btn-outline-* on buttons.') ?>
        <span class="tg-do"><?= __('Do') ?></span> <?= __('the action class of the table above.') ?></li>
      <li><span class="tg-dont"><?= __('Don\'t') ?></span> <?= __('Form->button() or Form->submit() without class (grey btn-secondary).') ?>
        <span class="tg-do"><?= __('Do') ?></span> <?= __('[\'class\' => \'btn btn-save\'].') ?></li>
      <li><span class="tg-dont"><?= __('Don\'t') ?></span> <?= __('the Milligram classes "button" and "button-outline".') ?>
        <span class="tg-do"><?= __('Do') ?></span> <?= __('"btn btn-add btn-sm float-end" for a New ... link.') ?></li>
      <li><span class="tg-dont"><?= __('Don\'t') ?></span> <?= __('style="color: ..." to fix the text of a button, or set styles from JavaScript.') ?>
        <span class="tg-do"><?= __('Do') ?></span> <?= __('toggle a class (pressed, hidden attribute).') ?></li>
      <li><span class="tg-dont"><?= __('Don\'t') ?></span> <?= __('hex colors of the palette in domain CSS files.') ?>
        <span class="tg-do"><?= __('Do') ?></span> <?= __('var(--aa-*).') ?></li>
    </ul>
    <h4><?= __('Still open') ?></h4>
    <ul class="tg-rules">
      <li><?= __('Some inline styles remain in templates (widths, member photos, PDF and email templates).') ?></li>
    </ul>
  </section>
</div>
