<h1><?= $user['id'] ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur' ?></h1>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= Security::e($err) ?></div>
<?php endforeach; ?>

<form method="post" class="admin-form">
    <?= Security::csrfField() ?>
    <label>Nom
        <input type="text" name="name" value="<?= Security::e($user['name']) ?>" required>
    </label>
    <label>Email
        <input type="email" name="email" value="<?= Security::e($user['email']) ?>" required>
    </label>
    <label>Mot de passe <?= $user['id'] ? '(laisser vide pour ne pas changer)' : '' ?>
        <input type="password" name="password" minlength="8" <?= $user['id'] ? '' : 'required' ?>>
    </label>
    <label>Rôle
        <select name="role">
            <?php foreach (['admin' => 'Administrateur', 'instructor' => 'Formateur', 'student' => 'Étudiant'] as $value => $label): ?>
                <option value="<?= $value ?>" <?= $user['role'] === $value ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Statut
        <select name="status">
            <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Actif</option>
            <option value="suspended" <?= $user['status'] === 'suspended' ? 'selected' : '' ?>>Suspendu</option>
        </select>
    </label>
    <button class="btn" type="submit">Enregistrer</button>
    <a class="btn-secondary" href="<?= adminUrl('users') ?>">Annuler</a>
</form>
