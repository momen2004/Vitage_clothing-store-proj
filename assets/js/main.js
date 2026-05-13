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

    // ---------- Live cart: instant local recalc + debounced server sync ----------
    const cartTable = document.getElementById('cartTable');
    if (cartTable) {
        const fmt       = n => '$' + n.toFixed(2);
        const subEl     = document.getElementById('cartSubtotal');
        const shpEl     = document.getElementById('cartShipping');
        const totEl     = document.getElementById('cartTotal');
        const statusEl  = document.getElementById('cartSaveStatus');
        const updateUrl = cartTable.dataset.updateUrl;
        const csrf      = cartTable.dataset.csrf;
        const pending   = new Map();   // itemId -> timeout handle

        const setStatus = (text, color) => {
            if (!statusEl) return;
            statusEl.textContent = text || '';
            statusEl.style.color = color || 'var(--muted)';
        };

        const localRecalc = () => {
            let subtotal = 0;
            cartTable.querySelectorAll('tbody tr').forEach(tr => {
                const price = parseFloat(tr.dataset.price) || 0;
                const input = tr.querySelector('.cart-qty');
                const qty   = Math.max(0, parseInt(input.value, 10) || 0);
                const line  = price * qty;
                const cell  = tr.querySelector('.row-subtotal');
                if (cell) cell.textContent = fmt(line);
                subtotal += line;
            });
            const shipping = (subtotal > 0 && subtotal < 80) ? 8 : 0;
            if (subEl) subEl.textContent = fmt(subtotal);
            if (shpEl) shpEl.textContent = shipping ? fmt(shipping) : 'Free';
            if (totEl) totEl.textContent = fmt(subtotal + shipping);
        };

        const applyServerTotals = data => {
            if (typeof data.subtotal === 'number' && subEl) subEl.textContent = fmt(data.subtotal);
            if (typeof data.shipping === 'number' && shpEl) shpEl.textContent = data.shipping ? fmt(data.shipping) : 'Free';
            if (typeof data.total    === 'number' && totEl) totEl.textContent = fmt(data.total);
            if (typeof data.cart_count === 'number') {
                const b = document.getElementById('cartBubble');
                if (b) b.textContent = data.cart_count;
            }
        };

        const removeRow = tr => {
            tr.style.transition = 'opacity .2s';
            tr.style.opacity = '0';
            setTimeout(() => {
                tr.remove();
                if (!cartTable.querySelector('tbody tr')) location.reload();
            }, 200);
        };

        const saveRow = async (tr, qty) => {
            const itemId = tr.dataset.itemId;
            setStatus('Saving…');
            try {
                const body = new FormData();
                body.append('csrf', csrf);
                body.append('item_id', itemId);
                body.append('quantity', qty);
                const res = await fetch(updateUrl, {
                    method: 'POST',
                    body,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await res.json();
                if (!data.ok) {
                    setStatus(data.error || 'Could not save', 'var(--accent)');
                    return;
                }
                applyServerTotals(data);
                if (data.removed) {
                    removeRow(tr);
                } else if (data.quantity !== qty) {
                    // server capped it (stock limit) — reflect that
                    tr.querySelector('.cart-qty').value = data.quantity;
                    localRecalc();
                    setStatus('Limited to available stock', 'var(--accent)');
                    return;
                }
                setStatus('Saved ✓', 'var(--leaf)');
                setTimeout(() => setStatus(''), 1200);
            } catch (e) {
                setStatus('Network error', 'var(--accent)');
            }
        };

        const scheduleSave = tr => {
            const itemId = tr.dataset.itemId;
            const qty    = Math.max(0, parseInt(tr.querySelector('.cart-qty').value, 10) || 0);
            if (pending.has(itemId)) clearTimeout(pending.get(itemId));
            const promise = new Promise(resolve => {
                pending.set(itemId, {
                    handle: setTimeout(async () => {
                        pending.delete(itemId);
                        await saveRow(tr, qty);
                        resolve();
                    }, 400),
                    resolve,
                    tr,
                    qty,
                });
            });
            return promise;
        };

        const flushAllPending = async () => {
            const tasks = [];
            for (const [itemId, entry] of pending) {
                clearTimeout(entry.handle);
                tasks.push(saveRow(entry.tr, entry.qty).then(() => entry.resolve()));
            }
            pending.clear();
            await Promise.all(tasks);
        };

        cartTable.addEventListener('input', e => {
            if (!e.target.classList.contains('cart-qty')) return;
            localRecalc();
            scheduleSave(e.target.closest('tr'));
        });

        // Flush immediately when the user blurs the input or presses Enter.
        cartTable.addEventListener('change', e => {
            if (!e.target.classList.contains('cart-qty')) return;
            flushAllPending();
        });

        cartTable.addEventListener('click', e => {
            if (!e.target.classList.contains('cart-remove')) return;
            const tr = e.target.closest('tr');
            tr.querySelector('.cart-qty').value = 0;
            localRecalc();
            const itemId = tr.dataset.itemId;
            if (pending.has(itemId)) {
                clearTimeout(pending.get(itemId).handle);
                pending.delete(itemId);
            }
            saveRow(tr, 0);
        });

        // If the user clicks Checkout (or any link off this page) while a
        // save is still queued, wait for it to land before navigating.
        document.addEventListener('click', async e => {
            const link = e.target.closest('a');
            if (!link || !link.href) return;
            // Same-origin link leaving cart page
            try {
                const targetUrl = new URL(link.href, location.href);
                if (targetUrl.origin !== location.origin) return;
            } catch (_) { return; }
            if (pending.size === 0) return;

            e.preventDefault();
            setStatus('Saving…');
            await flushAllPending();
            window.location.href = link.href;
        }, true);

        // Last-ditch safety: if the page is about to unload with a pending
        // save, fire it synchronously via sendBeacon so it can't be lost.
        window.addEventListener('beforeunload', () => {
            for (const [, entry] of pending) {
                clearTimeout(entry.handle);
                const body = new FormData();
                body.append('csrf',     csrf);
                body.append('item_id',  entry.tr.dataset.itemId);
                body.append('quantity', entry.qty);
                if (navigator.sendBeacon) {
                    navigator.sendBeacon(updateUrl, body);
                }
            }
            pending.clear();
        });
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
