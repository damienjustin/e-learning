<div class="page-head">
    <h1>Progression — <?= Security::e($course['title']) ?></h1>
    <a class="btn-secondary" href="<?= adminUrl('courses', ['action' => 'export_enrollments', 'id' => $course['id']]) ?>">Exporter CSV</a>
    <a class="btn-secondary" href="<?= adminUrl('courses', ['action' => 'edit', 'id' => $course['id']]) ?>">Retour au cours</a>
</div>

<table class="admin-table">
    <thead><tr><th>Étudiant</th><th>Email</th><th>Inscrit le</th><th>Progression</th></tr></thead>
    <tbody>
    <?php foreach ($students as $s): ?>
        <?php $rate = $totalLessons > 0 ? round(((int) $s['completed_lessons'] / $totalLessons) * 100) : 0; ?>
        <tr>
            <td><?= Security::e($s['name']) ?></td>
            <td><?= Security::e($s['email']) ?></td>
            <td><?= Security::e(date('d/m/Y', strtotime($s['enrolled_at']))) ?></td>
            <td>
                <div class="progress-bar"><div class="progress-bar-fill" style="width: <?= $rate ?>%"></div></div>
                <?= (int) $s['completed_lessons'] ?>/<?= $totalLessons ?> leçons (<?= $rate ?>%)
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$students): ?><tr><td colspan="4">Aucun étudiant inscrit.</td></tr><?php endif; ?>
    </tbody>
</table>
