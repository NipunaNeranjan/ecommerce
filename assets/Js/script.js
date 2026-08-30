document.addEventListener('DOMContentLoaded', function () {
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';

    document.querySelectorAll('.nav-link').forEach(function (link) {
        const href = link.getAttribute('href');
        if (!href) return;

        const normalized = href.split('/').pop();
        const isActive = normalized === currentPage ||
            (normalized === 'admin-dashboard.php' && currentPage.startsWith('admin-')) ||
            (normalized === 'index.php' && currentPage === 'index.php');

        if (isActive) {
            link.classList.add('active');
        }
    });

    const cartCountNode = document.querySelector('[data-cart-count]');
    const currentCartCount = Number(localStorage.getItem('evergreenCartCount') || 0);
    if (cartCountNode) {
        cartCountNode.textContent = currentCartCount;
    }

    document.querySelectorAll('.btn-outline, .btn-filled, .btn-primary, .btn-submit, .btn-checkout').forEach(function (button) {
        button.addEventListener('click', function (event) {
            const buttonText = button.textContent.trim();

            if (button.classList.contains('btn-submit')) {
                const form = button.closest('form');
                if (form && !form.checkValidity()) {
                    form.reportValidity();
                    event.preventDefault();
                    return;
                }
            }

            if (buttonText.toLowerCase().includes('add to cart') || buttonText.toLowerCase().includes('shop now') || buttonText.toLowerCase().includes('proceed to checkout')) {
                const count = Number(localStorage.getItem('evergreenCartCount') || 0) + 1;
                localStorage.setItem('evergreenCartCount', count);

                if (cartCountNode) {
                    cartCountNode.textContent = count;
                }
            }
        });
    });

    document.querySelectorAll('.wishlist-icon').forEach(function (icon) {
        icon.addEventListener('click', function () {
            const active = icon.classList.toggle('active');
            icon.textContent = active ? '💗' : '🤍';
        });
    });

    document.querySelectorAll('.qty-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            const row = button.closest('.cart-item');
            const qtyNode = row ? row.querySelector('.qty-num') : null;
            if (!qtyNode) return;

            let qty = Number(qtyNode.textContent.trim());
            if (button.textContent.includes('-')) {
                qty = Math.max(1, qty - 1);
            } else {
                qty += 1;
            }
            qtyNode.textContent = qty;
        });
    });

    document.querySelectorAll('.delete-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            const row = button.closest('.cart-item');
            if (row) {
                row.remove();
            }
        });
    });

    document.querySelectorAll('.admin-search-wrap input').forEach(function (input) {
        input.addEventListener('input', function () {
            const filter = input.value.toLowerCase();
            const rows = document.querySelectorAll('.admin-table tbody tr');

            rows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    });

    const contactForm = document.querySelector('.contact-form-box form');
    if (contactForm) {
        contactForm.addEventListener('submit', function (event) {
            const name = document.getElementById('name');
            const email = document.getElementById('email');
            const message = document.getElementById('message');

            if (!name.value.trim() || !email.value.trim() || !message.value.trim()) {
                event.preventDefault();
                alert('Please complete all fields before submitting.');
                return;
            }

            alert('Thank you! Your message has been sent successfully.');
        });
    }

    const modalTriggers = document.querySelectorAll('[data-modal-target]');
    const closeModal = function (modal) {
        if (!modal) return;
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    };

    modalTriggers.forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            const modalId = trigger.getAttribute('data-modal-target');
            const modal = document.getElementById(modalId);
            if (!modal) return;

            modal.classList.add('show');
            document.body.classList.add('modal-open');
        });
    });

    document.querySelectorAll('.admin-modal-close, .admin-modal, .admin-modal-cancel').forEach(function (element) {
        element.addEventListener('click', function (event) {
            if (event.target === element || element.classList.contains('admin-modal-close') || element.classList.contains('admin-modal-cancel')) {
                const modal = element.closest('.admin-modal');
                closeModal(modal);
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.admin-modal.show').forEach(function (modal) {
                closeModal(modal);
            });
        }
    });

    const proceedCheckoutBtn = document.getElementById('proceed-checkout-btn');
    const checkoutPanel = document.getElementById('checkout-panel');

    if (proceedCheckoutBtn && checkoutPanel) {
        proceedCheckoutBtn.addEventListener('click', function () {
            checkoutPanel.style.display = 'block';
            proceedCheckoutBtn.style.display = 'none';
        });
    }
});