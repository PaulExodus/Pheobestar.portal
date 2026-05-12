<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('student');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $examId = intval($_POST['exam_id'] ?? 0);
    $answers = $_POST['q'] ?? [];
    $userId = getUserId();
    
    $exam = fetchOne("SELECT * FROM cbt_exams WHERE id = ?", [$examId]);
    $student = fetchOne("SELECT id FROM students WHERE user_id = ?", [$userId]);
    
    if (!$exam || !$student) {
        setFlash('error', 'Invalid exam or student');
        redirect(APP_URL . '/public/pages/cbt-exams.php');
    }
    
    // Get all questions and calculate score
    $questions = fetchAll("SELECT * FROM cbt_questions WHERE exam_id = ?", [$examId]);
    $totalMarks = 0;
    $obtainedMarks = 0;
    
    foreach ($questions as $q) {
        $qMarks = floatval($q['marks'] ?? 1);
        $totalMarks += $qMarks;
        $studentAnswer = $answers[$q['id']] ?? '';
        
        if ($q['question_type'] === 'multiple_choice' && $studentAnswer === $q['correct_answer']) {
            $obtainedMarks += $qMarks;
        } elseif ($q['question_type'] === 'true_false' && strtoupper($studentAnswer) === strtoupper($q['correct_answer'] ?? '')) {
            $obtainedMarks += $qMarks;
        }
    }
    
    $percentage = $totalMarks > 0 ? round(($obtainedMarks / $totalMarks) * 100, 2) : 0;
    $grade = calculateGrade($percentage);
    
    // Save attempt
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO cbt_student_attempts (exam_id, student_id, answers, score, percentage, grade, remark, status, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'submitted', NOW())");
    $stmt->execute([$examId, $student['id'], json_encode($answers), $obtainedMarks, $percentage, $grade['grade'], $grade['remark']]);
    
    setFlash('success', "Exam submitted! Score: $obtainedMarks/$totalMarks ($percentage%) - Grade: {$grade['grade']}");
    redirect(APP_URL . '/public/pages/cbt-exams.php');
}

redirect(APP_URL . '/public/pages/cbt-exams.php');
