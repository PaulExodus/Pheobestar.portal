<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['teacher', 'admin', 'principal']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subjectId = intval($_POST['subject_id'] ?? 0);
    $classId = intval($_POST['class_id'] ?? 0);
    $session = getCurrentSession();
    $term = getCurrentTerm();
    $teacherId = null;
    
    if (getUserRole() === 'teacher') {
        $teacher = fetchOne("SELECT id FROM teachers WHERE user_id = ?", [getUserId()]);
        $teacherId = $teacher['id'] ?? null;
    }
    
    $caScores = $_POST['ca'] ?? [];
    $examScores = $_POST['exam'] ?? [];
    
    $saved = 0;
    foreach ($caScores as $id => $ca) {
        $ca = floatval($ca);
        $exam = floatval($examScores[$id] ?? 0);
        $total = min(100, $ca + $exam);
        $grade = calculateGrade($total);
        
        // Check if record exists
        $existing = fetchOne("SELECT id FROM assessments WHERE id = ?", [$id]);
        if ($existing) {
            executeQuery("UPDATE assessments SET ca_score = ?, exam_score = ?, total_score = ?, grade = ?, remark = ?, teacher_id = ? WHERE id = ?",
                [$ca, $exam, $total, $grade['grade'], $grade['remark'], $teacherId, $id]);
        } else {
            // Create new assessment record
            $studentId = intval($_POST['student_id'][$id] ?? 0);
            if ($studentId && $session && $term) {
                executeQuery("INSERT INTO assessments (student_id, subject_id, class_id, session_id, term_id, ca_score, exam_score, total_score, grade, remark, teacher_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$studentId, $subjectId, $classId, $session['id'], $term['id'], $ca, $exam, $total, $grade['grade'], $grade['remark'], $teacherId]);
            }
        }
        $saved++;
    }
    
    setFlash('success', "$saved result(s) saved successfully!");
    redirect(APP_URL . '/public/pages/results.php?subject_id=' . $subjectId . '&class_id=' . $classId);
}

redirect(APP_URL . '/public/pages/results.php');
