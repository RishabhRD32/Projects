<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// ── Handle status update ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $oid = (int)$_POST['order_id'];
    $ns  = $conn->real_escape_string($_POST['new_status']);
    $conn->query("UPDATE orders SET status='$ns' WHERE id=$oid");
    header("Location: admin.php?tab=orders&msg=updated&oid=$oid");
    exit;
}

// ── Handle delete ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $oid = (int)$_POST['order_id'];
    $conn->query("DELETE FROM orders WHERE id=$oid");
    header("Location: admin.php?tab=orders&msg=deleted");
    exit;
}

$tab = $_GET['tab'] ?? 'orders';

// ── Stats (live from DB) ───────────────────────────────────────
$s = [];
$s['total_orders']   = $conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'];
$s['revenue']        = $conn->query("SELECT IFNULL(SUM(total),0) r FROM orders WHERE status!='cancelled'")->fetch_assoc()['r'];
$s['customers']      = $conn->query("SELECT COUNT(*) c FROM users WHERE role='customer'")->fetch_assoc()['c'];
$s['pending']        = $conn->query("SELECT COUNT(*) c FROM orders WHERE status='pending'")->fetch_assoc()['c'];
$s['delivered']      = $conn->query("SELECT COUNT(*) c FROM orders WHERE status='delivered'")->fetch_assoc()['c'];
$s['cod']            = $conn->query("SELECT COUNT(*) c FROM orders WHERE payment_method='cod'")->fetch_assoc()['c'];

// ── Orders (filter support) ────────────────────────────────────
$sf   = $conn->real_escape_string($_GET['sf'] ?? '');
$pmf  = $conn->real_escape_string($_GET['pmf'] ?? '');
$wheres = ["1=1"];
if ($sf)  $wheres[] = "o.status='$sf'";
if ($pmf) $wheres[] = "o.payment_method='$pmf'";
$where = implode(' AND ', $wheres);

$orders = $conn->query("
    SELECT o.*, u.email, u.name AS uname
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE $where
    ORDER BY o.created_at DESC
");

// ── Customers ─────────────────────────────────────────────────
$customers = $conn->query("
    SELECT u.*, COUNT(o.id) AS orders, IFNULL(SUM(o.total),0) AS spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    WHERE u.role='customer'
    GROUP BY u.id
    ORDER BY u.created_at DESC
");

// ── Products ──────────────────────────────────────────────────
$products = $conn->query("SELECT * FROM products ORDER BY id");

$steps = ['pending','confirmed','preparing','out_for_delivery','delivered'];
$slabels = [
    'pending'=>'Pending','confirmed'=>'Confirmed','preparing'=>'Preparing',
    'out_for_delivery'=>'On the Way','delivered'=>'Delivered','cancelled'=>'Cancelled'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin – Foodie</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--gold:#D4AF37;--lead:#1a1a2e;--bg:#f0f2f7;--white:#fff;--sw:230px;
      --green:#27ae60;--red:#e74c3c;--blue:#2980b9;--orange:#e67e22}
body{background:var(--bg);font-family:'DM Sans',sans-serif;display:flex;min-height:100vh}

/* SIDEBAR */
.sb{width:var(--sw);background:var(--lead);display:flex;flex-direction:column;
    position:fixed;top:0;left:0;height:100vh;z-index:100;overflow-y:auto}
.sb-brand{padding:22px 20px 18px;border-bottom:1px solid rgba(255,255,255,.07)}
.sb-brand .logo{font-family:'Playfair Display',serif;font-size:1.65rem;color:var(--gold);display:block;margin-bottom:2px}
.sb-brand small{color:#666;font-size:.68rem;text-transform:uppercase;letter-spacing:.8px}
.sb nav{padding:10px 0;flex:1}
.sb nav a{display:flex;align-items:center;gap:11px;padding:12px 20px;color:#aaa;text-decoration:none;
           font-size:.87rem;font-weight:500;transition:.15s;border-left:3px solid transparent}
.sb nav a:hover{background:rgba(255,255,255,.04);color:#ddd}
.sb nav a.on{background:rgba(212,175,55,.1);color:var(--gold);border-left-color:var(--gold)}
.sb nav a i{width:16px;text-align:center}
.sb-foot{padding:16px 20px;border-top:1px solid rgba(255,255,255,.07)}
.sb-foot a{display:flex;align-items:center;gap:9px;color:#777;text-decoration:none;font-size:.82rem}
.sb-foot a:hover{color:var(--gold)}

/* MAIN */
.main{margin-left:var(--sw);flex:1;display:flex;flex-direction:column}
.topbar{background:var(--white);padding:0 28px;height:62px;display:flex;align-items:center;
        justify-content:space-between;box-shadow:0 1px 8px rgba(0,0,0,.07);
        position:sticky;top:0;z-index:50}
.topbar h1{font-size:1.15rem;font-weight:700;color:var(--lead)}
.admin-pill{background:rgba(212,175,55,.12);color:var(--gold);padding:6px 14px;border-radius:18px;
            font-size:.8rem;font-weight:700;display:flex;align-items:center;gap:6px}

/* ALERTS */
.alert{display:flex;align-items:center;gap:8px;padding:12px 18px;border-radius:10px;
       font-size:.86rem;font-weight:600;margin-bottom:20px}
.alert.ok{background:#d4edda;color:#155724}
.alert.del{background:#f8d7da;color:#721c24}

/* CONTENT */
.content{padding:26px 28px;flex:1}

/* STATS */
.stats{display:grid;grid-template-columns:repeat(6,1fr);gap:14px;margin-bottom:26px}
.stat{background:var(--white);border-radius:13px;padding:16px 18px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
.stat .ico{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;color:#fff;margin-bottom:10px}
.stat .val{font-size:1.45rem;font-weight:800;color:var(--lead)}
.stat .lbl{font-size:.72rem;color:#aaa;font-weight:500;margin-top:3px}

/* CARD */
.card{background:var(--white);border-radius:16px;padding:22px;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:20px}
.card-title{font-size:1rem;font-weight:700;color:var(--lead);margin-bottom:16px;display:flex;align-items:center;gap:8px}
.card-title i{color:var(--gold)}

/* TOOLBAR */
.toolbar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px}
.search{padding:9px 15px;border:1.5px solid #e8e8e8;border-radius:20px;font-size:.84rem;width:260px;
        font-family:'DM Sans',sans-serif;outline:none}
.search:focus{border-color:var(--gold)}
.fsel{padding:8px 12px;border:1.5px solid #e8e8e8;border-radius:20px;font-size:.84rem;
      font-family:'DM Sans',sans-serif;outline:none;background:var(--white);cursor:pointer}
.fsel:focus{border-color:var(--gold)}

/* TABLE */
.tw{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:.83rem}
thead tr{background:#f8f9fa}
th{padding:11px 13px;text-align:left;font-weight:700;color:#666;font-size:.72rem;text-transform:uppercase;letter-spacing:.3px;border-bottom:2px solid #eee;white-space:nowrap}
td{padding:11px 13px;border-bottom:1px solid #f3f3f3;vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:#fffdf0}

/* BADGE */
.b{display:inline-flex;align-items:center;gap:3px;padding:3px 10px;border-radius:18px;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;white-space:nowrap}
.b-pending{background:#fff3cd;color:#856404}
.b-confirmed{background:#d1e7dd;color:#0f5132}
.b-preparing{background:#cfe2ff;color:#084298}
.b-out_for_delivery{background:#fde8d8;color:#7d3c03}
.b-delivered{background:#d4edda;color:#155724}
.b-cancelled{background:#f8d7da;color:#721c24}
.b-cod{background:#e9ecef;color:#555}
.b-online{background:#d1ecf1;color:#0c5460}
.b-paid{background:#d4edda;color:#155724}
.b-unpaid{background:#fff3cd;color:#856404}

/* MINI TRACKER */
.mini-t{display:flex;align-items:center;gap:3px}
.md{width:9px;height:9px;border-radius:50%;background:#e0e0e0}
.md.done{background:var(--gold)}
.md.active{background:var(--gold);box-shadow:0 0 0 2px rgba(212,175,55,.25)}
.mlbl{font-size:.7rem;color:#999;margin-left:4px}

/* EXPAND */
details summary{cursor:pointer;color:var(--blue);font-size:.78rem;font-weight:600;list-style:none;display:flex;align-items:center;gap:4px}
details[open] summary{color:var(--lead)}
details ul{margin-top:6px;background:#f8f8f8;padding:8px 10px;border-radius:8px;font-size:.76rem;color:#555;line-height:1.9}
details ul li{list-style:none}

/* ACTIONS */
.act-form{display:flex;flex-direction:column;gap:5px}
.sel-stat{padding:5px 9px;border:1.5px solid #e0e0e0;border-radius:7px;font-size:.78rem;font-family:'DM Sans',sans-serif;outline:none}
.sel-stat:focus{border-color:var(--gold)}
.btn-row{display:flex;gap:5px}
.btn-upd{padding:5px 11px;background:var(--gold);color:#fff;border:none;border-radius:7px;cursor:pointer;font-size:.76rem;font-weight:700;transition:.2s}
.btn-upd:hover{background:var(--lead)}
.btn-del{padding:5px 9px;background:#fff;color:var(--red);border:1.5px solid #f5c6c6;border-radius:7px;cursor:pointer;font-size:.76rem;transition:.2s}
.btn-del:hover{background:var(--red);color:#fff;border-color:var(--red)}

/* AVATAR */
.av{width:34px;height:34px;border-radius:50%;background:var(--lead);color:var(--gold);
    display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.85rem;flex-shrink:0}
.cust-cell{display:flex;align-items:center;gap:10px}

/* PRODUCT GRID */
.pgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(155px,1fr));gap:14px}
.pcrd{border:1.5px solid #efefef;border-radius:13px;padding:15px;text-align:center;background:#fafafa;transition:.2s}
.pcrd:hover{border-color:var(--gold);background:#fffbe6}
.pcrd img{width:76px;height:76px;object-fit:contain;margin-bottom:8px}
.pcrd h4{font-size:.86rem;font-weight:600;margin-bottom:4px;color:var(--lead)}
.pcrd .price{color:var(--gold);font-weight:800;font-size:.95rem}
.pcrd .desc{font-size:.7rem;color:#aaa;margin-top:4px;line-height:1.3}

/* ANALYTICS */
.bar-row{display:flex;align-items:center;gap:10px;margin-bottom:10px}
.bar-label{width:90px;font-size:.75rem;color:#555;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bar-track{flex:1;background:#f0f0f0;border-radius:6px;height:22px;overflow:hidden}
.bar-fill{height:100%;border-radius:6px;display:flex;align-items:center;padding-left:8px;font-size:.7rem;font-weight:700;min-width:30px}
.bar-val{font-size:.74rem;color:#999;width:70px;text-align:right}

/* RESPONSIVE */
@media(max-width:1100px){.stats{grid-template-columns:repeat(3,1fr)}}
@media(max-width:800px){
  .sb{width:54px}.sb-brand .logo-text,.sb-brand small,.sb nav a span,.sb-foot span{display:none}
  .sb nav a{justify-content:center;padding:14px}.sb nav a i{width:auto}
  .main{margin-left:54px}.stats{grid-template-columns:repeat(2,1fr)}}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sb">
  <div class="sb-brand">
    <span class="logo">Foodie.<span class="logo-text"></span></span>
    <small>Admin Panel</small>
  </div>
  <nav>
    <a href="?tab=orders"    class="<?= $tab==='orders'?'on':'' ?>"><i class="fas fa-receipt"></i> <span>Orders</span></a>
    <a href="?tab=customers" class="<?= $tab==='customers'?'on':'' ?>"><i class="fas fa-users"></i> <span>Customers</span></a>
    <a href="?tab=products"  class="<?= $tab==='products'?'on':'' ?>"><i class="fas fa-burger"></i> <span>Products</span></a>
    <a href="?tab=analytics" class="<?= $tab==='analytics'?'on':'' ?>"><i class="fas fa-chart-bar"></i> <span>Analytics</span></a>
  </nav>
  <div class="sb-foot">
    <a href="logout.php"><i class="fas fa-right-from-bracket"></i> <span>Logout</span></a>
  </div>
</aside>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <h1><?php
      echo match($tab){
        'orders'    => '📋 Orders Management',
        'customers' => '👥 Customers',
        'products'  => '🍔 Products',
        'analytics' => '📊 Analytics',
        default     => 'Dashboard'
      };
    ?></h1>
    <div class="admin-pill"><i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['user_name']) ?></div>
  </div>

  <div class="content">

    <!-- ALERTS -->
    <?php if (($_GET['msg']??'')==='updated'): ?>
      <div class="alert ok"><i class="fas fa-check-circle"></i> Order #<?= (int)($_GET['oid']??0) ?> updated.</div>
    <?php elseif (($_GET['msg']??'')==='deleted'): ?>
      <div class="alert del"><i class="fas fa-trash"></i> Order deleted.</div>
    <?php endif; ?>

    <!-- STAT CARDS -->
    <div class="stats">
      <div class="stat"><div class="ico" style="background:var(--gold)"><i class="fas fa-receipt"></i></div><div class="val"><?= $s['total_orders'] ?></div><div class="lbl">Total Orders</div></div>
      <div class="stat"><div class="ico" style="background:var(--green)"><i class="fas fa-dollar-sign"></i></div><div class="val">$<?= number_format($s['revenue'],0) ?></div><div class="lbl">Revenue</div></div>
      <div class="stat"><div class="ico" style="background:var(--blue)"><i class="fas fa-users"></i></div><div class="val"><?= $s['customers'] ?></div><div class="lbl">Customers</div></div>
      <div class="stat"><div class="ico" style="background:var(--orange)"><i class="fas fa-clock"></i></div><div class="val"><?= $s['pending'] ?></div><div class="lbl">Pending</div></div>
      <div class="stat"><div class="ico" style="background:var(--green)"><i class="fas fa-check-circle"></i></div><div class="val"><?= $s['delivered'] ?></div><div class="lbl">Delivered</div></div>
      <div class="stat"><div class="ico" style="background:#8e44ad"><i class="fas fa-money-bill"></i></div><div class="val"><?= $s['cod'] ?></div><div class="lbl">COD Orders</div></div>
    </div>

    <!-- ========== ORDERS ========== -->
    <?php if ($tab === 'orders'): ?>
    <div class="card">
      <div class="card-title"><i class="fas fa-receipt"></i> All Customer Orders
        <span style="margin-left:auto;font-size:.78rem;color:#aaa;font-weight:400"><?= $orders->num_rows ?> order(s)</span>
      </div>
      <div class="toolbar">
        <input class="search" id="srch" type="text" placeholder="🔍 Search name, phone, email…">
        <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
          <input type="hidden" name="tab" value="orders">
          <select name="sf" class="fsel" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <?php foreach(['pending','confirmed','preparing','out_for_delivery','delivered','cancelled'] as $x): ?>
            <option value="<?= $x ?>" <?= $sf===$x?'selected':'' ?>><?= ucwords(str_replace('_',' ',$x)) ?></option>
            <?php endforeach; ?>
          </select>
          <select name="pmf" class="fsel" onchange="this.form.submit()">
            <option value="">All Payments</option>
            <option value="cod"    <?= $pmf==='cod'?'selected':'' ?>>Cash on Delivery</option>
            <option value="online" <?= $pmf==='online'?'selected':'' ?>>Online / UPI</option>
          </select>
        </form>
      </div>
      <div class="tw">
        <table id="otbl">
          <thead><tr>
            <th>Order</th><th>Customer</th><th>Phone</th><th>Address</th>
            <th>Items</th><th>Total</th><th>Payment</th><th>Pay Status</th>
            <th>Progress</th><th>Status</th><th>Date</th><th>Actions</th>
          </tr></thead>
          <tbody>
          <?php
          if ($orders->num_rows > 0):
            while ($o = $orders->fetch_assoc()):
              $oid   = (int)$o['id'];
              $sitems = $conn->query("
                  SELECT oi.quantity, oi.price, p.name
                  FROM order_items oi JOIN products p ON oi.product_id=p.id
                  WHERE oi.order_id=$oid
              ");
              $sidx = array_search($o['status'], $steps);
          ?>
          <tr>
            <td><strong>#<?= str_pad($oid,4,'0',STR_PAD_LEFT) ?></strong></td>
            <td>
              <div style="font-weight:600"><?= htmlspecialchars($o['customer_name'] ?? $o['uname'] ?? '—') ?></div>
              <?php if ($o['email']): ?><div style="font-size:.72rem;color:#aaa"><?= htmlspecialchars($o['email']) ?></div><?php endif; ?>
            </td>
            <td><?= htmlspecialchars($o['customer_phone'] ?? '—') ?></td>
            <td style="max-width:130px;font-size:.76rem;color:#888"><?= htmlspecialchars($o['customer_address'] ?? '—') ?></td>
            <td>
              <?php if ($sitems && $sitems->num_rows > 0): ?>
              <details><summary><?= $sitems->num_rows ?> item(s)</summary>
              <ul><?php while($it=$sitems->fetch_assoc()): ?>
                <li><?= htmlspecialchars($it['name']) ?> × <?= $it['quantity'] ?> – $<?= number_format($it['price']*$it['quantity'],2) ?></li>
              <?php endwhile; ?></ul></details>
              <?php else: echo '—'; endif; ?>
            </td>
            <td><strong>$<?= number_format($o['total'],2) ?></strong></td>
            <td><span class="b b-<?= $o['payment_method'] ?>"><?= $o['payment_method']==='online'?'💳 Online':'💵 COD' ?></span></td>
            <td><span class="b <?= $o['payment_status']==='paid'?'b-paid':'b-unpaid' ?>"><?= ucfirst($o['payment_status']) ?></span></td>
            <td>
              <?php if ($o['status']==='cancelled'): ?>
                <span style="font-size:.72rem;color:var(--red)">Cancelled</span>
              <?php else: ?>
              <div class="mini-t">
                <?php foreach($steps as $si=>$ss):
                  $done   = ($sidx!==false && $si < $sidx);
                  $active = ($ss===$o['status']);
                ?>
                <div class="md <?= $done?'done':($active?'active':'') ?>"></div>
                <?php endforeach; ?>
                <span class="mlbl"><?= $slabels[$o['status']] ?? '' ?></span>
              </div>
              <?php endif; ?>
            </td>
            <td><span class="b b-<?= $o['status'] ?>"><?= ucwords(str_replace('_',' ',$o['status'])) ?></span></td>
            <td style="white-space:nowrap;font-size:.74rem;color:#aaa"><?= date('d M Y<br>h:i A', strtotime($o['created_at'])) ?></td>
            <td>
              <div class="act-form">
                <form method="POST">
                  <input type="hidden" name="order_id" value="<?= $oid ?>">
                  <select name="new_status" class="sel-stat">
                    <?php foreach(['pending','confirmed','preparing','out_for_delivery','delivered','cancelled'] as $x): ?>
                    <option value="<?= $x ?>" <?= $o['status']===$x?'selected':'' ?>><?= ucwords(str_replace('_',' ',$x)) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <div class="btn-row" style="margin-top:5px">
                    <button type="submit" name="update_status" class="btn-upd">✓ Update</button>
                    <button type="submit" name="delete_order" class="btn-del"
                            onclick="return confirm('Delete order #<?= $oid ?>?')">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </form>
              </div>
            </td>
          </tr>
          <?php endwhile; else: ?>
          <tr><td colspan="12" style="text-align:center;padding:40px;color:#ccc;font-size:.9rem">
            No orders found. <?= $sf||$pmf ? '<a href="?tab=orders" style="color:var(--blue)">Clear filters</a>' : '' ?>
          </td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ========== CUSTOMERS ========== -->
    <?php elseif ($tab === 'customers'): ?>
    <div class="card">
      <div class="card-title"><i class="fas fa-users"></i> Registered Customers
        <span style="margin-left:auto;font-size:.78rem;color:#aaa;font-weight:400"><?= $customers->num_rows ?> accounts</span>
      </div>
      <div class="toolbar">
        <input class="search" id="csrch" type="text" placeholder="🔍 Search name, email, phone…">
      </div>
      <div class="tw">
        <table id="ctbl">
          <thead><tr><th>#</th><th>Customer</th><th>Phone</th><th>Address</th><th>Orders</th><th>Total Spent</th><th>Joined</th></tr></thead>
          <tbody>
          <?php $ci=1; while($c=$customers->fetch_assoc()): ?>
          <tr>
            <td><?= $ci++ ?></td>
            <td><div class="cust-cell">
              <div class="av"><?= strtoupper(substr($c['name']??'U',0,1)) ?></div>
              <div>
                <div style="font-weight:600"><?= htmlspecialchars($c['name']??'—') ?></div>
                <div style="font-size:.72rem;color:#aaa"><?= htmlspecialchars($c['email']) ?></div>
              </div>
            </div></td>
            <td><?= htmlspecialchars($c['phone']??'—') ?></td>
            <td style="font-size:.76rem;color:#aaa;max-width:160px"><?= htmlspecialchars($c['address']??'—') ?></td>
            <td><strong><?= $c['orders'] ?></strong></td>
            <td style="color:var(--green);font-weight:700">$<?= number_format($c['spent'],2) ?></td>
            <td style="font-size:.76rem;color:#aaa"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
          </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ========== PRODUCTS ========== -->
    <?php elseif ($tab === 'products'): ?>
    <div class="card">
      <div class="card-title"><i class="fas fa-burger"></i> Menu Products</div>
      <div class="pgrid">
      <?php while($p=$products->fetch_assoc()): ?>
      <div class="pcrd">
        <img src="<?= htmlspecialchars($p['image']??'') ?>"
             onerror="this.src='https://cdn-icons-png.flaticon.com/512/2830/2830305.png'"
             alt="<?= htmlspecialchars($p['name']) ?>">
        <h4><?= htmlspecialchars($p['name']) ?></h4>
        <div class="price">$<?= number_format($p['price'],2) ?></div>
        <?php if($p['description']): ?><div class="desc"><?= htmlspecialchars($p['description']) ?></div><?php endif; ?>
      </div>
      <?php endwhile; ?>
      </div>
    </div>

    <!-- ========== ANALYTICS ========== -->
    <?php elseif ($tab === 'analytics'):
      $rev7 = $conn->query("SELECT DATE(created_at) d, SUM(total) r, COUNT(*) n FROM orders WHERE status!='cancelled' AND created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY d ASC");
      $top  = $conn->query("SELECT p.name, p.image, SUM(oi.quantity) qty, SUM(oi.quantity*oi.price) rev FROM order_items oi JOIN products p ON oi.product_id=p.id GROUP BY p.id ORDER BY qty DESC LIMIT 6");
      $sbd  = $conn->query("SELECT status, COUNT(*) c FROM orders GROUP BY status ORDER BY c DESC");
    ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px">

      <div class="card" style="margin:0">
        <div class="card-title"><i class="fas fa-chart-line"></i> Revenue – Last 7 Days</div>
        <?php
        $r7rows = $rev7->fetch_all(MYSQLI_ASSOC);
        $maxr   = max(array_column($r7rows,'r') ?: [1]);
        if ($r7rows):
          foreach($r7rows as $r): ?>
        <div class="bar-row">
          <div class="bar-label"><?= date('d M',strtotime($r['d'])) ?></div>
          <div class="bar-track">
            <div class="bar-fill" style="width:<?= max(4,round($r['r']/$maxr*100)) ?>%;background:var(--gold);color:var(--lead)">
              $<?= number_format($r['r'],0) ?>
            </div>
          </div>
          <div class="bar-val"><?= $r['n'] ?> orders</div>
        </div>
        <?php endforeach; else: ?><p style="color:#ccc;font-size:.86rem">No data yet.</p><?php endif; ?>
      </div>

      <div class="card" style="margin:0">
        <div class="card-title"><i class="fas fa-star"></i> Top Products</div>
        <?php
        $trows = $top->fetch_all(MYSQLI_ASSOC);
        $maxq  = max(array_column($trows,'qty') ?: [1]);
        if ($trows):
          foreach($trows as $t): ?>
        <div class="bar-row">
          <div class="bar-label"><?= htmlspecialchars($t['name']) ?></div>
          <div class="bar-track">
            <div class="bar-fill" style="width:<?= max(4,round($t['qty']/$maxq*100)) ?>%;background:var(--lead);color:var(--gold)">
              <?= $t['qty'] ?> sold
            </div>
          </div>
          <div class="bar-val">$<?= number_format($t['rev'],0) ?></div>
        </div>
        <?php endforeach; else: ?><p style="color:#ccc;font-size:.86rem">No data yet.</p><?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-title"><i class="fas fa-chart-pie"></i> Order Status Breakdown</div>
      <div style="display:flex;gap:14px;flex-wrap:wrap">
      <?php
      $colors=['pending'=>'#856404','confirmed'=>'#0f5132','preparing'=>'#084298',
               'out_for_delivery'=>'#7d3c03','delivered'=>'#155724','cancelled'=>'#721c24'];
      while($sb=$sbd->fetch_assoc()): ?>
      <div style="background:#f9f9f9;border-radius:12px;padding:16px 22px;text-align:center;min-width:110px">
        <div style="font-size:1.7rem;font-weight:800;color:<?= $colors[$sb['status']]??'#333' ?>"><?= $sb['c'] ?></div>
        <div style="font-size:.72rem;font-weight:700;color:#888;margin-top:3px"><?= ucwords(str_replace('_',' ',$sb['status'])) ?></div>
      </div>
      <?php endwhile; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<script>
// Live search for orders
const srch = document.getElementById('srch');
if (srch) srch.addEventListener('input', () => {
    const q = srch.value.toLowerCase();
    document.querySelectorAll('#otbl tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
// Live search for customers
const csrch = document.getElementById('csrch');
if (csrch) csrch.addEventListener('input', () => {
    const q = csrch.value.toLowerCase();
    document.querySelectorAll('#ctbl tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
// Auto-dismiss alerts
setTimeout(() => document.querySelectorAll('.alert').forEach(a=>a.style.display='none'), 4000);
// Auto-refresh orders tab every 30s to pick up new orders
<?php if ($tab==='orders'): ?>
setTimeout(() => location.reload(), 30000);
<?php endif; ?>
</script>
</body>
</html>