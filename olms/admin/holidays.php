<?php
require __DIR__ . '/../includes/bootstrap.php';
$admin = require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? 'add');
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM holidays WHERE id = ?')->execute([$id]);
        set_flash('success', 'Holiday removed.');
    } else {
        $date = trim((string)($_POST['holiday_date'] ?? ''));
        $title = trim((string)($_POST['title'] ?? ''));
        $parsed = DateTime::createFromFormat('Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date || $title === '') set_flash('danger', 'Enter a valid holiday date and name.');
        else {
            try { $pdo->prepare('INSERT INTO holidays (holiday_date, title) VALUES (?, ?)')->execute([$date,$title]); set_flash('success', 'Holiday added.'); }
            catch (PDOException $e) { set_flash('warning', 'A holiday is already configured for that date.'); }
        }
    }
    redirect('admin/holidays.php');
}
$year = (int)($_GET['year'] ?? date('Y'));
if ($year < 2000 || $year > 2100) $year = (int)date('Y');
$stmt = $pdo->prepare('SELECT * FROM holidays WHERE YEAR(holiday_date) = ? ORDER BY holiday_date'); $stmt->execute([$year]); $holidays = $stmt->fetchAll();
$pageTitle = 'Company holidays';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">WORKING DAY CALENDAR</span><h1>Company holidays.</h1><p>Manage non-working dates. Configured holidays are excluded from leave-day calculations.</p></div></div>
<div class="holiday-layout"><section class="panel"><div class="panel-heading"><div><h2>Holiday calendar · <?= $year ?></h2><p><?= count($holidays) ?> holiday<?= count($holidays) === 1 ? '' : 's' ?> configured</p></div><div class="year-select"><a class="btn btn-outline btn-small" href="<?= h(url('admin/holidays.php?year=' . ($year-1))) ?>">←</a><a class="btn btn-outline btn-small" href="<?= h(url('admin/holidays.php?year=' . ($year+1))) ?>">→</a></div></div>
<?php if (!$holidays): ?><div class="empty-state"><div class="empty-icon">▦</div><strong>No holidays for <?= $year ?></strong><p>Add your company holidays to exclude them from leave calculations.</p></div><?php else: ?><div class="holiday-list"><?php foreach ($holidays as $holiday): ?><div class="holiday-row"><div class="holiday-date"><strong><?= date('d', strtotime($holiday['holiday_date'])) ?></strong><small><?= date('M', strtotime($holiday['holiday_date'])) ?></small></div><div><strong><?= h($holiday['title']) ?></strong><small><?= date('l, d F Y', strtotime($holiday['holiday_date'])) ?></small></div><form method="post" onsubmit="return confirm('Remove this holiday from the calendar?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$holiday['id'] ?>"><button type="submit" class="link-danger">Remove</button></form></div><?php endforeach; ?></div><?php endif; ?></section>
<section class="panel policy-form-panel"><div class="panel-heading"><div><h2>Add a holiday</h2><p>One holiday per date.</p></div></div><form method="post" class="form-stack"><?= csrf_field() ?><input type="hidden" name="action" value="add"><label>Holiday date *<input type="date" name="holiday_date" required></label><label>Holiday name *<input name="title" maxlength="120" required placeholder="e.g. Diwali"></label><button class="btn btn-primary btn-block" type="submit">Add holiday <span>→</span></button></form><div class="side-callout"><strong>Calculation note</strong><p>Weekends are excluded automatically. Add organisation-specific holidays here so employee leave balances count only working days.</p></div></section></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
