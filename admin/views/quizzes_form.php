<h1><?= $quiz['id'] ? 'Modifier le quiz' : 'Nouveau quiz' ?></h1>
<p class="muted">Cours : <?= Security::e($course['title']) ?> &rsaquo; Module : <?= Security::e($module['title']) ?></p>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= Security::e($err) ?></div>
<?php endforeach; ?>

<form method="post" class="admin-form">
    <?= Security::csrfField() ?>
    <input type="hidden" name="form" value="quiz">
    <label>Titre
        <input type="text" name="title" value="<?= Security::e($quiz['title']) ?>" required>
    </label>
    <label>Score de réussite (%)
        <input type="number" min="0" max="100" name="pass_score" value="<?= (int) $quiz['pass_score'] ?>">
    </label>
    <label>Tentatives max (0 = illimité)
        <input type="number" min="0" name="max_attempts" value="<?= (int) $quiz['max_attempts'] ?>">
    </label>
    <label>Position
        <input type="number" name="position" value="<?= (int) $quiz['position'] ?>">
    </label>
    <button class="btn" type="submit">Enregistrer</button>
    <a class="btn-secondary" href="<?= adminUrl('courses', ['action' => 'edit', 'id' => $course['id']]) ?>">Retour au cours</a>
</form>

<?php if ($quiz['id']): ?>
    <h2>Questions</h2>
    <?php foreach ($questions as $q): ?>
        <div class="module-block">
            <div class="page-head">
                <strong><?= Security::e($q['question']) ?></strong> <span class="muted">(<?= $q['type'] === 'multiple' ? 'choix multiple' : 'choix unique' ?>)</span>
                <form method="post" style="display:inline" onsubmit="return confirm('Supprimer cette question ?');">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="form" value="delete_question">
                    <input type="hidden" name="question_id" value="<?= (int) $q['id'] ?>">
                    <button class="btn-danger" type="submit">Supprimer</button>
                </form>
            </div>
            <ul class="lesson-admin-list">
                <?php foreach ($q['answers'] as $a): ?>
                    <li>
                        <?= $a['is_correct'] ? '✅' : '⬜️' ?> <?= Security::e($a['answer']) ?>
                        <form method="post" style="display:inline">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="form" value="delete_answer">
                            <input type="hidden" name="answer_id" value="<?= (int) $a['id'] ?>">
                            <button class="btn-link" type="submit">retirer</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
            <form method="post" class="inline-form">
                <?= Security::csrfField() ?>
                <input type="hidden" name="form" value="add_answer">
                <input type="hidden" name="question_id" value="<?= (int) $q['id'] ?>">
                <input type="text" name="answer" placeholder="Nouvelle réponse" required>
                <label class="checkbox-inline"><input type="checkbox" name="is_correct"> correcte</label>
                <button class="btn-secondary" type="submit">Ajouter une réponse</button>
            </form>
        </div>
    <?php endforeach; ?>

    <form method="post" class="admin-form">
        <?= Security::csrfField() ?>
        <input type="hidden" name="form" value="add_question">
        <label>Nouvelle question
            <input type="text" name="question" required>
        </label>
        <label>Type
            <select name="type">
                <option value="single">Choix unique</option>
                <option value="multiple">Choix multiple</option>
            </select>
        </label>
        <button class="btn" type="submit">Ajouter la question</button>
    </form>
<?php else: ?>
    <p class="muted">Enregistrez le quiz pour ajouter des questions.</p>
<?php endif; ?>
