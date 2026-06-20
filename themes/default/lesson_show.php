<div class="lesson-layout">
    <aside class="lesson-nav">
        <a href="/course/<?= Security::e($course['slug']) ?>">&larr; Retour au cours</a>
        <?php foreach ($modules as $module): ?>
            <h4><?= Security::e($module['title']) ?></h4>
            <ul>
                <?php foreach ($module['lessons'] as $item): ?>
                    <li class="<?= $item['id'] === $lesson['id'] ? 'active' : '' ?>">
                        <a href="/learn/<?= Security::e($course['slug']) ?>/<?= Security::e($item['slug']) ?>"><?= Security::e($item['title']) ?></a>
                    </li>
                <?php endforeach; ?>
                <?php foreach ($module['quizzes'] as $quiz): ?>
                    <li><a href="/quiz/<?= (int) $quiz['id'] ?>">Quiz : <?= Security::e($quiz['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </aside>

    <article class="lesson-content">
        <h1><?= Security::e($lesson['title']) ?></h1>

        <?php if ($lesson['content_type'] === 'video' && $lesson['video_url']): ?>
            <div class="video-wrap">
                <iframe src="<?= Security::e($lesson['video_url']) ?>" allowfullscreen></iframe>
            </div>
        <?php endif; ?>

        <?php $lessonBlocks = Blocks::decode($lesson['content_blocks'] ?? null); ?>
        <?php if ($lessonBlocks): ?>
            <div class="prose"><?= Blocks::render($lessonBlocks) ?></div>
        <?php else: ?>
            <div class="prose"><?= $lesson['content'] ?? '' ?></div>
        <?php endif; ?>

        <form method="post">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="complete">
            <button class="btn" type="submit" <?= $completed ? 'disabled' : '' ?>>
                <?= $completed ? 'Leçon terminée ✓' : 'Marquer comme terminée' ?>
            </button>
        </form>
    </article>
</div>
