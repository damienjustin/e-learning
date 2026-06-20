<h1>Quiz : <?= Security::e($quiz['title']) ?></h1>

<?php if ($result): ?>
    <div class="alert <?= $result['passed'] ? 'alert-success' : 'alert-error' ?>">
        Score : <?= (int) $result['score'] ?>% &mdash;
        <?= $result['passed'] ? 'Réussi !' : 'Échoué, vous pouvez réessayer.' ?>
    </div>
<?php endif; ?>

<form method="post">
    <?= Security::csrfField() ?>
    <?php foreach ($questions as $i => $question): ?>
        <fieldset class="quiz-question">
            <legend>Q<?= $i + 1 ?>. <?= Security::e($question['question']) ?></legend>
            <?php foreach ($question['answers'] as $answer): ?>
                <label class="quiz-option">
                    <input type="<?= $question['type'] === 'multiple' ? 'checkbox' : 'radio' ?>"
                           name="q<?= (int) $question['id'] ?><?= $question['type'] === 'multiple' ? '[]' : '' ?>"
                           value="<?= (int) $answer['id'] ?>">
                    <?= Security::e($answer['answer']) ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
    <?php endforeach; ?>
    <button class="btn" type="submit">Valider mes réponses</button>
</form>
