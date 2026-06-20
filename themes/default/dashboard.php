<h1>Bonjour, <?= Security::e($user['name']) ?></h1>

<h2>Mes cours</h2>
<div class="grid">
    <?php foreach ($enrollments as $course): ?>
        <article class="card">
            <h3><a href="/course/<?= Security::e($course['slug']) ?>"><?= Security::e($course['title']) ?></a></h3>
            <p><?= $course['completed_at'] ? 'Terminé' : 'En cours' ?></p>
        </article>
    <?php endforeach; ?>
    <?php if (!$enrollments): ?>
        <p>Vous n'êtes inscrit à aucun cours. <a href="/courses">Découvrir les cours</a>.</p>
    <?php endif; ?>
</div>
