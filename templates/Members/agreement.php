<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Data[]|\Cake\Collection\CollectionInterface $agreements
 */

?>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@2.3.2/dist/signature_pad.min.js"></script>

<style>
    .signature-pad {
        border: 2px solid #000;
        background-color: rgba(240, 240, 240, 255);
    }

    /* Desktop - horizontal signature */
    @media (min-width: 768px) {
        .signature-pad {
            width: 500px;
            height: 300px;
        }
    }

    /* Mobile/Tablet - vertical signature */
    @media (max-width: 767px) {
        .signature-pad {
            width: 300px;
            height: 500px;
        }
    }

</style>

<div class="col-12">
    <div class="alert alert-info" role="alert">
        <h4><?= __('Registration for the year {0}-{1}', $config['year'], $config['year'] + 1) ?></h4>
        <i>
            <?= __('Register for a new cheerleading season. Read carefully our club regulations. Given the number of members in the club, we are obliged to have a certain number of rules so that everything goes well. This way, you will be aware of all the important points for this year.') ?>
        </i>
    </div>
</div>

<div class="row">
    <div class="col">
        <div class="members view content">
            <div class="tags float-end" style="width: 100px">
                <?= $this->BackButton($refer, true) ?>
            </div>
            <h1><?= __x('Title for signing the membership for this season', 'Registration agreement') ?></h1>
            <h2><?= h(($member->coach ? __('Coach') . ' ' : '') . $member->fullName) ?></h2>
            <h4><?= h($member->teamString) ?></h4>
            <div class="row">
                <div class="col">
                    <?= $this->Form->create() ?>
                    <?php foreach ($agreements as $agreement): ?>
                        <div>
                            <?= $this->Form->control('A' . $agreement->param, ['type' => 'checkbox', 'label' => $agreement->value, 'required' => true, 'escape' => false]); ?>
                        </div>
                        <hr />
                    <?php endforeach; ?>
                    <div class="wrapper">
                        <canvas id="signature-padMember" class="signature-pad"></canvas>
                    </div>
                    <div>
                        <button type="button" id="clearMember"><?= __('Clear') ?></button>
                    </div>
                    <div <?= !$member->IsAdult ? '' : 'style="display:none"' ?>>
                        <div class="wrapper">
                            <canvas id="signature-padParent" class="signature-pad"></canvas>
                        </div>
                        <div>
                            <button type="button" id="clearParent"><?= __('Clear') ?></button>
                        </div>
                    </div>
                    <?= $this->Form->control('signatureMember', ['value' => '', 'type' => 'hidden']); ?>
                    <?= $this->Form->control('signatureParent', ['value' => '', 'type' => 'hidden']); ?>
                    <br />
                    <?= $this->Form->button(__('Submit')) ?>
                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    // Function to check if device is mobile/tablet
    function isMobileDevice() {
        return window.innerWidth <= 767;
    }

    // Function to get canvas dimensions based on device
    function getCanvasDimensions() {
        if (isMobileDevice()) {
            return { width: 300, height: 500 };
        } else {
            return { width: 500, height: 300 };
        }
    }

    // Function to setup canvas with responsive dimensions
    function setupCanvas(canvasId) {
        const canvas = document.getElementById(canvasId);
        const dimensions = getCanvasDimensions();
        canvas.width = dimensions.width;
        canvas.height = dimensions.height;
        return canvas;
    }

    // Setup member signature pad
    const memberCanvas = setupCanvas('signature-padMember');
    var signaturePadMember = new SignaturePad(memberCanvas, {
      backgroundColor: 'rgba(240, 240, 240, 255)',
      penColor: 'rgb(0, 0, 0)',
      onEnd: saveMember
    });

    // Setup parent signature pad
    const parentCanvas = setupCanvas('signature-padParent');
    var signaturePadParent = new SignaturePad(parentCanvas, {
      backgroundColor: 'rgba(240, 240, 240, 255)',
      penColor: 'rgb(0, 0, 0)',
      onEnd: saveParent
    });

    var cancelButtonMember = document.getElementById('clearMember');
    var cancelButtonParent = document.getElementById('clearParent');

    cancelButtonMember.addEventListener('click', function (event) {
      clearCanvasMember();
    });

    cancelButtonParent.addEventListener('click', function (event) {
      clearCanvasParent();
    });

    function clearCanvasMember() {
      signaturePadMember.clear();

      const canvas = document.getElementById('signature-padMember');
      const ctx = canvas.getContext('2d');
      const W = canvas.width;
      const H = canvas.height;

      ctx.fillStyle = 'black';
      ctx.fillRect(0, 0, W, H);
      ctx.fillStyle = 'white';
      ctx.fillRect(2, 2, W - 4, H - 4);

      ctx.beginPath();

      // Adjust text positioning based on orientation
      if (isMobileDevice()) {
        ctx.moveTo(W - 15, 15);
        ctx.lineTo(W - 15, H - 15);
        ctx.stroke();
        ctx.save();

        // Vertical layout - rotate text
        ctx.rotate(-Math.PI / 2);
        ctx.translate(0, 0);
        ctx.font = "20px Arial";
        ctx.fillStyle = 'rgb(200, 200, 200)';
        ctx.textAlign = "center";
        ctx.fillText("<?= __('Signature of') . ' ' . $member->FullName ?>", -H / 2, 40);
        ctx.fillText("<?= __x('{} are years of the season', 'Season {0}-{1}', $config['year'], $config['year'] + 1) ?>", -H / 2, 80);
      } else {
        ctx.moveTo(15, H - 15);
        ctx.lineTo(W - 15, H - 15);
        ctx.stroke();
        ctx.save();

        // Horizontal layout - normal text
        ctx.font = "20px Arial";
        ctx.fillStyle = 'rgb(200, 200, 200)';
        ctx.textAlign = "center";
        ctx.fillText("<?= __('Signature of') . ' ' . $member->FullName ?>", W / 2, H / 2 - 10);
        ctx.fillText("<?= __x('{} are years of the season', 'Season {0}-{1}', $config['year'], $config['year'] + 1) ?>", W / 2, H / 2 + 30);
      }

      ctx.restore();
    }

    function clearCanvasParent() {
      signaturePadParent.clear();

      const canvas = document.getElementById('signature-padParent');
      const ctx = canvas.getContext('2d');
      const W = canvas.width;
      const H = canvas.height;

      ctx.fillStyle = 'black';
      ctx.fillRect(0, 0, W, H);
      ctx.fillStyle = 'white';
      ctx.fillRect(2, 2, W - 4, H - 4);

      ctx.beginPath();

      // Adjust text positioning based on orientation
      if (isMobileDevice()) {
          ctx.moveTo(W - 15, 15);
          ctx.lineTo(W - 15, H - 15);
          ctx.stroke();

          ctx.save();

        // Vertical layout - rotate text
        ctx.rotate(-Math.PI / 2);
        ctx.translate(0, 0);
        ctx.font = "20px Arial";
        ctx.fillStyle = 'rgb(200, 200, 200)';
        ctx.textAlign = "center";
        ctx.fillText("<?= __('Signature of legal guardian/parent') ?>", -H / 2, 40);
        ctx.fillText("<?= __('of') . ' ' . $member->FullName ?>", -H / 2, 80);
        ctx.fillText("<?= __x('{} are years of the season', 'Season {0}-{1}', $config['year'], $config['year'] + 1) ?>", -H / 2, 120);
      } else {
        ctx.moveTo(15, H - 15);
        ctx.lineTo(W - 15, H - 15);
        ctx.stroke();
        ctx.save();

        // Horizontal layout - normal text
        ctx.font = "20px Arial";
        ctx.fillStyle = 'rgb(200, 200, 200)';
        ctx.textAlign = "center";
        ctx.fillText("<?= __('Signature of legal guardian/parent') ?>", W / 2, H / 2 - 20);
        ctx.fillText("<?= __('of') . ' ' . $member->FullName ?>", W / 2, H / 2 + 10);
        ctx.fillText("<?= __x('{} are years of the season', 'Season {0}-{1}', $config['year'], $config['year'] + 1) ?>", W / 2, H / 2 + 40);
      }

      ctx.restore();
    }

    function saveMember() {
      const canvas = document.getElementById('signature-padMember');
      $("#signaturemember").val(canvas.toDataURL());
    }

    function saveParent() {
      const canvas = document.getElementById('signature-padParent');
      $("#signatureparent").val(canvas.toDataURL());
    }

    // Handle window resize to adjust canvas if needed
    window.addEventListener('resize', function() {
      // Redraw canvases on orientation change
      setTimeout(function() {
        const newDimensions = getCanvasDimensions();

        // Update member canvas
        const memberCanvas = document.getElementById('signature-padMember');
        if (memberCanvas.width !== newDimensions.width || memberCanvas.height !== newDimensions.height) {
          memberCanvas.width = newDimensions.width;
          memberCanvas.height = newDimensions.height;
          signaturePadMember.clear();
          clearCanvasMember();
        }

        // Update parent canvas
        const parentCanvas = document.getElementById('signature-padParent');
        if (parentCanvas.width !== newDimensions.width || parentCanvas.height !== newDimensions.height) {
          parentCanvas.width = newDimensions.width;
          parentCanvas.height = newDimensions.height;
          signaturePadParent.clear();
          clearCanvasParent();
        }
      }, 100);
    });

    //***
    $(document).ready(function () {
      clearCanvasMember();
      clearCanvasParent();

      signaturePadMember.onEnd = function () {
        saveMember();
      };
      signaturePadParent.onEnd = function () {
        saveParent();
      };
    });
</script>
