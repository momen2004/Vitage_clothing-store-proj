/* Vitage — frontend helpers (cart AJAX, theme toggle, gallery) */

(function () {
    'use strict';

    // ---------- Theme toggle (sepia <-> dark) ----------
    const toggle = document.getElementById('themeToggle');
    if (toggle) {
        toggle.addEventListener('click', () => {
            const cur = document.body.getAttribute('data-theme') === 'dark' ? 'sepia' : 'dark';
            document.body.setAttribute('data-theme', cur);
            document.cookie = 'theme=' + cur + ';path=/;max-age=' + (60 * 60 * 24 * 180);
        });
    }

    // ---------- Update cart badge ----------
    function setCartCount(n) {
        const b = document.getElementById('cartBubble');
        if (b) b.textContent = n;
    }

    // ---------- Product gallery ----------
    document.querySelectorAll('.thumbs img').forEach(img => {
        img.addEventListener('click', () => {
            const main = document.querySelector('.gallery .main-img img');
            if (main) main.src = img.dataset.full || img.src;
            document.querySelectorAll('.thumbs img').forEach(i => i.classList.remove('active'));
            img.classList.add('active');
        });
    });

    // ---------- AJAX add to cart ----------
    document.body.addEventListener('submit', async (ev) => {
        const form = ev.target;
        if (!form.matches('form.ajax-add-cart')) return;
        ev.preventDefault();

        const btn = form.querySelector('button[type="submit"]');
        const origText = btn ? btn.textContent : null;
        if (btn) { btn.disabled = true; btn.textContent = 'Adding…'; }

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            if (data.ok) {
                setCartCount(data.cart_count);
                showToast('Added to cart!');
            } else {
                showToast(data.error || 'Could not add to cart', true);
            }
        } catch (e) {
            showToast('Network error', true);
        } finally {
            if (btn) { btn.disabled = false; btn.textContent = origText; }
        }
    });

    // ---------- Toast ----------
    function showToast(msg, error) {
        let t = document.getElementById('vitage-toast');
        if (!t) {
            t = document.createElement('div');
            t.id = 'vitage-toast';
            t.style.cssText = `
                position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
                background: #2d1e12; color: #fbf3e2; padding: 12px 22px; border-radius: 6px;
                font-family: 'Special Elite', monospace; letter-spacing: 1px;
                box-shadow: 0 8px 24px rgba(0,0,0,0.3);
                opacity: 0; transition: opacity .25s ease; z-index: 999;`;
            document.body.appendChild(t);
        }
        t.textContent = msg;
        t.style.background = error ? '#8a1f1f' : '#2d1e12';
        requestAnimationFrame(() => { t.style.opacity = '1'; });
        clearTimeout(t._h);
        t._h = setTimeout(() => { t.style.opacity = '0'; }, 2200);
    }
})();
