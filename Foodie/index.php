<?php
session_start();
// Redirect admin to admin panel
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin.php");
    exit;
}
$logged_in = isset($_SESSION['user_id']);
$user_name    = htmlspecialchars($_SESSION['user_name']    ?? '');
$user_phone   = htmlspecialchars($_SESSION['user_phone']   ?? '');
$user_address = htmlspecialchars($_SESSION['user_address'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie | Food Delivery</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css"/>
    <link rel="stylesheet" href="style.css">
    <style>
    /* ── Logged-in nav ── */
    .user-greeting{display:flex;align-items:center;gap:10px;font-size:.87rem;font-weight:600}
    .user-greeting i{color:var(--gold-color);font-size:1.2rem}
    .user-greeting .greet-name{color:var(--lead-color)}
    .orders-link{display:flex;align-items:center;gap:6px;background:var(--gold-color);color:#fff;padding:7px 16px;border-radius:20px;font-size:.82rem;font-weight:700;text-decoration:none;transition:all .2s}
    .orders-link:hover{background:var(--lead-color);color:var(--gold-color)}
    .logout-link{color:#999;font-size:.78rem;text-decoration:none;border:1.5px solid #ddd;padding:5px 12px;border-radius:14px;transition:.2s}
    .logout-link:hover{color:#e74c3c;border-color:#e74c3c}

    /* ── Modal ── */
    .modal{position:fixed;inset:0;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;z-index:5000}
    .modal.hidden{display:none!important}
    .modal-content{background:#fff;border-radius:18px;padding:30px;width:90%;max-width:430px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2)}

    /* ── Payment UI ── */
    .modal-content h2{font-size:1.25rem;margin-bottom:18px;color:#1a1a2e}
    .field-group{margin-bottom:12px}
    .field-group label{display:block;font-size:.74rem;font-weight:700;color:#666;text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px}
    .field-group input{width:100%;padding:10px 13px;border:1.5px solid #e0e0e0;border-radius:9px;font-size:.9rem;font-family:inherit;transition:.2s;box-sizing:border-box}
    .field-group input:focus{border-color:#f2bd12;outline:none;background:#fffbe6}
    .divider{border:none;border-top:1px solid #f0f0f0;margin:16px 0}
    .pay-tabs{display:flex;gap:10px;margin-bottom:16px}
    .pay-tab{flex:1;border:2px solid #eee;border-radius:12px;padding:14px 8px;text-align:center;cursor:pointer;transition:.2s;font-size:.85rem;font-weight:600;color:#888}
    .pay-tab.active{border-color:#f2bd12;background:#fffbe6;color:#333}
    .pay-tab i{display:block;font-size:1.4rem;margin-bottom:6px;color:#f2bd12}
    .pay-section{display:none;padding:14px;background:#f9f9f9;border-radius:10px;margin-bottom:14px;text-align:center}
    .pay-section.active{display:block}
    .pay-section img{margin-bottom:6px}
    .pay-section p{font-size:.82rem;color:#888}
    .pay-section .cod-msg{font-size:.9rem;color:#555;text-align:left;line-height:1.6}
    .modal-actions{display:flex;gap:10px;margin-top:18px}
    .modal-actions button{flex:1;padding:12px;border:none;border-radius:9px;font-size:.95rem;font-weight:700;cursor:pointer;transition:.2s}
    .btn-cancel{background:#f0f0f0;color:#666}
    .btn-cancel:hover{background:#e0e0e0}
    .btn-confirm{background:#27ae60;color:#fff}
    .btn-confirm:hover{background:#219a52}
    .btn-confirm:disabled{background:#ccc;cursor:not-allowed}

    /* ── Success modal ── */
    .success-content{text-align:center;padding:40px 30px}
    .checkmark{font-size:3.5rem;margin-bottom:14px}
    .success-content h2{font-size:1.5rem;margin-bottom:8px;color:#1a1a2e}
    .success-content p{color:#888;font-size:.9rem;margin-bottom:6px}
    .success-actions{display:flex;gap:10px;justify-content:center;margin-top:20px;flex-wrap:wrap}
    .success-actions a,.success-actions button{padding:11px 22px;border-radius:9px;font-size:.9rem;font-weight:700;cursor:pointer;text-decoration:none;border:none;transition:.2s}
    .btn-track{background:#1a1a2e;color:#D4AF37}
    .btn-track:hover{background:#D4AF37;color:#fff}
    .btn-continue{background:#f0f0f0;color:#555}
    .btn-continue:hover{background:#e0e0e0}

    /* ── Login prompt overlay ── */
    #login-prompt{position:fixed;inset:0;background:rgba(0,0,0,.65);display:flex;align-items:center;justify-content:center;z-index:6000}
    #login-prompt.hidden{display:none!important}
    .login-prompt-box{background:#fff;border-radius:18px;padding:40px 36px;text-align:center;max-width:360px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.2)}
    .login-prompt-box .icon{font-size:3rem;margin-bottom:14px}
    .login-prompt-box h3{font-size:1.3rem;color:#1a1a2e;margin-bottom:8px}
    .login-prompt-box p{color:#888;font-size:.88rem;margin-bottom:22px}
    .login-prompt-box .btn-login{display:block;background:#D4AF37;color:#fff;padding:13px;border-radius:9px;font-weight:700;text-decoration:none;font-size:1rem;margin-bottom:10px;transition:.2s}
    .login-prompt-box .btn-login:hover{background:#1a1a2e}
    .login-prompt-box .btn-dismiss{background:none;border:none;color:#aaa;font-size:.82rem;cursor:pointer;text-decoration:underline}
    </style>
</head>
<body>

<div class="toast" id="toast"></div>

<!-- ===== HEADER ===== -->
<header>
  <div class="wrapper nav-container">
    <a href="index.php" class="logo">Foodie.</a>
    <ul class="nav-list" id="nav-list">
      <li><a href="#home"     class="nav-link active">Home</a></li>
      <li><a href="#menu"     class="nav-link">Menu</a></li>
      <li><a href="#services" class="nav-link">Services</a></li>
      <li><a href="#reviews"  class="nav-link">Reviews</a></li>
    </ul>
    <div class="nav-icons">
      <div class="cart-icon-container" id="cart-btn">
        <i class="fas fa-shopping-bag cart-icon"></i>
        <div class="cart-count" id="cart-count">0</div>
      </div>
      <div class="menu-toggle" id="menu-btn"><i class="fas fa-bars"></i></div>
      <?php if ($logged_in): ?>
      <div class="user-greeting">
        <i class="fas fa-user-circle"></i>
        <span class="greet-name"><?= $user_name ?></span>
        <a href="my_orders.php" class="orders-link"><i class="fas fa-box-open"></i> My Orders</a>
        <a href="logout.php" class="logout-link">Logout</a>
      </div>
      <?php else: ?>
      <a href="login.php" class="btn" style="padding:.5rem 1.5rem;font-size:.9rem">Sign In</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<!-- ===== CART SIDEBAR ===== -->
<div class="cart-sidebar" id="cart-sidebar">
  <div class="cart-header">
    <span class="cart-title">Your Cart</span>
    <i class="fas fa-times close-cart" id="close-cart"></i>
  </div>
  <div class="cart-items" id="cart-items">
    <div class="text-center" style="margin-top:2rem;color:var(--gray)">Your cart is empty</div>
  </div>
  <div class="cart-footer">
    <div class="total-price"><span>Total</span><span id="cart-total">$0.00</span></div>
    <button id="checkout-btn" class="btn checkout-btn">Checkout</button>
  </div>
</div>

<!-- ===== MAIN ===== -->
<main>
  <!-- HERO -->
  <section class="hero wrapper" id="home">
    <div class="hero-content">
      <h1 class="hero-title">Enjoy Your <span>Delicious</span> Food</h1>
      <p class="hero-desc">Experience the best food delivery service in town. Fresh, hot, and tasty meals delivered right to your doorstep within minutes.</p>
      <a href="#menu" class="btn">Order Now</a>
    </div>
    <div class="hero-img"><img src="images/burger.png" alt="Burger"></div>
  </section>

  <!-- SERVICES -->
  <section class="section-padding wrapper" id="services">
    <div class="text-center">
      <h5 class="heading-sub">How it works</h5>
      <h2 class="heading-main">Our Services</h2>
    </div>
    <div class="services-grid">
      <div class="service-card">
        <div class="service-img"><img src="images/easy-to-order.png" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2830/2830305.png'" alt="Order"></div>
        <h3 class="service-title">Easy To Order</h3>
        <p class="service-desc">Order food with a single click from a wide variety of options.</p>
      </div>
      <div class="service-card">
        <div class="service-img"><img src="images/fast-delivery.png" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2555/2555013.png'" alt="Delivery"></div>
        <h3 class="service-title">Fast Delivery</h3>
        <p class="service-desc">We ensure your food arrives hot and fresh in record time.</p>
      </div>
      <div class="service-card">
        <div class="service-img"><img src="images/best-quality.png" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2555/2555160.png'" alt="Quality"></div>
        <h3 class="service-title">Best Quality</h3>
        <p class="service-desc">Our chefs use only the finest ingredients to prepare your meals.</p>
      </div>
    </div>
  </section>

  <!-- MENU -->
  <section class="section-padding wrapper" id="menu">
    <div class="text-center">
      <h5 class="heading-sub">Our Menu</h5>
      <h2 class="heading-main">Most Popular</h2>
    </div>
    <div class="menu-grid" id="menu-grid"></div>
  </section>

  <!-- REVIEWS -->
  <section class="section-padding wrapper" id="reviews">
    <div class="text-center">
      <h5 class="heading-sub">Testimonials</h5>
      <h2 class="heading-main">What They Say</h2>
    </div>
    <div class="swiper mySwiper">
      <div class="swiper-wrapper">
        <div class="swiper-slide"><div class="review-card">
          <img src="images/profile1.jpeg" onerror="this.src='https://randomuser.me/api/portraits/men/32.jpg'" class="reviewer-img" alt="">
          <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
          <h3 class="service-title">John Doe</h3>
          <p class="review-text">"Absolutely delicious! The delivery was super fast and the food was hot."</p>
        </div></div>
        <div class="swiper-slide"><div class="review-card">
          <img src="images/profile2.jpeg" onerror="this.src='https://randomuser.me/api/portraits/women/44.jpg'" class="reviewer-img" alt="">
          <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div>
          <h3 class="service-title">Jane Smith</h3>
          <p class="review-text">"Best burgers in town. Highly recommend the Double Beef Burger!"</p>
        </div></div>
        <div class="swiper-slide"><div class="review-card">
          <img src="images/profile3.jpeg" onerror="this.src='https://randomuser.me/api/portraits/men/85.jpg'" class="reviewer-img" alt="">
          <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
          <h3 class="service-title">Mike Ross</h3>
          <p class="review-text">"Great service and amazing food quality. Will order again!"</p>
        </div></div>
      </div>
      <div class="swiper-pagination"></div>
    </div>
  </section>
</main>

<!-- FOOTER -->
<footer>
  <div class="wrapper footer-grid">
    <div class="footer-col">
      <a href="#" class="logo">Foodie.</a>
      <p style="margin-top:1rem;color:var(--gray);line-height:1.6">Our mission is to satisfy your cravings with the best food in town, delivered fast.</p>
      <div class="social-links" style="margin-top:1rem">
        <a href="https://www.instagram.com" target="_blank"><i class="fab fa-instagram"></i></a>
        <a href="https://www.twitter.com"   target="_blank"><i class="fab fa-twitter"></i></a>
        <a href="https://www.google.com"    target="_blank"><i class="fab fa-google"></i></a>
        <a href="https://www.facebook.com"  target="_blank"><i class="fab fa-facebook-f"></i></a>
      </div>
    </div>
    <div class="footer-col"><h3>Company</h3>
      <ul class="footer-links"><li><a href="#">About Us</a></li><li><a href="#menu">Our Menu</a></li><li><a href="#">Contact</a></li></ul>
    </div>
    <div class="footer-col"><h3>Get Help</h3>
      <ul class="footer-links"><li><a href="#">FAQ</a></li><li><a href="#">Shipping</a></li><li><a href="#">Returns</a></li></ul>
    </div>
    <div class="footer-col"><h3>Contact</h3>
      <ul class="footer-links"><li><a href="#">+1 234 567 890</a></li><li><a href="#">support@foodie.com</a></li><li><a href="#">New York, NY</a></li></ul>
    </div>
  </div>
  <div class="text-center" style="margin-top:3rem;color:var(--gray)">&copy; 2025 Foodie. All Rights Reserved.</div>
</footer>

<!-- ===== LOGIN PROMPT (shown if not logged in and tries to checkout) ===== -->
<div id="login-prompt" class="hidden">
  <div class="login-prompt-box">
    <div class="icon">🔐</div>
    <h3>Login Required</h3>
    <p>You need to be logged in to place an order. Sign in or create a free account to continue.</p>
    <a href="login.php" class="btn-login">Login / Sign In</a>
    <button class="btn-dismiss" onclick="document.getElementById('login-prompt').classList.add('hidden')">Continue Browsing</button>
  </div>
</div>

<!-- ===== PAYMENT MODAL ===== -->
<div id="payment-modal" class="modal hidden">
  <div class="modal-content">
    <h2>🛒 Place Your Order</h2>

    <div class="field-group">
      <label>Full Name *</label>
      <input type="text" id="cust-name" placeholder="Your name" value="<?= $user_name ?>">
    </div>
    <div class="field-group">
      <label>Phone Number *</label>
      <input type="tel" id="cust-phone" placeholder="10-digit number" value="<?= $user_phone ?>">
    </div>
    <div class="field-group">
      <label>Delivery Address *</label>
      <input type="text" id="cust-address" placeholder="Your full address" value="<?= $user_address ?>">
    </div>

    <hr class="divider">
    <p style="font-size:.85rem;font-weight:700;color:#333;margin-bottom:10px">Payment Method</p>
    <div class="pay-tabs">
      <div class="pay-tab active" id="tab-online" onclick="setPayMethod('online')">
        <i class="fas fa-qrcode"></i>UPI / Scan
      </div>
      <div class="pay-tab" id="tab-cod" onclick="setPayMethod('cod')">
        <i class="fas fa-money-bill-wave"></i>Cash on Delivery
      </div>
    </div>

    <div class="pay-section active" id="section-online">
      <img src="images/scanner.jpeg" alt="UPI QR">
      <p>Scan with GPay, PhonePe, Paytm or any UPI app</p>
    </div>
    <div class="pay-section" id="section-cod">
      <p class="cod-msg">💵 <strong>Pay in cash</strong> when your rider arrives. No advance payment needed.</p>
    </div>

    <div class="modal-actions">
      <button class="btn-cancel"  onclick="closePayModal()">Cancel</button>
      <button class="btn-confirm" id="confirm-btn" onclick="submitOrder()">
        <span id="confirm-btn-text">Confirm Order</span>
      </button>
    </div>
  </div>
</div>

<!-- ===== SUCCESS MODAL ===== -->
<div id="order-success" class="modal hidden">
  <div class="modal-content success-content">
    <div class="checkmark">🎉</div>
    <h2>Order Placed!</h2>
    <p>Your food is being prepared and will be delivered soon.</p>
    <p id="order-info" style="font-size:.82rem;color:#aaa"></p>
    <div class="success-actions">
      <a href="my_orders.php" class="btn-track"><i class="fas fa-box-open"></i> Track My Order</a>
      <button class="btn-continue" onclick="closeSuccess()">Continue Shopping</button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
<script>
// Pass PHP session state to JS
const IS_LOGGED_IN = <?= $logged_in ? 'true' : 'false' ?>;

// ── Product data ──────────────────────────────────────────────
const PRODUCTS = [
    {id:1, name:'Double Beef Burger', price:12.50, img:'images/burger.png'},
    {id:2, name:'Cheese Pizza',       price:15.00, img:'images/pizza.png'},
    {id:3, name:'Fried Chicken',      price:10.00, img:'images/fried-chicken.png'},
    {id:4, name:'Grilled Sandwich',   price:8.50,  img:'images/sandwich.png'},
    {id:5, name:'Chicken Roll',       price:9.00,  img:'images/chicken-roll.png'},
    {id:6, name:'Italian Spaghetti',  price:14.00, img:'images/spaghetti.png'},
    {id:7, name:'Spring Roll',        price:7.00,  img:'images/spring-roll.png'},
    {id:8, name:'Lasagna',            price:11.50, img:'images/lasagna.png'}
];

let cart = [];
let payMethod = 'online';

// ── Render menu ───────────────────────────────────────────────
const menuGrid = document.getElementById('menu-grid');
PRODUCTS.forEach(p => {
    const card = document.createElement('div');
    card.className = 'food-card';
    card.innerHTML = `
        <img src="${p.img}" class="food-img" alt="${p.name}"
             onerror="this.src='https://cdn-icons-png.flaticon.com/512/2830/2830305.png'">
        <h3 class="food-title">${p.name}</h3>
        <span class="food-price">$${p.price.toFixed(2)}</span>
        <button class="btn" onclick="addToCart(${p.id})">Add to Cart</button>
    `;
    menuGrid.appendChild(card);
});

// ── Cart ──────────────────────────────────────────────────────
function addToCart(id) {
    if (cart.some(i => i.id === id)) { showToast('Already in cart!', true); return; }
    const p = PRODUCTS.find(x => x.id === id);
    cart.push({...p, qty:1});
    renderCart();
    showToast('Added to cart 🛒');
    document.getElementById('cart-sidebar').classList.add('active');
}

function changeQty(action, id) {
    cart = cart.map(i => i.id === id ? {...i, qty: action==='plus' ? i.qty+1 : i.qty-1} : i)
               .filter(i => i.qty > 0);
    renderCart();
}

function renderCart() {
    const el = document.getElementById('cart-items');
    el.innerHTML = '';
    if (!cart.length) {
        el.innerHTML = '<div class="text-center" style="margin-top:2rem;color:var(--gray)">Your cart is empty</div>';
        document.getElementById('cart-total').innerText = '$0.00';
        document.getElementById('cart-count').innerText = '0';
        return;
    }
    let total = 0, count = 0;
    cart.forEach(item => {
        total += item.price * item.qty;
        count += item.qty;
        const d = document.createElement('div');
        d.className = 'cart-item';
        d.innerHTML = `
            <img src="${item.img}" class="cart-item-img" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2830/2830305.png'">
            <div class="cart-item-details">
                <h4 class="cart-item-title">${item.name}</h4>
                <span class="cart-item-price">$${item.price.toFixed(2)}</span>
            </div>
            <div class="cart-actions">
                <div class="qty-btn" onclick="changeQty('minus',${item.id})"><i class="fas fa-minus"></i></div>
                <span>${item.qty}</span>
                <div class="qty-btn" onclick="changeQty('plus',${item.id})"><i class="fas fa-plus"></i></div>
            </div>`;
        el.appendChild(d);
    });
    document.getElementById('cart-total').innerText = '$' + total.toFixed(2);
    document.getElementById('cart-count').innerText = count;
}

// ── Toast ────────────────────────────────────────────────────
function showToast(msg, isErr=false) {
    const t = document.getElementById('toast');
    t.innerText = msg;
    t.style.background = isErr ? '#e74c3c' : '#212121';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2500);
}

// ── Cart sidebar ─────────────────────────────────────────────
document.getElementById('cart-btn').addEventListener('click', () =>
    document.getElementById('cart-sidebar').classList.add('active'));
document.getElementById('close-cart').addEventListener('click', () =>
    document.getElementById('cart-sidebar').classList.remove('active'));

// ── Mobile nav ───────────────────────────────────────────────
const menuBtn = document.getElementById('menu-btn');
if (menuBtn) menuBtn.addEventListener('click', () => {
    document.getElementById('nav-list').classList.toggle('active');
    const i = menuBtn.querySelector('i');
    i.classList.toggle('fa-bars');
    i.classList.toggle('fa-times');
});

// ── Checkout button ──────────────────────────────────────────
document.getElementById('checkout-btn').addEventListener('click', () => {
    if (!cart.length) { showToast('Your cart is empty!', true); return; }

    // Force login
    if (!IS_LOGGED_IN) {
        document.getElementById('cart-sidebar').classList.remove('active');
        document.getElementById('login-prompt').classList.remove('hidden');
        return;
    }

    document.getElementById('cart-sidebar').classList.remove('active');
    document.getElementById('payment-modal').classList.remove('hidden');
});

// ── Payment method toggle ────────────────────────────────────
function setPayMethod(method) {
    payMethod = method;
    document.getElementById('tab-online').classList.toggle('active', method==='online');
    document.getElementById('tab-cod').classList.toggle('active',    method==='cod');
    document.getElementById('section-online').classList.toggle('active', method==='online');
    document.getElementById('section-cod').classList.toggle('active',    method==='cod');
}

function closePayModal() {
    document.getElementById('payment-modal').classList.add('hidden');
}

function closeSuccess() {
    document.getElementById('order-success').classList.add('hidden');
}

// ── Submit order ─────────────────────────────────────────────
async function submitOrder() {
    const name    = document.getElementById('cust-name').value.trim();
    const phone   = document.getElementById('cust-phone').value.trim();
    const address = document.getElementById('cust-address').value.trim();

    if (!name)    { showToast('Enter your name', true);    document.getElementById('cust-name').focus();    return; }
    if (!phone)   { showToast('Enter phone number', true); document.getElementById('cust-phone').focus();   return; }
    if (!address) { showToast('Enter delivery address', true); document.getElementById('cust-address').focus(); return; }

    const btn = document.getElementById('confirm-btn');
    const txt = document.getElementById('confirm-btn-text');
    btn.disabled = true;
    txt.innerText = 'Placing order...';

    const payload = {
        cart:             cart.map(i => ({id:i.id, qty:i.qty})),
        payment_method:   payMethod,
        customer_name:    name,
        customer_phone:   phone,
        customer_address: address
    };

    try {
        const res  = await fetch('place_order.php', {
            method:  'POST',
            headers: {'Content-Type':'application/json'},
            body:    JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.status === 'success') {
            closePayModal();
            document.getElementById('order-success').classList.remove('hidden');
            document.getElementById('order-info').innerHTML =
                `Order <strong>#${String(data.order_id).padStart(4,'0')}</strong> · Total <strong>$${data.total}</strong>`;
            cart = [];
            renderCart();
        } else if (data.status === 'not_logged_in') {
            closePayModal();
            document.getElementById('login-prompt').classList.remove('hidden');
        } else {
            showToast(data.message || 'Failed to place order.', true);
        }
    } catch(err) {
        showToast('Network error. Is XAMPP running?', true);
        console.error(err);
    } finally {
        btn.disabled = false;
        txt.innerText = 'Confirm Order';
    }
}

// ── Swiper ───────────────────────────────────────────────────
new Swiper('.mySwiper', {
    spaceBetween:30, centeredSlides:true,
    autoplay:{delay:2500, disableOnInteraction:false},
    pagination:{el:'.swiper-pagination', clickable:true},
    breakpoints:{0:{slidesPerView:1},768:{slidesPerView:2},1020:{slidesPerView:3}}
});
</script>
</body>
</html>