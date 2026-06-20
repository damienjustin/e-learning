<h1>Tous les cours</h1>
<div class="grid">
    <?php foreach ($courses as $course): ?>
        <article class="card">
            <h3><a href="/course/<?= Security::e($course['slug']) ?>"><?= Security::e($course['title']) ?></a></h3>
            <p><?= Security::e($course['summary']) ?></p>
            <?php if ((float) $course['price'] > 0): ?>
                <span class="price"><?= number_format((float) $course['price'], 2) ?> &euro;</span>
            <?php else: ?>
                <span class="price">Gratuit</span>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
    <?php if (!$courses): ?>
        <p>Aucun cours publié pour le moment.</p>
    <?php endif; ?>
</div>
