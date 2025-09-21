Bonjour,

Nous avons constaté que certaines de vos factures sont actuellement en retard de paiement.

Pour assurer le bon fonctionnement du club, il est important que celles-ci soient réglées dans les délais.

Vous pouvez consulter le détail de vos factures via le lien suivant :  
<?= $url ?>


Si vous avez égaré une facture, le PDF est disponible en téléchargement à la même adresse.

Factures en attente de paiement :
<?php foreach ($lateBills as $bill): ?>
    - <?= h($bill->label) ?> — <?= h($bill->amount) ?> CHF
<?php endforeach; ?>


Merci d'avance pour votre réactivité.

Salutations,  
<?= $site->sender ?>  
<?= $site->sender_phone ?>  

*Ce message est un rappel automatique envoyé chaque mois. N’hésitez pas à me contacter directement pour toute question ou information complémentaire.*
