Bonjour,
Vous pouvez trouver ci-joint la facture de <?= $bill->member->FullName ?>.

Motif: <?= $bill->label ?>

N° de facture: <?= $bill->Reference ?>

amount: <?= $this->Number->currency($bill->amount, 'CHF') ?>

Délais de paiement:  <?= $bill->due_date ?>


Consultez vos informations et l'état de vos factures avec ce lien:
<?= $url ?>


Salutations
<?= $bill->site->sender ?>
<?= $bill->site->sender_phone ?>