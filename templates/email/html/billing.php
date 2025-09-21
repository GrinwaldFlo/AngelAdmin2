<p>Bonjour,</p>
<p>Vous pouvez trouver ci-joint la facture de <?= $bill->member->FullName ?>.</p>
<br>
<p>Motif: <b><?= $bill->label ?></p></b>
<p>N° de facture: <b><?= $bill->Reference ?></b></p>
<p>amount: <b><?= $this->Number->currency($bill->amount, 'CHF') ?></b></p>
<p>Délais de paiement:  <b><?= $bill->due_date ?></b></p>
<p>Consultez vos informations et l'état de vos factures avec ce lien:</p>
<?= '<p><a href="'.$url.'">'.$url.'</a></p>'; ?>
<br>
<p>Salutations</p>
<p><?= $bill->site->sender ?></p>
<p><?= $bill->site->sender_phone ?></p>
