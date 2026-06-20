<article>
    <h1><?= Security::e($course['title']) ?></h1>
    <p class="muted">Par <?= Security::e($course['instructor_name']) ?></p>
    <?php $descBlocks = Blocks::decode($course['description_blocks'] ?? null); ?>
    <?php if ($descBlocks): ?>
        <div class="prose"><?= Blocks::render($descBlocks) ?></div>
    <?php else: ?>
        <p><?= nl2br(Security::e($course['description'])) ?></p>
    <?php endif; ?>

    <?php if (Auth::check() && !$enrolled): ?>
        <form method="post">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="enroll">
            <button class="btn" type="submit">S'inscrire à ce cours</button>
        </form>
    <?php elseif (!Auth::check()): ?>
        <a class="btn" href="/login">Connectez-vous pour vous inscrire</a>
    <?php endif; ?>

    <h2>Contenu du cours</h2>
    <?php foreach ($modules as $module): ?>
        <div class="module">
            <h3><?= Security::e($module['title']) ?></h3>
            <?php $moduleBlocks = Blocks::decode($module['description_blocks'] ?? null); ?>
            <?php if ($moduleBlocks): ?>
                <div class="prose"><?= Blocks::render($moduleBlocks) ?></div>
            <?php endif; ?>
            <ul class="lesson-list">
                <?php foreach ($module['lessons'] as $lesson): ?>
                    <li>
                        <?php if ($enrolled || Auth::hasRole('admin', 'instructor')): ?>
                            <a href="/learn/<?= Security::e($course['slug']) ?>/<?= Security::e($lesson['slug']) ?>">
                                <?= Security::e($lesson['title']) ?>
                            </a>
                        <?php else: ?>
                            <?= Security::e($lesson['title']) ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
                <?php foreach ($module['quizzes'] as $quiz): ?>
                    <li class="quiz-item">
                        <?php if ($enrolled || Auth::hasRole('admin', 'instructor')): ?>
                            <a href="/quiz/<?= (int) $quiz['id'] ?>">Quiz : <?= Security::e($quiz['title']) ?></a>
                        <?php else: ?>
                            Quiz : <?= Security::e($quiz['title']) ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>
</article>
