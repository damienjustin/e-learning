<div class="auth-form">
    <h1>Inscription</h1>
    <?php if ($error): ?><div class="alert alert-error"><?= Security::e($error) ?></div><?php endif; ?>
    <form method="post">
        <?= Security::csrfField() ?>
        <label>Nom
            <input type="text" name="name" required autofocus>
        </label>
        <label>Email
            <input type="email" name="email" required>
        </label>
        <label>Mot de passe (8 caractères minimum)
            <input type="password" name="password" required minlength="8">
        </label>
        <button class="btn" type="submit">Créer mon compte</button>
    </form>
    <p>Déjà inscrit ? <a href="/login">Connectez-vous</a></p>
</div>
