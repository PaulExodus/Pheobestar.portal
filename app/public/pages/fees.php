<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAuth();

$role = getUserRole();
$userId = getUserId();
$sidebarMenu = getSidebarMenu($role);

$page = intval($_GET['page'] ?? 1);
$search = trim($_GET['search'] ?? '');

// Fee data based on role
$studentFees = [];
$feeStructures = [];
$totalOwed = 0;
$totalPaid = 0;

if ($role === 'student') {
    $student = fetchOne("SELECT id FROM students WHERE user_id = ?", [$userId]);
    if ($student) {
        $studentFees = fetchAll("SELECT sf.*, fc.category_name, fs.amount as original_amount FROM student_fees sf JOIN fee_structures fs ON sf.fee_structure_id = fs.id JOIN fee_categories fc ON fs.category_id = fc.id WHERE sf.student_id = ? ORDER BY sf.created_at DESC", [$student['id']]);
        $summary = fetchOne("SELECT COALESCE(SUM(balance),0) as owed, COALESCE(SUM(amount_paid),0) as paid FROM student_fees WHERE student_id = ?", [$student['id']]);
        $totalOwed = $summary['owed'] ?? 0;
        $totalPaid = $summary['paid'] ?? 0;
    }
} elseif ($role === 'parent') {
    $wards = fetchAll("SELECT id FROM students WHERE parent_id = ?", [$userId]);
    $wardIds = array_column($wards, 'id');
    if (!empty($wardIds)) {
        $placeholders = implode(',', array_fill(0, count($wardIds), '?'));
        $studentFees = fetchAll("SELECT sf.*, fc.category_name, fs.amount, u.first_name, u.last_name FROM student_fees sf JOIN fee_structures fs ON sf.fee_structure_id = fs.id JOIN fee_categories fc ON fs.category_id = fc.id JOIN students s ON sf.student_id = s.id JOIN users u ON s.user_id = u.id WHERE sf.student_id IN ($placeholders) ORDER BY sf.created_at DESC", $wardIds);
    }
} elseif (hasRole(['bursar','admin'])) {
    $where = "WHERE 1=1";
    $params = [];
    if ($search) {
        $where .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR s.admission_number LIKE ?)";
        $params = ["%$search%", "%$search%", "%$search%"];
    }
    $result = paginate("SELECT sf.*, fc.category_name, fs.amount, u.first_name, u.last_name, s.admission_number FROM student_fees sf JOIN fee_structures fs ON sf.fee_structure_id = fs.id JOIN fee_categories fc ON fs.category_id = fc.id JOIN students s ON sf.student_id = s.id JOIN users u ON s.user_id = u.id $where ORDER BY sf.created_at DESC", $params, $page);
    $studentFees = $result['items'];
    $totalPages = $result['totalPages'];
    
    $summary = fetchOne("SELECT COALESCE(SUM(amount_due),0) as total_due, COALESCE(SUM(amount_paid),0) as total_paid, COALESCE(SUM(balance),0) as total_balance FROM student_fees");
    $totalOwed = $summary['total_balance'] ?? 0;
    $totalPaid = $summary['total_paid'] ?? 0;
}

$statusColors = ['pending' => 'badge-warning', 'partial' => 'badge-info', 'paid' => 'badge-success', 'overdue' => 'badge-danger', 'waived' => 'badge-secondary'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fees - Phoebestar Royalty Schools</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --purple: #58135E; --pink: #ED1E78; --gold: #FFC107; --dark-purple: #2D0A33; --gray-100: #F8F9FA; --gray-200: #E9ECEF; --gray-300: #DEE2E6; --gray-500: #ADB5BD; --gray-700: #495057; --green: #28A745; --red: #DC3545; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--gray-100); color: var(--gray-700); }
        h1, h2, h3 { font-family: 'Playfair Display', serif; }
        .dashboard { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: linear-gradient(180deg, var(--dark-purple), var(--purple)); color: #fff; position: fixed; top: 0; left: 0; bottom: 0; overflow-y: auto; z-index: 100; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 12px; }
        .sidebar-header img { width: 44px; height: 44px; border-radius: 8px; }
        .sidebar-menu { padding: 16px 0; list-style: none; }
        .sidebar-menu li a { display: flex; align-items: center; gap: 12px; padding: 12px 24px; color: rgba(255,255,255,0.8); text-decoration: none; font-size: 13px; transition: all 0.3s; border-left: 3px solid transparent; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background: rgba(255,255,255,0.1); color: #fff; border-left-color: var(--gold); }
        .sidebar-footer { padding: 16px 24px; border-top: 1px solid rgba(255,255,255,0.1); }
        .sidebar-footer a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 13px; display: flex; align-items: center; gap: 10px; }
        .main-content { flex: 1; margin-left: 260px; padding: 24px; padding-bottom: 80px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
        .top-bar h1 { font-size: 24px; color: var(--purple); }
        .card { background: #fff; border-radius: 12px; border: 1px solid var(--gray-200); overflow: hidden; }
        .card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center; }
        .card-header h3 { font-size: 16px; color: var(--purple); }
        .card-body { padding: 20px; }
        .summary-bar { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .summary-item { padding: 16px; border-radius: 12px; text-align: center; }
        .summary-item.owed { background: rgba(220,53,69,0.08); }
        .summary-item.paid { background: rgba(40,167,69,0.08); }
        .summary-item h3 { font-size: 24px; color: var(--dark-purple); }
        .summary-item p { font-size: 12px; color: var(--gray-500); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; font-size: 12px; font-weight: 600; color: var(--gray-500); text-transform: uppercase; border-bottom: 1px solid var(--gray-200); }
        td { padding: 12px; font-size: 13px; border-bottom: 1px solid var(--gray-100); }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-warning { background: rgba(255,193,7,0.1); color: #d4a017; }
        .badge-success { background: rgba(40,167,69,0.1); color: var(--green); }
        .badge-danger { background: rgba(220,53,69,0.1); color: var(--red); }
        .btn-primary { background: var(--purple); color: #fff; border: none; border-radius: 8px; padding: 10px 20px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.3s; }
        .btn-primary:hover { background: var(--dark-purple); }
        .btn-pay { background: var(--green); color: #fff; border: none; border-radius: 6px; padding: 6px 14px; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; }
        .search-box { display: flex; align-items: center; background: var(--gray-100); border: 1px solid var(--gray-200); border-radius: 8px; padding: 0 12px; height: 40px; }
        .search-box input { border: none; background: none; outline: none; font-size: 13px; width: 200px; font-family: 'Inter', sans-serif; }
        .menu-toggle { display: none; background: none; border: none; font-size: 20px; color: var(--purple); cursor: pointer; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); z-index: 500; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 16px; }
            .menu-toggle { display: block; }
            table { display: block; overflow-x: auto; }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="/public/assets/logo-icon.png" alt="PRS">
                <div><h3 style="font-size:16px;">Phoebestar</h3><span style="font-size:11px;opacity:0.7;">Royalty Schools</span></div>
            </div>
            <ul class="sidebar-menu">
                <?php foreach ($sidebarMenu as $item): ?>
                <li><a href="<?= $item['url'] ?>" class="<?= strpos($item['url'], 'fees') !== false ? 'active' : '' ?>"><i class="fas fa-<?= strtolower(str_replace(['LayoutDashboard','Users','GraduationCap','UserCheck','BookOpen','FileText','Wallet','Monitor','ClipboardList','UserPlus','BarChart3','Newspaper','Bell','Image','Library','MessageCircle','Settings','Video','CreditCard'], ['th-large','users','graduation-cap','user-check','book-open','file-alt','wallet','desktop','clipboard-list','user-plus','chart-bar','newspaper','bell','image','book','comments','cog','video','credit-card'], $item['icon'])) ?>"></i> <?= $item['label'] ?></a></li>
                <?php endforeach; ?>
            </ul>
            <div class="sidebar-footer"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div style="display:flex;align-items:center;gap:16px;">
                    <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button>
                    <h1><?= $role === 'student' ? 'My Fees' : ($role === 'parent' ? 'Ward Fees' : 'Fee Management') ?></h1>
                </div>
                <?php if (hasRole(['bursar','admin'])): ?>
                <div style="display:flex;gap:8px;">
                    <form method="GET"><div class="search-box"><i class="fas fa-search" style="color:var(--gray-500);margin-right:8px;"></i><input type="text" name="search" placeholder="Search..." value="<?= sanitize($search) ?>"></div></form>
                    <a href="fee-structures.php" class="btn-primary"><i class="fas fa-plus"></i> Fee Structure</a>
                </div>
                <?php endif; ?>
            </div>

            <div class="summary-bar">
                <div class="summary-item owed">
                    <h3 style="color:var(--red);"><?= formatCurrency($totalOwed) ?></h3>
                    <p>Total Balance</p>
                </div>
                <div class="summary-item paid">
                    <h3 style="color:var(--green);"><?= formatCurrency($totalPaid) ?></h3>
                    <p>Total Paid</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3>Fee Details</h3></div>
                <div class="card-body">
                    <table>
                        <thead>
                            <tr>
                                <?php if (hasRole(['bursar','admin','parent'])): ?><th>Student</th><?php endif; ?>
                                <th>Category</th>
                                <th>Amount Due</th>
                                <th>Amount Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($studentFees)): ?>
                            <tr><td colspan="7" style="text-align:center;color:var(--gray-500);padding:40px;">No fee records found</td></tr>
                            <?php else: foreach ($studentFees as $f): ?>
                            <tr>
                                <?php if (hasRole(['bursar','admin','parent'])): ?>
                                <td><?= sanitize(($f['first_name'] ?? '') . ' ' . ($f['last_name'] ?? '')) ?></td>
                                <?php endif; ?>
                                <td><?= sanitize($f['category_name'] ?? 'N/A') ?></td>
                                <td><?= formatCurrency($f['amount_due'] ?? $f['amount'] ?? 0) ?></td>
                                <td><?= formatCurrency($f['amount_paid'] ?? 0) ?></td>
                                <td><?= formatCurrency($f['balance'] ?? ($f['amount_due'] ?? 0) - ($f['amount_paid'] ?? 0)) ?></td>
                                <td><span class="badge badge-<?= $f['status'] ?? 'pending' ?>"><?= ucfirst($f['status'] ?? 'Pending') ?></span></td>
                                <td>
                                    <?php if (($f['balance'] ?? 0) > 0): ?>
                                    <a href="fee-payment.php?id=<?= $f['id'] ?>" class="btn-pay"><i class="fas fa-credit-card"></i> Pay</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
