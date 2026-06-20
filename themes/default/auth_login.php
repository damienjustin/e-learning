<div class="auth-form">
    <h1>Connexion</h1>
    <?php if ($error): ?><div class="alert alert-error"><?= Security::e($error) ?></div><?php endif; ?>
    <form method="post">
        <?= Security::csrfField() ?>
        <label>Email
            <input type="email" name="email" required autofocus>
        </label>
        <label>Mot de passe
            <input type="password" name="password" required>
        </label>
        <button class="btn" type="submit">Se connecter</button>
    </form>
    <p>Pas encore de compte ? <a href="/register">Inscrivez-vous</a></p>
</div>
