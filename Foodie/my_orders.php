<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=my_orders.php");
    exit;
}
if ($_SESSION['role'] === 'admin') {
    header("Location: admin.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$orders = $conn->query("
    SELECT id, total, status, payment_method, payment_status,
           customer_name, customer_phone, customer_address, created_at
    FROM orders
    WHERE user_id = $user_id
    ORDER BY created_at DESC
");

$steps  = ['pending','confirmed','preparing','out_for_delivery','delivered'];
$labels = [
    'pending'          => 'Order Placed',
    'confirmed'        => 'Confirmed',
    'preparing'        => 'Preparing',
    'out_for_delivery' => 'Out for Delivery',
    'delivered'        => 'Delivered',
    'cancelled'        => 'Cancelled',
];
$icons = [
    'pending'          => '📋',
    'confirmed'        => '✅',
    'preparing'        => '👨‍🍳',
    'out_for_delivery' => '🛵',
    'delivered'        => '🎉',
    'cancelled'        => '❌',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Orders – Foodie</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--gold:#D4AF37;--lead:#1a1a2e;--soft:#f0f2f7}
body{background:var(--soft);font-family:'DM Sans',sans-serif;min-height:100vh}

/* NAV */
.nav{background:var(--lead);height:62px;display:flex;align-items:center;justify-content:space-between;padding:0 28px;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,.3)}
.nav .logo{font-family:'Playfair Display',serif;font-size:1.6rem;color:var(--gold);text-decoration:none}
.nav .links{display:flex;align-items:center;gap:8px}
.nav .links a{color:#bbb;text-decoration:none;font-size:.86rem;padding:7px 15px;border-radius:18px;transition:.2s;font-weight:500}
.nav .links a:hover,.nav .links a.active{background:rgba(212,175,55,.15);color:var(--gold)}
.nav .user{display:flex;align-items:center;gap:10px;font-size:.85rem;color:#aaa}
.nav .user strong{color:var(--gold)}
.nav .user a{color:#888;text-decoration:none;font-size:.78rem;background:rgba(255,255,255,.07);padding:5px 12px;border-radius:14px;transition:.2s}
.nav .user a:hover{background:rgba(212,175,55,.2);color:var(--gold)}

/* PAGE */
.page{max-width:860px;margin:32px auto;padding:0 18px 60px}
.page-title{font-family:'Playfair Display',serif;font-size:1.9rem;color:var(--lead);margin-bottom:4px}
.page-sub{color:#888;font-size:.88rem;margin-bottom:22px}

/* FILTERS */
.filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px}
.filter-btn{padding:7px 16px;background:#fff;border:1.5px solid #ddd;border-radius:20px;font-size:.8rem;font-weight:600;color:#666;cursor:pointer;transition:.2s}
.filter-btn:hover,.filter-btn.on{background:var(--gold);border-color:var(--gold);color:#fff}

/* REFRESH */
.refresh-row{display:flex;justify-content:flex-end;align-items:center;gap:10px;margin-bottom:14px;font-size:.8rem;color:#aaa}
.refresh-btn{display:flex;align-items:center;gap:5px;background:#fff;border:1.5px solid #ddd;border-radius:18px;padding:6px 14px;cursor:pointer;font-size:.8rem;color:#666;transition:.2s}
.refresh-btn:hover{border-color:var(--gold);color:var(--gold)}

/* EMPTY */
.empty{background:#fff;border-radius:18px;padding:60px 30px;text-align:center;box-shadow:0 3px 16px rgba(0,0,0,.06)}
.empty .big{font-size:3.5rem;margin-bottom:14px}
.empty h2{font-family:'Playfair Display',serif;font-size:1.4rem;color:var(--lead);margin-bottom:8px}
.empty p{color:#aaa;margin-bottom:22px;font-size:.9rem}
.empty a{background:var(--lead);color:var(--gold);padding:12px 26px;border-radius:9px;text-decoration:none;font-weight:700;transition:.2s}
.empty a:hover{background:var(--gold);color:#fff}

/* ORDER CARD */
.order-card{background:#fff;border-radius:18px;margin-bottom:22px;box-shadow:0 3px 16px rgba(0,0,0,.07);overflow:hidden;transition:.2s}
.order-card:hover{box-shadow:0 6px 24px rgba(0,0,0,.1)}

.card-head{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #f2f2f2;flex-wrap:wrap;gap:8px}
.order-num{font-weight:800;color:var(--lead);font-size:.95rem}
.order-date{font-size:.76rem;color:#aaa;margin-top:2px}
.badges{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.badge{display:inline-flex;align-items:center;gap:4px;padding:4px 11px;border-radius:18px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;white-space:nowrap}
.b-pending         {background:#fff3cd;color:#856404}
.b-confirmed       {background:#d1e7dd;color:#0f5132}
.b-preparing       {background:#cfe2ff;color:#084298}
.b-out_for_delivery{background:#fde8d8;color:#7d3c03}
.b-delivered       {background:#d4edda;color:#155724}
.b-cancelled       {background:#f8d7da;color:#721c24}
.b-cod             {background:#e9ecef;color:#555}
.b-online          {background:#d1ecf1;color:#0c5460}
.b-paid            {background:#d4edda;color:#155724}
.b-pend            {background:#fff3cd;color:#856404}

/* TRACKER */
.card-body{padding:22px}
.tracker{display:flex;align-items:flex-start;justify-content:space-between;position:relative;margin-bottom:22px}
.tracker::before{content:'';position:absolute;top:19px;left:calc(10% + 10px);right:calc(10% + 10px);height:3px;background:#ebebeb;z-index:0}
.tracker-fill{position:absolute;top:19px;left:calc(10% + 10px);height:3px;background:var(--gold);z-index:1;transition:width .5s}
.step{flex:1;display:flex;flex-direction:column;align-items:center;gap:7px;position:relative;z-index:2}
.step-circle{width:38px;height:38px;border-radius:50%;border:2.5px solid #ddd;background:#fff;display:flex;align-items:center;justify-content:center;font-size:.95rem;transition:.3s}
.step.done  .step-circle{background:var(--gold);border-color:var(--gold);color:#fff}
.step.active .step-circle{border-color:var(--gold);box-shadow:0 0 0 4px rgba(212,175,55,.2)}
.step-label{font-size:.68rem;font-weight:600;color:#ccc;text-align:center;line-height:1.3}
.step.done .step-label,.step.active .step-label{color:var(--lead)}
.cancelled-note{background:#fff0f0;border:1.5px solid #f5c6c6;border-radius:10px;padding:12px 16px;text-align:center;color:#c0392b;font-weight:600;font-size:.88rem;margin-bottom:18px}

/* ITEMS */
.items-heading{font-size:.72rem;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px}
.item-row{display:flex;align-items:center;gap:12px;padding:8px 0;border-bottom:1px solid #f5f5f5}
.item-row:last-child{border:none}
.item-img{width:44px;height:44px;object-fit:contain;border-radius:8px;background:#f9f9f9;padding:4px}
.item-info{flex:1}
.item-name{font-weight:600;font-size:.88rem;color:var(--lead)}
.item-qty{font-size:.76rem;color:#aaa}
.item-price{font-weight:700;color:var(--gold);font-size:.88rem}

/* FOOTER */
.card-foot{display:flex;align-items:center;justify-content:space-between;padding:14px 22px;background:#fafafa;border-top:1px solid #f2f2f2;flex-wrap:wrap;gap:8px}
.order-total{font-size:1rem;font-weight:800;color:var(--lead)}
.order-addr{font-size:.76rem;color:#aaa;max-width:380px}
</style>
</head>
<body>

<nav class="nav">
  <a href="index.php" class="logo">Foodie.</a>
  <div class="links">
    <a href="index.php">🍔 Menu</a>
    <a href="my_orders.php" class="active">📦 My Orders</a>
  </div>
  <div class="user">
    Hi, <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
    <a href="logout.php">Logout</a>
  </div>
</nav>

<div class="page">
  <div class="page-title">My Orders</div>
  <div class="page-sub">All orders placed under your account</div>

  <?php if ($orders->num_rows === 0): ?>
  <div class="empty">
    <div class="big">🛍️</div>
    <h2>No orders yet</h2>
    <p>You haven't placed any orders. Explore the menu and order your first meal!</p>
    <a href="index.php#menu">Browse Menu</a>
  </div>

  <?php else: ?>
  <div class="filters">
    <button class="filter-btn on" onclick="filter('all',this)">All (<?= $orders->num_rows ?>)</button>
    <button class="filter-btn" onclick="filter('pending',this)">⏳ Pending</button>
    <button class="filter-btn" onclick="filter('confirmed',this)">✅ Confirmed</button>
    <button class="filter-btn" onclick="filter('preparing',this)">👨‍🍳 Preparing</button>
    <button class="filter-btn" onclick="filter('out_for_delivery',this)">🛵 On the way</button>
    <button class="filter-btn" onclick="filter('delivered',this)">🎉 Delivered</button>
    <button class="filter-btn" onclick="filter('cancelled',this)">❌ Cancelled</button>
  </div>

  <div class="refresh-row">
    <span id="countdown-label">Auto-refresh in 30s</span>
    <button class="refresh-btn" onclick="location.reload()">
      <i class="fas fa-rotate-right"></i> Refresh Now
    </button>
  </div>

  <div id="orders-wrap">
  <?php
  $orders->data_seek(0);
  while ($o = $orders->fetch_assoc()):
    $oid    = (int)$o['id'];
    $status = $o['status'];
    $sidx   = array_search($status, $steps);
    $fill   = 0;
    if ($sidx !== false) $fill = $sidx / (count($steps) - 1) * 100;
    if ($status === 'cancelled') $fill = 0;

    $items = $conn->query("
        SELECT oi.quantity, oi.price, p.name, p.image
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = $oid
    ");
  ?>
  <div class="order-card" data-status="<?= $status ?>">

    <div class="card-head">
      <div>
        <div class="order-num">Order #<?= str_pad($oid,4,'0',STR_PAD_LEFT) ?></div>
        <div class="order-date"><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></div>
      </div>
      <div class="badges">
        <span class="badge b-<?= $status ?>"><?= ($icons[$status]??'') ?> <?= ucwords(str_replace('_',' ',$status)) ?></span>
        <span class="badge b-<?= $o['payment_method'] ?>"><?= $o['payment_method']==='online'?'💳 Online':'💵 COD' ?></span>
        <span class="badge <?= $o['payment_status']==='paid'?'b-paid':'b-pend' ?>"><?= ucfirst($o['payment_status']) ?></span>
      </div>
    </div>

    <div class="card-body">
      <?php if ($status === 'cancelled'): ?>
        <div class="cancelled-note">❌ This order was cancelled</div>
      <?php else: ?>
      <div class="tracker">
        <div class="tracker-fill" style="width:calc(<?= $fill ?>% - 20px)"></div>
        <?php foreach ($steps as $si => $step):
          $done   = ($sidx !== false && $si < $sidx);
          $active = ($step === $status);
          $cls    = $done ? 'done' : ($active ? 'active' : '');
        ?>
        <div class="step <?= $cls ?>">
          <div class="step-circle">
            <?php if ($done):   ?><i class="fas fa-check" style="font-size:.7rem"></i>
            <?php elseif ($active): ?><span><?= $icons[$step]??($si+1) ?></span>
            <?php else: ?><span style="font-size:.7rem;color:#ccc"><?= $si+1 ?></span>
            <?php endif; ?>
          </div>
          <div class="step-label"><?= $labels[$step] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="items-heading">Items Ordered</div>
      <?php if ($items && $items->num_rows > 0):
        while ($it = $items->fetch_assoc()): ?>
      <div class="item-row">
        <img class="item-img" src="<?= htmlspecialchars($it['image']??'') ?>"
             onerror="this.src='https://cdn-icons-png.flaticon.com/512/2830/2830305.png'"
             alt="<?= htmlspecialchars($it['name']) ?>">
        <div class="item-info">
          <div class="item-name"><?= htmlspecialchars($it['name']) ?></div>
          <div class="item-qty">Qty: <?= $it['quantity'] ?></div>
        </div>
        <div class="item-price">$<?= number_format($it['price']*$it['quantity'],2) ?></div>
      </div>
      <?php endwhile; endif; ?>
    </div>

    <div class="card-foot">
      <div class="order-total">Total: $<?= number_format($o['total'],2) ?></div>
      <div class="order-addr">📍 <?= htmlspecialchars($o['customer_address']??'—') ?></div>
    </div>
  </div>
  <?php endwhile; ?>
  </div>
  <?php endif; ?>
</div>

<script>
function filter(status, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('on'));
    btn.classList.add('on');
    document.querySelectorAll('.order-card').forEach(c => {
        c.style.display = (status==='all' || c.dataset.status===status) ? '' : 'none';
    });
}

// Auto-refresh countdown
let secs = 30;
const lbl = document.getElementById('countdown-label');
setInterval(() => {
    secs--;
    if (lbl) lbl.textContent = `Auto-refresh in ${secs}s`;
    if (secs <= 0) location.reload();
}, 1000);
</script>
</body>
</html>