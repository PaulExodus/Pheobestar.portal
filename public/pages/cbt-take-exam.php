<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('student');

$examId = intval($_GET['id'] ?? 0);
$userId = getUserId();

$exam = fetchOne("SELECT e.*, sub.subject_name FROM cbt_exams e LEFT JOIN subjects sub ON e.subject_id = sub.id WHERE e.id = ? AND e.status = 'published'", [$examId]);
if (!$exam) {
    setFlash('error', 'Exam not found or not available');
    redirect(APP_URL . '/public/pages/cbt-exams.php');
}

$student = fetchOne("SELECT id FROM students WHERE user_id = ?", [$userId]);
if (!$student) {
    setFlash('error', 'Student record not found');
    redirect(APP_URL . '/public/pages/cbt-exams.php');
}

// Check for existing attempt
$existingAttempt = fetchOne("SELECT * FROM cbt_student_attempts WHERE exam_id = ? AND student_id = ? AND status IN ('in_progress','submitted') ORDER BY attempt_number DESC LIMIT 1", [$examId, $student['id']]);

// Get questions
$questions = fetchAll("SELECT * FROM cbt_questions WHERE exam_id = ? ORDER BY sort_order, RAND()", [$examId]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($exam['exam_title']) ?> - CBT Exam</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --purple: #58135E; --pink: #ED1E78; --gold: #FFC107; --dark-purple: #2D0A33; --gray-100: #F8F9FA; --gray-200: #E9ECEF; --gray-300: #DEE2E6; --gray-500: #ADB5BD; --gray-700: #495057; --green: #28A745; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--gray-100); }
        .exam-container { max-width: 800px; margin: 0 auto; padding: 24px; }
        .exam-header { background: linear-gradient(135deg, var(--purple), var(--pink)); color: #fff; padding: 24px; border-radius: 12px; margin-bottom: 24px; }
        .exam-header h1 { font-size: 20px; margin-bottom: 8px; }
        .exam-header p { font-size: 13px; opacity: 0.9; }
        .timer { position: fixed;n    top: 20px; right: 20px; background: var(--dark-purple); color: #fff; padding: 12px 20px; border-radius: 8px; font-size: 18px; font-weight: 700; z-index: 100; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .timer.warning { background: #ff9800; animation: pulse 1s infinite; }
        .timer.danger { background: var(--red); animation: pulse 0.5s infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        .question-card { background: #fff; border-radius: 12px; padding: 24px; margin-bottom: 16px; border: 1px solid var(--gray-200); }
        .question-num { font-size: 12px; color: var(--pink); font-weight: 600; margin-bottom: 8px; }
        .question-text { font-size: 15px; color: var(--dark-purple); margin-bottom: 16px; line-height: 1.6; }
        .options { display: flex; flex-direction: column; gap: 10px; }
        .option { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border: 2px solid var(--gray-200); border-radius: 8px; cursor: pointer; transition: all 0.3s; }
        .option:hover { border-color: var(--purple); background: rgba(88,19,94,0.03); }
        .option.selected { border-color: var(--purple); background: rgba(88,19,94,0.08); }
        .option input { width: 18px; height: 18px; accent-color: var(--purple); }
        .option label { flex: 1; font-size: 14px; cursor: pointer; }
        .btn-submit { width: 100%; background: var(--purple); color: #fff; border: none; border-radius: 8px; padding: 16px; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 24px; transition: all 0.3s; }
        .btn-submit:hover { background: var(--dark-purple); }
        .progress-bar { background: var(--gray-200); height: 6px; border-radius: 3px; margin-bottom: 24px; overflow: hidden; }
        .progress-fill { background: linear-gradient(90deg, var(--purple), var(--pink)); height: 100%; border-radius: 3px; transition: width 0.3s; }
        .instructions { background: #fff; border-radius: 12px; padding: 24px; margin-bottom: 24px; border: 1px solid var(--gray-200); }
        .instructions h3 { color: var(--purple); margin-bottom: 12px; }
        .instructions ul { padding-left: 20px; }
        .instructions li { margin-bottom: 8px; font-size: 14px; }
        .btn-start { background: var(--green); color: #fff; border: none; border-radius: 8px; padding: 14px 32px; font-size: 16px; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>
    <div class="exam-container">
        <?php if (!$existingAttempt || $existingAttempt['status'] !== 'submitted'): ?>
        <div class="timer" id="timer"><?= $exam['duration_minutes'] ?>:00</div>
        <?php endif; ?>

        <div class="exam-header">
            <h1><i class="fas fa-laptop" style="margin-right:8px;"></i><?= sanitize($exam['exam_title']) ?></h1>
            <p><?= sanitize($exam['subject_name'] ?? '') ?> | <?= $exam['duration_minutes'] ?> minutes | <?= count($questions) ?> questions</p>
        </div>

        <?php if ($existingAttempt && $existingAttempt['status'] === 'submitted'): ?>
        <div class="instructions">
            <h3><i class="fas fa-check-circle" style="color:var(--green);"></i> Exam Completed</h3>
            <p>You have already submitted this exam.</p>
            <p><strong>Score:</strong> <?= $existingAttempt['score'] ?> / <?= $exam['total_marks'] ?> (<?= $existingAttempt['percentage'] ?>%)</p>
            <p><strong>Grade:</strong> <?= sanitize($existingAttempt['grade'] ?? 'N/A') ?></p>
            <p><strong>Remark:</strong> <?= sanitize($existingAttempt['remark'] ?? 'N/A') ?></p>
            <a href="cbt-exams.php" class="btn-start" style="text-decoration:none;display:inline-block;margin-top:16px;">Back to Exams</a>
        </div>
        <?php elseif (isset($_GET['start']) || $existingAttempt): ?>
        <div class="progress-bar"><div class="progress-fill" id="progressBar" style="width:0%"></div></div>

        <form method="POST" action="cbt-submit.php" id="examForm">
            <input type="hidden" name="exam_id" value="<?= $examId ?>">
            <?php foreach ($questions as $i => $q): ?>
            <div class="question-card" data-q="<?= $i + 1 ?>">
                <div class="question-num">Question <?= $i + 1 ?> of <?= count($questions) ?></div>
                <div class="question-text"><?= nl2br(sanitize($q['question_text'])) ?></div>
                <div class="options">
                    <?php foreach (['a','b','c','d','e'] as $opt): $optKey = 'option_' . $opt; if (!empty($q[$optKey])): ?>
                    <div class="option" onclick="selectOption(this)">
                        <input type="radio" name="q[<?= $q['id'] ?>]" value="<?= strtoupper($opt) ?>" id="q<?= $q['id'] ?>_<?= $opt ?>">
                        <label for="q<?= $q['id'] ?>_<?= $opt ?>"><?= strtoupper($opt) ?>. <?= sanitize($q[$optKey]) ?></label>
                    </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Submit Exam</button>
        </form>
        <?php else: ?>
        <div class="instructions">
            <h3>Exam Instructions</h3>
            <ul>
                <li>This exam contains <strong><?= count($questions) ?> questions</strong></li>
                <li>You have <strong><?= $exam['duration_minutes'] ?> minutes</strong> to complete</li>
                <li>Each question has only one correct answer</li>
                <li>You cannot pause or retake the exam once started</li>
                <li>The timer will start when you click "Start Exam"</li>
                <?php if ($exam['instructions']): ?><li><?= nl2br(sanitize($exam['instructions'])) ?></li><?php endif; ?>
            </ul>
            <a href="?id=<?= $examId ?>&start=1" class="btn-start" style="text-decoration:none;display:inline-block;margin-top:16px;"><i class="fas fa-play"></i> Start Exam</a>
        </div>
        <?php endif; ?>
    </div>

    <script>
        <?php if ((!$existingAttempt || $existingAttempt['status'] !== 'submitted') && (isset($_GET['start']) || $existingAttempt)): ?>
        // Timer
        let duration = <?= $exam['duration_minutes'] ?> * 60;
        let timerInterval = setInterval(function() {
            duration--;
            let mins = Math.floor(duration / 60);
            let secs = duration % 60;
            document.getElementById('timer').textContent = mins + ':' + (secs < 10 ? '0' : '') + secs;
            
            if (duration <= 60) {
                document.getElementById('timer').classList.add('danger');
            } else if (duration <= 300) {
                document.getElementById('timer').classList.add('warning');
            }
            
            if (duration <= 0) {
                clearInterval(timerInterval);
                document.getElementById('examForm').submit();
            }
        }, 1000);

        // Auto-submit on form
        document.getElementById('examForm').addEventListener('submit', function() {
            clearInterval(timerInterval);
        });

        // Progress tracking
        function selectOption(el) {
            el.classList.add('selected');
            let siblings = el.parentElement.querySelectorAll('.option');
            siblings.forEach(s => { if (s !== el) s.classList.remove('selected'); });
            updateProgress();
        }

        function updateProgress() {
            const total = document.querySelectorAll('.question-card').length;
            const answered = document.querySelectorAll('.option.selected').length;
            document.getElementById('progressBar').style.width = (answered / total * 100) + '%';
        }
        <?php endif; ?>
    </script>
</body>
</html>
