<p>Bonjour,</p>

<p>
    Nous avons constaté que certaines de vos factures sont actuellement en retard de paiement.
</p>

<p>
    Pour assurer le bon fonctionnement du club, il est important que celles-ci soient réglées dans les délais.
</p>

<p>
    Vous pouvez consulter le détail de vos factures via le lien suivant :
</p>

<p>
    <a href="<?= $url ?>" class="button">Voir mes factures</a>
</p>

<p>
    Si vous avez égaré une facture, le PDF est disponible en téléchargement à la même adresse.
</p>

<p><strong>Factures en attente de paiement :</strong></p>
<ul>
    <?php foreach ($lateBills as $bill): ?>
        <li><?= h($bill->label) ?> — <?= h($bill->amount) ?> CHF</li>
    <?php endforeach; ?>
</ul>

<p>Merci d'avance pour votre réactivité.</p>

<p>
    Salutations,<br>
    <strong><?= $site->sender ?></strong><br>
    <?= $site->sender_phone ?>
</p>

<p class="footer">
    *Ce message est un rappel automatique envoyé chaque mois. N’hésitez pas à me contacter directement pour toute
    question ou information complémentaire.*
</p>