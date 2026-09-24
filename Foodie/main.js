// ============================================================
//  main.js  –  Foodie Frontend Logic
// ============================================================

const products = [
    { id: 1, name: 'Double Beef Burger', price: 12.50, img: 'images/burger.png' },
    { id: 2, name: 'Cheese Pizza',       price: 15.00, img: 'images/pizza.png' },
    { id: 3, name: 'Fried Chicken',      price: 10.00, img: 'images/fried-chicken.png' },
    { id: 4, name: 'Grilled Sandwich',   price: 8.50,  img: 'images/sandwich.png' },
    { id: 5, name: 'Chicken Roll',       price: 9.00,  img: 'images/chicken-roll.png' },
    { id: 6, name: 'Italian Spaghetti',  price: 14.00, img: 'images/spaghetti.png' },
    { id: 7, name: 'Spring Roll',        price: 7.00,  img: 'images/spring-roll.png' },
    { id: 8, name: 'Lasagna',            price: 11.50, img: 'images/lasagna.png' }
];

let cart = [];

// --- SELECTORS ---
const menuGrid        = document.getElementById('menu-grid');
const cartItemsEl     = document.getElementById('cart-items');
const cartTotalEl     = document.getElementById('cart-total');
const cartCountEl     = document.getElementById('cart-count');
const cartSidebar     = document.getElementById('cart-sidebar');
const cartBtn         = document.getElementById('cart-btn');
const closeCartBtn    = document.getElementById('close-cart');
const menuBtn         = document.getElementById('menu-btn');
const navList         = document.getElementById('nav-list');
const toast           = document.getElementById('toast');
const checkoutBtn     = document.getElementById('checkout-btn');
const paymentModal    = document.getElementById('payment-modal');
const orderSuccess    = document.getElementById('order-success');
const confirmOrderBtn = document.getElementById('confirm-order-btn');
const cancelBtn       = document.getElementById('cancel-btn');
const doneBtn         = document.getElementById('done-btn');

// --- INIT ---
function initApp() {
    renderMenu();
}

// --- RENDER MENU ---
function renderMenu() {
    if (!menuGrid) return;
    menuGrid.innerHTML = '';
    products.forEach(product => {
        const card = document.createElement('div');
        card.classList.add('food-card');
        card.innerHTML = `
            <img src="${product.img}" alt="${product.name}" class="food-img"
                 onerror="this.src='https://cdn-icons-png.flaticon.com/512/2830/2830305.png'">
            <h3 class="food-title">${product.name}</h3>
            <span class="food-price">$${product.price.toFixed(2)}</span>
            <button class="btn" onclick="addToCart(${product.id})">Add to Cart</button>
        `;
        menuGrid.appendChild(card);
    });
}

// --- CART LOGIC ---
function addToCart(id) {
    if (cart.some(item => item.id === id)) {
        showToast('Item already in cart!', true);
        return;
    }
    const product = products.find(p => p.id === id);
    cart.push({ ...product, qty: 1 });
    updateCart();
    showToast('Item Added to Cart! 🛒');
    if (cartSidebar) cartSidebar.classList.add('active');
}

function updateCart() {
    renderCartItems();
    renderTotal();
}

function renderCartItems() {
    if (!cartItemsEl) return;
    cartItemsEl.innerHTML = '';

    if (cart.length === 0) {
        cartItemsEl.innerHTML = '<div class="text-center" style="margin-top:2rem;color:var(--gray)">Your cart is empty</div>';
        return;
    }

    cart.forEach(item => {
        const div = document.createElement('div');
        div.classList.add('cart-item');
        div.innerHTML = `
            <img src="${item.img}" alt="${item.name}" class="cart-item-img"
                 onerror="this.src='https://cdn-icons-png.flaticon.com/512/2830/2830305.png'">
            <div class="cart-item-details">
                <h4 class="cart-item-title">${item.name}</h4>
                <span class="cart-item-price">$${item.price.toFixed(2)}</span>
            </div>
            <div class="cart-actions">
                <div class="qty-btn" onclick="changeQty('minus',${item.id})"><i class="fas fa-minus"></i></div>
                <span>${item.qty}</span>
                <div class="qty-btn" onclick="changeQty('plus',${item.id})"><i class="fas fa-plus"></i></div>
            </div>
        `;
        cartItemsEl.appendChild(div);
    });
}

function changeQty(action, id) {
    cart = cart.map(item => {
        if (item.id === id) {
            const qty = action === 'plus' ? item.qty + 1 : item.qty - 1;
            return { ...item, qty };
        }
        return item;
    }).filter(item => item.qty > 0);
    updateCart();
}

function renderTotal() {
    let totalPrice = 0, totalItems = 0;
    cart.forEach(item => { totalPrice += item.price * item.qty; totalItems += item.qty; });
    if (cartTotalEl) cartTotalEl.innerText = `$${totalPrice.toFixed(2)}`;
    if (cartCountEl) cartCountEl.innerText = totalItems;
}

// --- TOAST ---
function showToast(msg, isError = false) {
    if (!toast) return;
    toast.innerText = msg;
    toast.style.backgroundColor = isError ? '#e74c3c' : 'var(--lead-color)';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2500);
}

// --- EVENTS: Cart sidebar ---
if (cartBtn)      cartBtn.addEventListener('click',      () => cartSidebar.classList.add('active'));
if (closeCartBtn) closeCartBtn.addEventListener('click', () => cartSidebar.classList.remove('active'));

// --- EVENTS: Mobile menu ---
if (menuBtn) {
    menuBtn.addEventListener('click', () => {
        navList.classList.toggle('active');
        const icon = menuBtn.querySelector('i');
        icon.classList.toggle('fa-bars');
        icon.classList.toggle('fa-times');
    });
}

// --- CHECKOUT: Open modal ---
if (checkoutBtn) {
    checkoutBtn.addEventListener('click', () => {
        const total = parseFloat((cartTotalEl?.innerText ?? '$0').replace('$', ''));
        if (total <= 0) {
            showToast('Your cart is empty!', true);
            return;
        }
        if (paymentModal) paymentModal.classList.remove('hidden');
        if (cartSidebar)  cartSidebar.classList.remove('active');
    });
}

// --- CHECKOUT: Cancel ---
if (cancelBtn) {
    cancelBtn.addEventListener('click', () => {
        if (paymentModal) paymentModal.classList.add('hidden');
    });
}

// --- CHECKOUT: Confirm & Place Order ---
if (confirmOrderBtn) {
    confirmOrderBtn.addEventListener('click', async () => {
        // Validate fields
        const nameEl    = document.getElementById('cust-name');
        const phoneEl   = document.getElementById('cust-phone');
        const addressEl = document.getElementById('cust-address');

        const custName    = nameEl?.value.trim()    || '';
        const custPhone   = phoneEl?.value.trim()   || '';
        const custAddress = addressEl?.value.trim() || '';

        if (!custName) {
            showToast('Please enter your name', true);
            nameEl?.focus();
            return;
        }
        if (!custPhone) {
            showToast('Please enter your phone number', true);
            phoneEl?.focus();
            return;
        }
        if (!custAddress) {
            showToast('Please enter your delivery address', true);
            addressEl?.focus();
            return;
        }

        // Get payment method
        const onlineRadio = document.getElementById('online-radio');
        const paymentMethod = (onlineRadio && onlineRadio.checked) ? 'online' : 'cod';

        // Build payload
        const payload = {
            cart:             cart.map(i => ({ id: i.id, qty: i.qty, price: i.price })),
            payment_method:   paymentMethod,
            customer_name:    custName,
            customer_phone:   custPhone,
            customer_address: custAddress
        };

        // Disable button while loading
        const btnText = document.getElementById('confirm-btn-text');
        if (btnText) btnText.innerText = 'Placing order…';
        confirmOrderBtn.disabled = true;

        try {
            const response = await fetch('place_order.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload)
            });
            const data = await response.json();

            if (data.status === 'success') {
                // Hide payment modal, show success
                if (paymentModal) paymentModal.classList.add('hidden');
                if (orderSuccess) orderSuccess.classList.remove('hidden');

                // Show order ID in success modal
                const msgEl = document.getElementById('order-confirmation-msg');
                if (msgEl && data.order_id) {
                    msgEl.innerHTML = `Order ID: <strong class="order-id-toast">#${String(data.order_id).padStart(4,'0')}</strong> — Total: <strong>$${data.total}</strong>`;
                }

                // Reset cart
                cart = [];
                updateCart();
            } else {
                showToast(data.message || 'Failed to place order. Try again.', true);
            }
        } catch (err) {
            // Fallback: still show success (demo mode without PHP)
            console.warn('place_order.php not reachable:', err);
            if (paymentModal) paymentModal.classList.add('hidden');
            if (orderSuccess) orderSuccess.classList.remove('hidden');
            cart = [];
            updateCart();
        } finally {
            if (btnText) btnText.innerText = 'Confirm Order';
            confirmOrderBtn.disabled = false;
        }
    });
}

// --- DONE button ---
if (doneBtn) {
    doneBtn.addEventListener('click', () => {
        if (orderSuccess) orderSuccess.classList.add('hidden');
    });
}

// --- SWIPER ---
if (typeof Swiper !== 'undefined') {
    new Swiper('.mySwiper', {
        spaceBetween: 30,
        centeredSlides: true,
        autoplay: { delay: 2500, disableOnInteraction: false },
        pagination: { el: '.swiper-pagination', clickable: true },
        breakpoints: {
            0:    { slidesPerView: 1 },
            768:  { slidesPerView: 2 },
            1020: { slidesPerView: 3 },
        }
    });
}

// --- START ---
initApp();