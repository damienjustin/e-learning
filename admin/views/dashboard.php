<h1>Tableau de bord</h1>
<div class="stat-grid">
    <div class="stat-card"><span class="stat-number"><?= $counts['courses'] ?></span>Cours</div>
    <?php if ($isAdmin): ?>
        <div class="stat-card"><span class="stat-number"><?= $counts['users'] ?></span>Utilisateurs</div>
    <?php endif; ?>
    <div class="stat-card"><span class="stat-number"><?= $counts['enrollments'] ?></span>Inscriptions</div>
</div>

<div class="dashboard-columns">
    <div>
        <h2>Cours les plus populaires</h2>
        <table class="admin-table">
            <thead><tr><th>Cours</th><th>Inscrits</th></tr></thead>
            <tbody>
            <?php foreach ($popularCourses as $c): ?>
                <tr>
                    <td><a href="<?= adminUrl('courses', ['action' => 'edit', 'id' => $c['id']]) ?>"><?= Security::e($c['title']) ?></a></td>
                    <td><?= (int) $c['enrollment_count'] ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$popularCourses): ?><tr><td colspan="2">Aucun cours.</td></tr><?php endif; ?>
            </tbody>
        </table>

        <h2>Taux de complétion</h2>
        <table class="admin-table">
            <thead><tr><th>Cours</th><th>Complétion</th></tr></thead>
            <tbody>
            <?php foreach ($completionRates as $c): ?>
                <tr>
                    <td><?= Security::e($c['title']) ?></td>
                    <td><?= $c['rate'] === null ? '—' : $c['rate'] . '%' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$completionRates): ?><tr><td colspan="2">Aucune donnée.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <div>
        <h2>Dernières inscriptions</h2>
        <table class="admin-table">
            <thead><tr><th>Utilisateur</th><th>Cours</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($recentEnrollments as $e): ?>
                <tr>
                    <td><?= Security::e($e['user_name']) ?></td>
                    <td><?= Security::e($e['course_title']) ?></td>
                    <td><?= Security::e(date('d/m/Y', strtotime($e['enrolled_at']))) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$recentEnrollments): ?><tr><td colspan="3">Aucune inscription récente.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
