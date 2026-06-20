<h1><?= $group['id'] ? 'Modifier le groupe' : 'Nouveau groupe' ?></h1>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= Security::e($err) ?></div>
<?php endforeach; ?>

<form method="post" class="admin-form">
    <?= Security::csrfField() ?>
    <input type="hidden" name="form" value="group">
    <label>Nom du groupe
        <input type="text" name="name" value="<?= Security::e($group['name']) ?>" required>
    </label>
    <button class="btn" type="submit">Enregistrer</button>
    <a class="btn-secondary" href="<?= adminUrl('groups') ?>">Retour</a>
</form>

<?php if ($group['id']): ?>
    <h2>Membres</h2>
    <ul class="lesson-admin-list">
        <?php foreach ($members as $member): ?>
            <li>
                <?= Security::e($member['name']) ?> (<?= Security::e($member['email']) ?>)
                <form method="post" action="<?= adminUrl('groups', ['action' => 'remove_member']) ?>" style="display:inline">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int) $group['id'] ?>">
                    <input type="hidden" name="user_id" value="<?= (int) $member['id'] ?>">
                    <button class="btn-link" type="submit">retirer</button>
                </form>
            </li>
        <?php endforeach; ?>
        <?php if (!$members): ?><li class="muted">Aucun membre</li><?php endif; ?>
    </ul>
    <form method="post" action="<?= adminUrl('groups', ['action' => 'add_member']) ?>" class="inline-form">
        <?= Security::csrfField() ?>
        <input type="hidden" name="id" value="<?= (int) $group['id'] ?>">
        <select name="user_id" required>
            <option value="">Choisir un utilisateur inscrit...</option>
            <?php foreach ($candidates as $c): ?>
                <option value="<?= (int) $c['id'] ?>"><?= Security::e($c['name']) ?> (<?= Security::e($c['email']) ?>)</option>
            <?php endforeach; ?>
        </select>
        <button class="btn-secondary" type="submit">Ajouter le membre</button>
    </form>
    <?php if (!$candidates): ?><p class="muted">Aucun utilisateur inscrit disponible à ajouter.</p><?php endif; ?>

    <h2>Cours accessibles via ce groupe</h2>
    <ul class="lesson-admin-list">
        <?php foreach ($courses as $course): ?>
            <li><a href="<?= adminUrl('courses', ['action' => 'edit', 'id' => $course['id']]) ?>"><?= Security::e($course['title']) ?></a></li>
        <?php endforeach; ?>
        <?php if (!$courses): ?><li class="muted">Aucun cours restreint à ce groupe pour le moment.</li><?php endif; ?>
    </ul>
    <p class="muted">Pour donner accès à un cours, ouvrez le cours, mettez son accès en "Restreint" et ajoutez ce groupe.</p>
<?php else: ?>
    <p class="muted">Enregistrez le groupe pour gérer ses membres.</p>
<?php endif; ?>
