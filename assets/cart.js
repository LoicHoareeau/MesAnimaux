const key = 'mesanimaux.cart.v1';
const euro = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' });

function readCart() {
    try {
        const stored = JSON.parse(localStorage.getItem(key) ?? '{}');
        return Object.fromEntries(Object.entries(stored ?? {}).filter(([id, quantity]) =>
            /^[1-9]\d*$/.test(id) && Number.isSafeInteger(quantity) && quantity > 0 && quantity <= 999
        ));
    } catch {
        return {};
    }
}

function announce(message) {
    const status = document.querySelector('[data-cart-status]');
    if (status) status.textContent = message;
}

function saveCart(cart) {
    try {
        localStorage.setItem(key, JSON.stringify(cart));
        return true;
    } catch {
        announce('Le navigateur ne permet pas d’enregistrer le panier.');
        return false;
    }
}

function renderCart() {
    const cart = readCart();
    const count = Object.values(cart).reduce((total, quantity) => total + quantity, 0);
    document.querySelectorAll('[data-cart-count]').forEach(badge => badge.textContent = count);

    const page = document.querySelector('[data-cart-page]');
    if (!page) return;

    const lines = page.querySelector('[data-cart-lines]');
    const active = document.activeElement;
    const activeId = lines.contains(active) ? active.closest('[data-line-id]')?.dataset.lineId : null;
    const action = active?.matches('[data-remove]') ? '[data-remove]' : active?.dataset.change;
    let total = 0;
    lines.replaceChildren();

    page.querySelectorAll('[data-cart-article]').forEach(template => {
        const id = template.dataset.cartArticle;
        const quantity = cart[id];
        if (!quantity) return;

        const line = template.content.firstElementChild.cloneNode(true);
        const subtotal = Number(template.dataset.cents) * quantity;
        total += subtotal;
        line.dataset.lineId = id;
        line.querySelector('[data-quantity]').textContent = quantity;
        line.querySelector('[data-line-total]').textContent = euro.format(subtotal / 100);
        line.querySelector('[data-change="-1"]').disabled = quantity === 1;
        line.querySelector('[data-change="1"]').disabled = quantity === 999;
        lines.append(line);
    });

    page.querySelector('[data-cart-empty]').hidden = lines.childElementCount > 0;
    page.querySelector('[data-cart-total]').textContent = euro.format(total / 100);
    if (activeId) {
        const line = lines.querySelector(`[data-line-id="${activeId}"]`);
        const selector = action === '[data-remove]' ? action : `[data-change="${action}"]`;
        const control = line?.querySelector(selector);
        (control && !control.disabled ? control : line?.querySelector('[data-remove]') ?? lines.querySelector('[data-remove]') ?? document.querySelector('.intro a'))?.focus();
    }
}

document.addEventListener('click', event => {
    const button = event.target.closest('button');
    if (!button) return;

    const addId = button.dataset.addId;
    const lineId = button.closest('[data-line-id]')?.dataset.lineId;
    if (!addId && !lineId) return;

    const cart = readCart();
    const id = addId ?? lineId;
    const previous = cart[id] ?? 0;
    const quantity = addId ? previous + 1 : previous + Number(button.dataset.change ?? 0);
    if (quantity > 999) {
        announce('La quantité maximale par article est de 999.');
        return;
    }

    const removed = button.hasAttribute('data-remove');
    if (removed) delete cart[id];
    else cart[id] = quantity;

    if (saveCart(cart)) {
        renderCart();
        announce(addId ? `${button.dataset.addName} ajouté au panier.` : removed ? 'Article retiré du panier.' : `Quantité mise à jour à ${quantity}.`);
    }
});

document.addEventListener('turbo:load', renderCart);
window.addEventListener('pageshow', renderCart);
window.addEventListener('storage', event => {
    if (event.key === key || event.key === null) renderCart();
});
renderCart();
