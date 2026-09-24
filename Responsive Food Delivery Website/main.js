// --- DATA ---
// Image paths updated to match your file structure screenshot
const products = [
    { id: 1, name: 'Double Beef Burger', price: 12.50, img: 'images/burger.png' },
    { id: 2, name: 'Cheese Pizza', price: 15.00, img: 'images/pizza.png' },
    { id: 3, name: 'Fried Chicken', price: 10.00, img: 'images/fried-chicken.png' },
    { id: 4, name: 'Grilled Sandwich', price: 8.50, img: 'images/sandwich.png' }, // Corrected filename
    { id: 5, name: 'Chicken Roll', price: 9.00, img: 'images/chicken-roll.png' },
    { id: 6, name: 'Italian Spaghetti', price: 14.00, img: 'images/spaghetti.png' }, // Corrected filename
    { id: 7, name: 'Spring Roll', price: 7.00, img: 'images/spring-roll.png' },
    { id: 8, name: 'Lasagna', price: 11.50, img: 'images/lasagna.png' } // Corrected filename
];

let cart = [];

// --- SELECTORS ---
const menuGrid = document.getElementById('menu-grid');
const cartItemsContainer = document.getElementById('cart-items');
const cartTotalEl = document.getElementById('cart-total');
const cartCountEl = document.getElementById('cart-count');
const cartSidebar = document.getElementById('cart-sidebar');
const cartBtn = document.getElementById('cart-btn');
const closeCartBtn = document.getElementById('close-cart');
const menuBtn = document.getElementById('menu-btn');
const navList = document.getElementById('nav-list');
const toast = document.getElementById('toast');

// --- INIT ---
function initApp() {
    renderMenu();
}

// --- RENDER MENU ---
function renderMenu() {
    menuGrid.innerHTML = '';
    products.forEach(product => {
        const card = document.createElement('div');
        card.classList.add('food-card');
        
        // Fallback for image errors in case file path is slightly off
        const imgPath = product.img;
        
        card.innerHTML = `
            <img src="${imgPath}" alt="${product.name}" class="food-img">
            <h3 class="food-title">${product.name}</h3>
            <span class="food-price">$${product.price.toFixed(2)}</span>
            <button class="btn" onclick="addToCart(${product.id})">Add to Cart</button>
        `;
        menuGrid.appendChild(card);
    });
}

// --- CART LOGIC ---
function addToCart(id) {
    // Check if item already exists
    if (cart.some(item => item.id === id)) {
        showToast("Item already in cart!", true);
        return;
    }

    const product = products.find(p => p.id === id);
    cart.push({ ...product, qty: 1 });
    
    updateCart();
    showToast("Item Added to Cart!");
    cartSidebar.classList.add('active'); // Auto open cart
}

function updateCart() {
    renderCartItems();
    renderTotal();
}

function renderCartItems() {
    cartItemsContainer.innerHTML = '';
    
    if (cart.length === 0) {
        cartItemsContainer.innerHTML = '<div class="text-center" style="margin-top: 2rem; color: var(--gray);">Your cart is empty</div>';
        return;
    }

    cart.forEach(item => {
        const cartItem = document.createElement('div');
        cartItem.classList.add('cart-item');
        cartItem.innerHTML = `
            <img src="${item.img}" alt="${item.name}" class="cart-item-img">
            <div class="cart-item-details">
                <h4 class="cart-item-title">${item.name}</h4>
                <span class="cart-item-price">$${item.price.toFixed(2)}</span>
            </div>
            <div class="cart-actions">
                <div class="qty-btn" onclick="changeQty('minus', ${item.id})"><i class="fas fa-minus"></i></div>
                <span>${item.qty}</span>
                <div class="qty-btn" onclick="changeQty('plus', ${item.id})"><i class="fas fa-plus"></i></div>
            </div>
        `;
        cartItemsContainer.appendChild(cartItem);
    });
}

function changeQty(action, id) {
    cart = cart.map(item => {
        let qty = item.qty;
        if (item.id === id) {
            if (action === 'minus') {
                qty--;
            } else if (action === 'plus') {
                qty++;
            }
        }
        return { ...item, qty };
    });

    // Remove items with 0 qty
    cart = cart.filter(item => item.qty > 0);
    updateCart();
}

function renderTotal() {
    let totalPrice = 0;
    let totalItems = 0;

    cart.forEach(item => {
        totalPrice += item.price * item.qty;
        totalItems += item.qty;
    });

    cartTotalEl.innerText = `$${totalPrice.toFixed(2)}`;
    cartCountEl.innerText = totalItems;
}

// --- UI FUNCTIONS ---
function showToast(msg, isError = false) {
    if(!toast) return;
    toast.innerText = msg;
    toast.style.backgroundColor = isError ? 'red' : 'var(--lead-color)';
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
    }, 2000);
}

// --- EVENT LISTENERS ---
if(cartBtn) {
    cartBtn.addEventListener('click', () => {
        cartSidebar.classList.add('active');
    });
}

if(closeCartBtn) {
    closeCartBtn.addEventListener('click', () => {
        cartSidebar.classList.remove('active');
    });
}

if(menuBtn) {
    menuBtn.addEventListener('click', () => {
        navList.classList.toggle('active');
        const icon = menuBtn.querySelector('i');
        if (navList.classList.contains('active')) {
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-times');
        } else {
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }
    });
}

// Initialize Swiper
var swiper = new Swiper(".mySwiper", {
    spaceBetween: 30,
    centeredSlides: true,
    autoplay: {
        delay: 2500,
        disableOnInteraction: false,
    },
    pagination: {
        el: ".swiper-pagination",
        clickable: true,
    },
    breakpoints: {
        0: { slidesPerView: 1 },
        768: { slidesPerView: 2 },
        1020: { slidesPerView: 3 },
    },
});

// Start App
initApp();