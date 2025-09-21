<main class="main">
  <div class="container">
    <div class="content">

      <?php if (!empty($contents)) : ?>
        <div class="row">
          <h4><?= __('Teams') ?></h4>
        </div>
        <div class="row">
          <div class="col">

            <?php foreach ($teams as $key => $team) : ?>
              <?php if ($key % 2 == 0) : ?>
                <h5><?= h($team->name) ?></h5> 
                <ul>
                  <?= __('Fees: {0}CHF, Size: {1}', $team->membership_fee, $team->nbMembers) ?>
                  <br>
                  <?= h($team->description) ?>
                </ul>
              <?php endif; ?>
            <?php endforeach; ?>              

          </div>
          <div class="col">
            <?php foreach ($teams as $key => $team) : ?>
              <?php if ($key % 2 == 1) : ?>
                <h5><?= h($team->name) ?></h5> 
                <ul>
                  <?= __('Fees: {0}CHF, Size: {1}', $team->membership_fee, $team->nbMembers) ?>
                  <br>
                  <?= h($team->description) ?>
                </ul>
              <?php endif; ?>
            <?php endforeach; ?>             
          </div>
        </div>
      <?php endif; ?>
      <hr class="animated fadeInLeft" style="visibility: visible;">
      <?php if (!empty($contents)) : ?>
        <div class="row">
          <div class="col">
            <ul>
              <?php foreach ($contents as $key => $content) : ?>
                <?php
                if ($key % 2 == 0)
                  echo $content['text'];
                ?>
                <hr class="animated fadeInLeft" style="visibility: visible;">
              <?php endforeach; ?>              
            </ul>
          </div>
          <div class="col">
            <ul>
              <?php foreach ($contents as $key => $content) : ?>
                <?php
                if ($key % 2 == 1)
                  echo $content['text'];
                ?>
                <hr class="animated fadeInLeft" style="visibility: visible;">
              <?php endforeach; ?>              
            </ul>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>
