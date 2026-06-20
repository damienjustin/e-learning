<h1><?= $course['id'] ? 'Modifier le cours' : 'Nouveau cours' ?></h1>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= Security::e($err) ?></div>
<?php endforeach; ?>

<form method="post" class="admin-form admin-form--wide">
    <?= Security::csrfField() ?>
    <label>Titre
        <input type="text" name="title" value="<?= Security::e($course['title']) ?>" required>
    </label>
    <label>Slug (URL, laisser vide pour générer automatiquement)
        <input type="text" name="slug" value="<?= Security::e($course['slug']) ?>">
    </label>
    <label>Résumé
        <textarea name="summary" rows="2"><?= Security::e($course['summary']) ?></textarea>
    </label>
    <label>Description complète</label>
    <?php
        $builderName = 'description_blocks';
        $builderBlocksJson = $course['description_blocks'] ?? '[]';
        require __DIR__ . '/partials/block_builder.php';
    ?>
    <label>Prix (&euro;)
        <input type="number" step="0.01" min="0" name="price" value="<?= Security::e((string) $course['price']) ?>">
    </label>
    <label>Statut
        <select name="status">
            <?php foreach (['draft' => 'Brouillon', 'published' => 'Publié', 'archived' => 'Archivé'] as $value => $label): ?>
                <option value="<?= $value ?>" <?= $course['status'] === $value ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button class="btn" type="submit">Enregistrer</button>
</form>

<?php if ($course['id']): ?>
    <h2>Modules &amp; leçons</h2>
    <a class="btn-secondary" href="<?= adminUrl('modules', ['action' => 'create', 'course_id' => $course['id']]) ?>">+ Ajouter un module</a>

    <?php foreach ($modules as $module): ?>
        <div class="module-block">
            <div class="page-head">
                <h3><?= Security::e($module['title']) ?></h3>
                <div>
                    <a class="btn-secondary" href="<?= adminUrl('modules', ['action' => 'edit', 'id' => $module['id'], 'course_id' => $course['id']]) ?>">Modifier</a>
                    <form method="post" action="<?= adminUrl('modules', ['action' => 'delete', 'course_id' => $course['id']]) ?>" style="display:inline" onsubmit="return confirm('Supprimer ce module et son contenu ?');">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int) $module['id'] ?>">
                        <button class="btn-danger" type="submit">Supprimer</button>
                    </form>
                </div>
            </div>

            <?php if ($module['lessons']): ?>
                <div class="reorder-list" data-reorder data-module-id="<?= (int) $module['id'] ?>" data-kind="lessons">
                    <?php foreach ($module['lessons'] as $lesson): ?>
                        <div class="reorder-item" draggable="true" data-id="<?= (int) $lesson['id'] ?>">
                            <span class="block-handle">⠿</span>
                            📄 <a href="<?= adminUrl('lessons', ['action' => 'edit', 'id' => $lesson['id'], 'module_id' => $module['id'], 'course_id' => $course['id']]) ?>"><?= Security::e($lesson['title']) ?></a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($module['quizzes']): ?>
                <div class="reorder-list" data-reorder data-module-id="<?= (int) $module['id'] ?>" data-kind="quizzes">
                    <?php foreach ($module['quizzes'] as $quiz): ?>
                        <div class="reorder-item" draggable="true" data-id="<?= (int) $quiz['id'] ?>">
                            <span class="block-handle">⠿</span>
                            📝 <a href="<?= adminUrl('quizzes', ['action' => 'edit', 'id' => $quiz['id'], 'module_id' => $module['id'], 'course_id' => $course['id']]) ?>">Quiz: <?= Security::e($quiz['title']) ?></a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <a class="btn-link" href="<?= adminUrl('lessons', ['action' => 'create', 'module_id' => $module['id'], 'course_id' => $course['id']]) ?>">+ Leçon</a>
            <a class="btn-link" href="<?= adminUrl('quizzes', ['action' => 'create', 'module_id' => $module['id'], 'course_id' => $course['id']]) ?>">+ Quiz</a>
        </div>
    <?php endforeach; ?>

    <?php if ($modules): ?>
        <script>
        (function () {
            var csrfToken = <?= json_encode(Security::csrfToken()) ?>;
            var reorderUrl = <?= json_encode(adminUrl('modules', ['action' => 'reorder', 'course_id' => $course['id']])) ?>;

            document.querySelectorAll('[data-reorder]').forEach(function (list) {
                var dragId = null;

                function order() {
                    return Array.from(list.querySelectorAll('.reorder-item')).map(function (el) {
                        return el.dataset.id;
                    });
                }

                function save() {
                    var body = new URLSearchParams();
                    body.set('_csrf', csrfToken);
                    body.set('module_id', list.dataset.moduleId);
                    body.set('kind', list.dataset.kind);
                    body.set('order', JSON.stringify(order()));
                    fetch(reorderUrl, { method: 'POST', body: body, headers: { 'X-Requested-With': 'fetch' } });
                }

                list.addEventListener('dragstart', function (e) {
                    var item = e.target.closest('.reorder-item');
                    if (!item) return;
                    dragId = item.dataset.id;
                    item.classList.add('is-dragging');
                });
                list.addEventListener('dragend', function (e) {
                    var item = e.target.closest('.reorder-item');
                    if (item) item.classList.remove('is-dragging');
                    list.querySelectorAll('.reorder-item').forEach(function (el) {
                        el.classList.remove('drop-before', 'drop-after');
                    });
                });
                list.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    var item = e.target.closest('.reorder-item');
                    if (!item || dragId === null) return;
                    list.querySelectorAll('.reorder-item').forEach(function (el) {
                        el.classList.remove('drop-before', 'drop-after');
                    });
                    var rect = item.getBoundingClientRect();
                    var before = (e.clientY - rect.top) < rect.height / 2;
                    item.classList.add(before ? 'drop-before' : 'drop-after');
                });
                list.addEventListener('drop', function (e) {
                    e.preventDefault();
                    var item = e.target.closest('.reorder-item');
                    if (!item || dragId === null) return;
                    var dragged = list.querySelector('.reorder-item[data-id="' + dragId + '"]');
                    if (!dragged || dragged === item) return;
                    var rect = item.getBoundingClientRect();
                    var before = (e.clientY - rect.top) < rect.height / 2;
                    list.insertBefore(dragged, before ? item : item.nextSibling);
                    dragId = null;
                    save();
                });
            });
        })();
        </script>
    <?php endif; ?>
<?php else: ?>
    <p class="muted">Enregistrez le cours pour pouvoir ajouter des modules et des leçons.</p>
<?php endif; ?>
