<?php

declare(strict_types=1);

$userId = Auth::id();
$isAdmin = Auth::hasRole('admin');

$courseId = (int) ($_GET['course_id'] ?? 0);
$moduleId = (int) ($_GET['module_id'] ?? 0);

$stmt = $db->prepare('SELECT * FROM courses WHERE id = ?');
$stmt->execute([$courseId]);
$course = $stmt->fetch();
if (!$course || (!$isAdmin && (int) $course['instructor_id'] !== $userId)) {
    http_response_code(404);
    exit('Cours introuvable.');
}

$stmt = $db->prepare('SELECT * FROM modules WHERE id = ? AND course_id = ?');
$stmt->execute([$moduleId, $courseId]);
$module = $stmt->fetch();
if (!$module) {
    http_response_code(404);
    exit('Module introuvable.');
}

function backToQuiz(int $quizId, int $moduleId, int $courseId): void
{
    header('Location: ' . adminUrl('quizzes', ['action' => 'edit', 'id' => $quizId, 'module_id' => $moduleId, 'course_id' => $courseId]));
    exit;
}

switch ($action) {
    case 'create':
    case 'edit':
        $quiz = ['id' => null, 'title' => '', 'pass_score' => 70, 'max_attempts' => 0, 'position' => 0];
        if ($action === 'edit') {
            $id = (int) ($_GET['id'] ?? 0);
            $stmt = $db->prepare('SELECT * FROM quizzes WHERE id = ? AND module_id = ?');
            $stmt->execute([$id, $moduleId]);
            $quiz = $stmt->fetch();
            if (!$quiz) {
                http_response_code(404);
                exit('Quiz introuvable.');
            }
        }

        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'quiz') {
            if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
                $errors[] = 'Jeton de sécurité invalide.';
            } else {
                $title = trim((string) ($_POST['title'] ?? ''));
                $passScore = max(0, min(100, (int) ($_POST['pass_score'] ?? 70)));
                $maxAttempts = max(0, (int) ($_POST['max_attempts'] ?? 0));
                $position = (int) ($_POST['position'] ?? 0);

                if ($title === '') {
                    $errors[] = 'Le titre est obligatoire.';
                }

                if (!$errors) {
                    if ($action === 'create') {
                        $db->prepare('INSERT INTO quizzes (module_id, title, pass_score, max_attempts, position) VALUES (?, ?, ?, ?, ?)')
                            ->execute([$moduleId, $title, $passScore, $maxAttempts, $position]);
                        backToQuiz((int) $db->lastInsertId(), $moduleId, $courseId);
                    }
                    $db->prepare('UPDATE quizzes SET title = ?, pass_score = ?, max_attempts = ?, position = ? WHERE id = ? AND module_id = ?')
                        ->execute([$title, $passScore, $maxAttempts, $position, $quiz['id'], $moduleId]);
                    backToQuiz((int) $quiz['id'], $moduleId, $courseId);
                }
                $quiz = array_merge($quiz, compact('title', 'passScore', 'maxAttempts', 'position'));
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'add_question' && $quiz['id']) {
            if (Security::verifyCsrf($_POST['_csrf'] ?? null)) {
                $question = trim((string) ($_POST['question'] ?? ''));
                $type = in_array($_POST['type'] ?? '', ['single', 'multiple'], true) ? $_POST['type'] : 'single';
                if ($question !== '') {
                    $db->prepare('INSERT INTO quiz_questions (quiz_id, question, type) VALUES (?, ?, ?)')
                        ->execute([$quiz['id'], $question, $type]);
                }
            }
            backToQuiz((int) $quiz['id'], $moduleId, $courseId);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'delete_question' && $quiz['id']) {
            if (Security::verifyCsrf($_POST['_csrf'] ?? null)) {
                $qid = (int) ($_POST['question_id'] ?? 0);
                $check = $db->prepare('SELECT 1 FROM quiz_questions WHERE id = ? AND quiz_id = ?');
                $check->execute([$qid, $quiz['id']]);
                if ($check->fetch()) {
                    $db->prepare('DELETE FROM quiz_questions WHERE id = ?')->execute([$qid]);
                }
            }
            backToQuiz((int) $quiz['id'], $moduleId, $courseId);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'add_answer' && $quiz['id']) {
            if (Security::verifyCsrf($_POST['_csrf'] ?? null)) {
                $qid = (int) ($_POST['question_id'] ?? 0);
                $check = $db->prepare('SELECT 1 FROM quiz_questions WHERE id = ? AND quiz_id = ?');
                $check->execute([$qid, $quiz['id']]);
                if ($check->fetch()) {
                    $answer = trim((string) ($_POST['answer'] ?? ''));
                    $isCorrect = !empty($_POST['is_correct']) ? 1 : 0;
                    if ($answer !== '') {
                        $db->prepare('INSERT INTO quiz_answers (question_id, answer, is_correct) VALUES (?, ?, ?)')
                            ->execute([$qid, $answer, $isCorrect]);
                    }
                }
            }
            backToQuiz((int) $quiz['id'], $moduleId, $courseId);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'delete_answer' && $quiz['id']) {
            if (Security::verifyCsrf($_POST['_csrf'] ?? null)) {
                $aid = (int) ($_POST['answer_id'] ?? 0);
                $check = $db->prepare('SELECT qa.id FROM quiz_answers qa JOIN quiz_questions qq ON qq.id = qa.question_id WHERE qa.id = ? AND qq.quiz_id = ?');
                $check->execute([$aid, $quiz['id']]);
                if ($check->fetch()) {
                    $db->prepare('DELETE FROM quiz_answers WHERE id = ?')->execute([$aid]);
                }
            }
            backToQuiz((int) $quiz['id'], $moduleId, $courseId);
        }

        $questions = [];
        if ($quiz['id']) {
            $stmt = $db->prepare('SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY position ASC, id ASC');
            $stmt->execute([$quiz['id']]);
            $questions = $stmt->fetchAll();
            foreach ($questions as &$q) {
                $a = $db->prepare('SELECT * FROM quiz_answers WHERE question_id = ? ORDER BY position ASC, id ASC');
                $a->execute([$q['id']]);
                $q['answers'] = $a->fetchAll();
            }
            unset($q);
        }

        render('quizzes_form', compact('quiz', 'course', 'module', 'errors', 'questions'));
        break;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::verifyCsrf($_POST['_csrf'] ?? null)) {
            $id = (int) ($_POST['id'] ?? 0);
            $db->prepare('DELETE FROM quizzes WHERE id = ? AND module_id = ?')->execute([$id, $moduleId]);
        }
        header('Location: ' . adminUrl('courses', ['action' => 'edit', 'id' => $courseId]));
        exit;

    default:
        header('Location: ' . adminUrl('courses', ['action' => 'edit', 'id' => $courseId]));
        exit;
}
