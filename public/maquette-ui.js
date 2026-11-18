const maquetteCartKey = 'mesanimaux.maquette.cart.v1';
const formatMaquettePrice = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' });

function readMaquetteCart() {
    try {
        const items = JSON.parse(localStorage.getItem(maquetteCartKey) ?? '[]');
        return Array.isArray(items) ? items : [];
    } catch {
        return [];
    }
}

function writeMaquetteCart(items) {
    try {
        localStorage.setItem(maquetteCartKey, JSON.stringify(items));
        return true;
    } catch {
        return false;
    }
}

function imageMaquette(item) {
    const fallback = '/images/boutique/' + (/lapin|adoption/i.test(item.name) ? 'lapin-nain' :
        /croquette|nutrition/i.test(item.name) ? 'croquettes-chien' :
            /harnais|manteau|collier/i.test(item.name) ? 'harnais-balade' : 'jouet-corde') + '.jpg';
    return /^(?:https:\/\/lh3\.googleusercontent\.com\/|\/images\/boutique\/)/.test(item.image ?? '') ? item.image : fallback;
}

function readMaquetteClassicCart(stored) {
    const safe = value => String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[char]);
    return JSON.parse(stored).filter(item => /^[a-z0-9-]+$/i.test(item.id)).map(item => ({
        ...item,
        title: safe(item.name ?? item.title),
        category: safe(item.category ?? 'Animalia'),
        variant: safe(item.variant),
        image: safe(imageMaquette(item)),
        imageAlt: safe(item.name ?? item.title),
        unitPrice: Number(item.price ?? item.unitPrice),
        quantity: Number(item.quantity ?? item.qty) || 1,
    }));
}

function renderMaquetteRows(list, cart) {
    const sample = list.querySelector('.cart-item');
    if (!sample) return;
    const rows = new Map([...list.querySelectorAll('.cart-item')].map(row => [row.dataset.id, row]));
    for (const item of cart) {
        const existing = rows.get(item.id);
        const row = existing ?? sample.cloneNode(true);
        const title = row.querySelector('h2');
        const name = item.name ?? item.title;
        const details = title.parentElement;
        if (!existing || title.textContent.trim() !== name) {
            details.querySelector('div')?.remove();
            details.querySelector('p').textContent = item.details ?? '';
            row.querySelector('img').src = imageMaquette(item);
            row.querySelector('img').alt = '';
        }
        row.dataset.id = item.id;
        row.dataset.price = item.price;
        title.textContent = name;
        row.querySelector('.item-subtotal + span').textContent = '(' + formatMaquettePrice.format(item.price) + '/u)';
        if (!existing) list.append(row);
    }
}


function syncMaquetteBadges() {
    const count = readMaquetteCart().reduce((total, item) => total + (Number(item.quantity ?? item.qty) || 1), 0);
    document.querySelectorAll('header [data-path="panier"] span.w-5, header [aria-label="Mon panier"] span:last-child, #headerCartCount')
        .forEach(badge => {
            if (!/^\d+$/.test(badge.textContent.trim())) return;
            badge.textContent = count;
            if (badge.matches('button[aria-label="Mon panier"] span.bg-secondary')) {
                badge.style.display = count > 0 ? 'flex' : 'none';
            }
        });
}

function notifyMaquette(message) {
    let notice = document.querySelector('[data-maquette-notice]');
    if (!notice) {
        notice = document.createElement('p');
        notice.dataset.maquetteNotice = '';
        notice.setAttribute('role', 'status');
        notice.style.cssText = 'position:fixed;left:1rem;bottom:1rem;z-index:100;background:#133322;color:white;padding:1rem;border-radius:.65rem;max-width:calc(100% - 2rem)';
        document.body.append(notice);
    }
    notice.textContent = message;
    notice.hidden = false;
    clearTimeout(notice.dismiss);
    notice.dismiss = setTimeout(() => notice.hidden = true, 3500);
}

function addMaquetteItem(card, name, price, articleId) {
    const amount = Number(String(price).replace(/[^\d,.]/g, '').replace(',', '.'));
    if (!name || !Number.isFinite(amount) || amount <= 0) return;
    const id = articleId ?? 'demo-' + name.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-');
    const items = readMaquetteCart();
    const existing = items.find(item => item.id === id);
    if (existing) {
        existing.quantity = Number(existing.quantity ?? existing.qty ?? 1) + 1;
        existing.qty = existing.quantity;
    } else {
        items.push({ id, name, price: amount, quantity: 1, qty: 1, image: card?.querySelector('img')?.getAttribute('src') });
    }
    if (!writeMaquetteCart(items)) {
        notifyMaquette('Le navigateur ne permet pas de conserver le panier.');
        return;
    }
    syncMaquetteBadges();
    if (articleId || document.documentElement.dataset.maquette !== 'accessoires') notifyMaquette(name + ' ajouté au panier.');
}

document.addEventListener('click', event => {
    const target = event.target.closest('a, button');
    if (!target) return;
    if (target.hasAttribute('data-site-link')) {
        event.stopImmediatePropagation();
        return;
    }
    if (target.matches('[data-demo-link], #checkout-cta-btn, #btn-subscribe-trial, a[href="#"]:not([onclick])')) {
        event.preventDefault();
        event.stopImmediatePropagation();
        notifyMaquette('Cette fonctionnalité est indisponible dans cette version de test.');
        return;
    }

    const liveCard = target.closest('[data-live-card]');
    if (liveCard && target.matches('[data-live-add]')) {
        addMaquetteItem(liveCard, liveCard.dataset.name, liveCard.dataset.price, liveCard.dataset.id);
        return;
    }
    if (target.closest('header') && target.matches('[aria-label="Mon panier"]') && document.querySelector('[data-vitrine-filtres]')) {
        event.preventDefault();
        event.stopImmediatePropagation();
        location.assign('/panier');
        return;
    }

    const screen = document.documentElement.dataset.maquette;
    const card = target.closest('article');
    if (screen === 'accessoires' && target.getAttribute('onclick')?.startsWith('ajouterAuPanier(')) {
        const args = target.getAttribute('onclick').match(/^ajouterAuPanier\('(.+)',\s*'([^']+)'\)/);
        if (args) addMaquetteItem(card, args[1].replaceAll("\\'", "'"), args[2]);
    }
    if (screen === 'animaux' && card && /Réserver\s*&\s*Adopter/i.test(target.textContent)) {
        const name = card.querySelector('h3')?.textContent.trim();
        const price = card.textContent.match(/\d+(?:[,.]\d{2})?\s*€/u)?.[0];
        addMaquetteItem(card, name, price);
    }

    if (!['panier', 'panier-classique'].includes(screen)
        && target.matches('[aria-label="Mon panier"]')
        && !document.querySelector('#cartDrawer')) {
        event.preventDefault();
        event.stopImmediatePropagation();
        location.assign('/panier');
        return;
    }
    setTimeout(syncMaquetteBadges, 0);
}, true);

document.addEventListener('DOMContentLoaded', () => {
    markVitrineNavigation();
    syncBotanicalSummary();
    if (document.querySelector('[data-vitrine-empty]')) {
        document.querySelector('#noProductsMsg')?.style.setProperty('display', 'none');
    }
    if (document.documentElement.dataset.maquette === 'animaux' && document.querySelector('[data-vitrine-filtres]')) {
        filterVitrineAnimaux();
    }
    syncMaquetteBadges();
    const query = new URLSearchParams(location.search);
    const term = query.get('q')?.trim().toLocaleLowerCase('fr');
    const rayon = query.get('rayon');
    if (!document.querySelector('[data-vitrine-filtres]') && document.documentElement.dataset.maquette === 'catalogue' && (term || rayon)) {
        for (const card of document.querySelectorAll('.product-card')) {
            const name = card.dataset.name ?? card.querySelector('h3')?.textContent ?? '';
            const category = card.dataset.cat ?? card.dataset.category ?? '';
            card.hidden = Boolean(term && !name.toLocaleLowerCase('fr').includes(term)
                || rayon && category && !category.toLocaleLowerCase('fr').includes(rayon.toLocaleLowerCase('fr')));
        }
    }
    const headerSearch = document.querySelector('header input');
    headerSearch?.addEventListener('keydown', event => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        location.assign('/catalogue?' + new URLSearchParams({ q: headerSearch.value }));
    });
});

document.addEventListener('submit', event => {
    if (!event.target.matches('footer form')) return;
    event.preventDefault();
    notifyMaquette('La newsletter est indisponible dans cette version de test.');
}, true);


window.addEventListener('storage', syncMaquetteBadges);
window.addEventListener('pageshow', syncMaquetteBadges);


function markVitrineNavigation() {
    const current = location.pathname.replace(/\/$/, '') || '/';
    if (current.startsWith('/maquette/')) return;
    document.querySelectorAll('header nav a[data-site-link]').forEach(link => {
        const destination = new URL(link.href);
        const active = destination.pathname === current && !destination.hash
            && !(current === '/catalogue' && destination.search);
        if (!active) {
            link.removeAttribute('aria-current');
            return;
        }
        link.setAttribute('aria-current', 'page');
        const nav = link.closest('[data-vitrine-menu]');
        if (nav && nav.scrollWidth > nav.clientWidth) {
            const right = link.getBoundingClientRect().right - nav.getBoundingClientRect().right;
            if (right > 0) nav.scrollLeft += right + 8;
        }
    });
}

function filterVitrineAnimaux() {
    const cards = [...document.querySelectorAll('article[data-live-card]')];
    const buttons = [...document.querySelectorAll('#category-tabs button')];
    const especes = ['all', 'chien', 'chat', 'rongeur', 'oiseau'];
    buttons.forEach((button, index) => {
        const espece = especes[index];
        const visible = cards.filter(card => espece === 'all' || card.dataset.specie === espece);
        button.hidden = visible.length === 0;
        if (button.hidden) button.style.display = 'none';
        button.setAttribute('aria-pressed', String(index === 0));
        button.addEventListener('click', () => {
            buttons.forEach(other => other.setAttribute('aria-pressed', String(other === button)));
            cards.forEach(card => card.classList.toggle('hidden', !visible.includes(card)));
        });
    });
}

function syncBotanicalSummary() {
    const subtotal = document.querySelector('#summary-subtotal');
    const promoTag = document.querySelector('#promo-applied-tag');
    const discount = document.querySelector('#discount-row');
    const progress = document.querySelector('#shipping-progress-bar');
    if (!subtotal || !promoTag || !discount || !progress) return;

    const gauge = progress.parentElement.previousElementSibling;
    const [label, percent] = gauge.querySelectorAll('span');
    const message = progress.closest('section')?.querySelector('p');
    function refresh() {
        const amount = Number(subtotal.textContent.replace(/[^\d,]/g, '').replace(',', '.'));
        const reached = amount >= 49;
        label.textContent = reached ? 'Palier de 49 € atteint' : 'Livraison offerte dès 49 €';
        percent.textContent = progress.style.width;
        if (message) {
            message.textContent = reached ? 'La livraison offerte est incluse dans cette simulation.'
                : 'Il manque ' + formatMaquettePrice.format(Math.max(0, 49 - amount)) + ' pour atteindre le seuil de livraison offerte.';
        }
        const display = discount.classList.contains('hidden') ? 'none' : '';
        if (promoTag.style.display !== display) promoTag.style.display = display;
    }
    refresh();
    new MutationObserver(refresh).observe(subtotal, { childList: true });
}
