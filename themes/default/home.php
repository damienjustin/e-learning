<section class="hero">
    <h1>Apprenez à votre rythme</h1>
    <p>Des cours créés par des formateurs passionnés, des quiz pour valider vos acquis.</p>
    <a class="btn" href="/courses">Voir les cours</a>
</section>

<section>
    <h2>Derniers cours</h2>
    <div class="grid">
        <?php foreach ($courses as $course): ?>
            <article class="card">
                <h3><a href="/course/<?= Security::e($course['slug']) ?>"><?= Security::e($course['title']) ?></a></h3>
                <p><?= Security::e($course['summary']) ?></p>
            </article>
        <?php endforeach; ?>
        <?php if (!$courses): ?>
            <p>Aucun cours publié pour le moment.</p>
        <?php endif; ?>
    </div>
</section>
