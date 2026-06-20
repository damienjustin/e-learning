<?php

declare(strict_types=1);

Auth::requireLogin();

$quizId = (int) ($_GET['quiz_id'] ?? 0);

$stmt = $db->prepare('SELECT q.*, m.course_id FROM quizzes q JOIN modules m ON m.id = q.module_id WHERE q.id = ? LIMIT 1');
$stmt->execute([$quizId]);
$quiz = $stmt->fetch();
if (!$quiz) {
    notFound();
}

$stmt = $db->prepare('SELECT 1 FROM enrollments WHERE user_id = ? AND course_id = ?');
$stmt->execute([Auth::id(), $quiz['course_id']]);
if (!$stmt->fetch() && !Auth::hasRole('admin', 'instructor')) {
    http_response_code(403);
    exit('Vous devez être inscrit à ce cours.');
}

$stmt = $db->prepare('SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY position ASC, id ASC');
$stmt->execute([$quizId]);
$questions = $stmt->fetchAll();
foreach ($questions as &$question) {
    $answers = $db->prepare('SELECT id, answer FROM quiz_answers WHERE question_id = ? ORDER BY position ASC, id ASC');
    $answers->execute([$question['id']]);
    $question['answers'] = $answers->fetchAll();
}
unset($question);

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
        http_response_code(400);
        exit('Jeton de sécurité invalide.');
    }

    if ($quiz['max_attempts'] > 0) {
        $attemptsStmt = $db->prepare('SELECT COUNT(*) FROM quiz_attempts WHERE user_id = ? AND quiz_id = ?');
        $attemptsStmt->execute([Auth::id(), $quizId]);
        if ((int) $attemptsStmt->fetchColumn() >= $quiz['max_attempts']) {
            http_response_code(403);
            exit('Nombre maximum de tentatives atteint.');
        }
    }

    $correctCount = 0;
    $submitted = [];
    foreach ($questions as $question) {
        $given = $_POST['q' . $question['id']] ?? [];
        $given = is_array($given) ? array_map('intval', $given) : [(int) $given];
        sort($given);

        $correctStmt = $db->prepare('SELECT id FROM quiz_answers WHERE question_id = ? AND is_correct = 1');
        $correctStmt->execute([$question['id']]);
        $correctIds = array_map('intval', array_column($correctStmt->fetchAll(), 'id'));
        sort($correctIds);

        $isCorrect = ($given === $correctIds && $given !== []);
        if ($isCorrect) {
            $correctCount++;
        }
        $submitted['q' . $question['id']] = $given;
    }

    $score = $questions === [] ? 0 : (int) round(($correctCount / count($questions)) * 100);
    $passed = $score >= $quiz['pass_score'];

    $db->prepare('INSERT INTO quiz_attempts (user_id, quiz_id, score, passed, answers_json) VALUES (?, ?, ?, ?, ?)')
        ->execute([Auth::id(), $quizId, $score, $passed ? 1 : 0, json_encode($submitted)]);

    $result = ['score' => $score, 'passed' => $passed];
}

View::render('quiz_take', [
    'quiz' => $quiz,
    'questions' => $questions,
    'result' => $result,
]);
