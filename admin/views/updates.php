<h1>Mises à jour</h1>
<p class="muted">Version installée : <strong><?= Security::e($installedVersion) ?></strong></p>

<?php if ($updateError): ?>
    <div class="alert alert-error"><?= Security::e($updateError) ?></div>
<?php endif; ?>

<?php if ($updateResult): ?>
    <div class="alert alert-success">
        Mise à jour appliquée avec succès.
        <?php if ($updateResult['migrations']): ?>
            Migrations appliquées : <?= Security::e(implode(', ', $updateResult['migrations'])) ?>.
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($checkError): ?>
    <div class="alert alert-error">Impossible de vérifier les mises à jour : <?= Security::e($checkError) ?></div>
<?php elseif ($release): ?>
    <?php $hasUpdate = Updater::isNewer($release['version'], $installedVersion); ?>
    <div class="admin-form">
        <?php if ($hasUpdate): ?>
            <p>Une nouvelle version est disponible : <strong><?= Security::e($release['version']) ?></strong></p>
            <?php if ($release['changelog']): ?>
                <h2>Notes de version</h2>
                <p style="white-space:pre-wrap;"><?= Security::e($release['changelog']) ?></p>
            <?php endif; ?>
            <form method="post" onsubmit="return confirm('Mettre à jour le cœur du CMS maintenant ? Vos contenus, votre configuration et vos thèmes personnalisés ne seront pas modifiés.');">
                <?= Security::csrfField() ?>
                <input type="hidden" name="do" value="update">
                <button class="btn" type="submit">Mettre à jour vers <?= Security::e($release['version']) ?></button>
            </form>
        <?php else: ?>
            <p>✅ Vous utilisez déjà la dernière version disponible.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<p class="muted" style="margin-top:24px;">
    Seuls les dossiers du cœur (<code>core/</code>, <code>admin/</code>, <code>includes/</code>, <code>install/</code>, <code>themes/default/</code>) sont remplacés.
    Votre configuration (<code>config/config.php</code>), vos fichiers (<code>uploads/</code>) et vos thèmes personnalisés ne sont jamais modifiés.
    Les mises à jour de base de données n'ajoutent ou ne modifient que des structures, sans jamais supprimer vos données.
</p>
