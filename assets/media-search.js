/**
 * Quick Navigation – Live-Suche im Medienpool.
 *
 * Das Suchfeld des Medienpools (#be_search-media-name mit data-qn-media) zeigt beim Tippen Treffer, ohne Eingabe die
 * zuletzt bearbeiteten Medien. Im Auswahl-Modus (Overlay eines Medienfelds) hat jeder Treffer „übernehmen“ – ein
 * Knopf mit data-rex-pick, den das Overlay des Cores selbst verarbeitet. Funktioniert auch in Inhalten, die das
 * Overlay nachlädt (rex:ready).
 */
(() => {
    'use strict';

    const DEBOUNCE = 120;
    let counter = 0;

    const el = (tag, attrs = {}, ...children) => {
        const node = document.createElement(tag);
        for (const [k, v] of Object.entries(attrs)) {
            if (v === null || v === undefined || v === false) {
                continue;
            }
            if (k === 'class') {
                node.className = v;
            } else if (k === 'text') {
                node.textContent = v;
            } else {
                node.setAttribute(k, v === true ? '' : v);
            }
        }
        node.append(...children.filter((c) => c !== null && c !== undefined));
        return node;
    };

    const highlight = (text, term) => {
        const span = el('span');
        const i = term ? text.toLowerCase().indexOf(term.toLowerCase()) : -1;
        if (i < 0) {
            span.textContent = text;
            return span;
        }
        span.append(text.slice(0, i), el('mark', { text: text.slice(i, i + term.length) }), text.slice(i + term.length));
        return span;
    };

    const init = (input) => {
        if (input.dataset.qnReady) {
            return;
        }
        input.dataset.qnReady = '1';
        const cfg = JSON.parse(input.dataset.qnMedia);
        const t = cfg.i18n;
        const id = 'qn-media-' + (++counter);

        // im Element <search> des Medienpools, damit Klicks im Overlay des Cores ankommen
        const panel = el('div', { class: 'qn-media-panel', id, popover: 'manual' });
        const list = el('div', { class: 'qn-media-list', role: 'listbox', id: id + '-list', 'aria-label': t.results });
        panel.append(list);
        (input.closest('search') || input.parentElement).append(panel);
        input.setAttribute('aria-controls', id + '-list');

        let rows = [];
        let active = -1;
        let timer = 0;
        let controller = null;

        const place = () => {
            const r = input.closest('.rex-input-group')?.getBoundingClientRect() ?? input.getBoundingClientRect();
            const width = Math.max(r.width, Math.min(420, window.innerWidth - 16));
            const left = Math.max(8, Math.min(r.right - width, window.innerWidth - width - 8));
            // CSSOM, kein style-Attribut im Markup (CSP)
            panel.style.top = Math.round(r.bottom + 4) + 'px';
            panel.style.left = Math.round(left) + 'px';
            panel.style.width = Math.round(width) + 'px';
        };

        const open = () => {
            if (!panel.matches(':popover-open')) {
                place();
                panel.showPopover();
                input.setAttribute('aria-expanded', 'true');
            }
        };

        const close = () => {
            if (panel.matches(':popover-open')) {
                panel.hidePopover();
            }
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            active = -1;
        };

        const select = (i) => {
            rows[active]?.classList.remove('is-active');
            rows[active]?.setAttribute('aria-selected', 'false');
            active = i;
            const row = rows[active];
            if (!row) {
                input.removeAttribute('aria-activedescendant');
                return;
            }
            row.classList.add('is-active');
            row.setAttribute('aria-selected', 'true');
            input.setAttribute('aria-activedescendant', row.id);
            row.scrollIntoView({ block: 'nearest' });
        };

        const render = (data, term) => {
            rows = [];
            active = -1;
            list.replaceChildren(el('div', { class: 'qn-media-head', role: 'presentation', text: data.recent ? t.recent : t.results }));
            data.items.forEach((item, i) => {
                const thumb = item.thumbnail
                    ? el('img', { src: item.thumbnail, alt: '', loading: 'lazy', decoding: 'async' })
                    : el('i', { class: 'rex-icon rex-icon-media', 'aria-hidden': 'true' });
                const row = el('div', { class: 'qn-media-row', role: 'option', id: id + '-o' + i, 'aria-selected': 'false' },
                    el('a', { class: 'qn-media-link', href: item.url, tabindex: '-1' },
                        el('span', { class: 'qn-media-thumb' }, thumb),
                        el('span', { class: 'qn-media-text' },
                            el('span', { class: 'qn-media-title' }, highlight(item.title, term)),
                            el('span', { class: 'qn-media-meta' }, highlight(item.meta, term)),
                        ),
                    ),
                    item.pick ? el('button', {
                        type: 'button',
                        class: 'rex-button qn-media-pick',
                        'data-variant': 'primary',
                        'data-size': 'xs',
                        'data-effect': cfg.picker === 'multiple' ? 'flash' : null,
                        'data-rex-pick': item.pick,
                        'data-rex-pick-label': item.title,
                        tabindex: '-1',
                        text: t.pick,
                    }) : null,
                );
                row.addEventListener('pointermove', () => active !== i && select(i));
                rows.push(row);
                list.append(row);
            });
            if (!rows.length) {
                list.append(el('p', { class: 'qn-media-empty', text: t.empty }));
            }
            open();
        };

        const run = async () => {
            const term = input.value.trim();
            controller?.abort();
            controller = new AbortController();
            const params = new URLSearchParams({ q: term });
            if (cfg.types) {
                params.set('types', cfg.types);
            }
            if (cfg.picker) {
                params.set('picker', cfg.picker);
            }
            if (cfg.language) {
                params.set('language', cfg.language);
            }
            try {
                const res = await fetch(cfg.url + '&' + params, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: controller.signal });
                if (res.ok && document.activeElement === input) {
                    render(await res.json(), term);
                }
            } catch {
                // abgebrochen oder nicht erreichbar – die normale Suche bleibt
            }
        };

        const schedule = (delay = DEBOUNCE) => {
            clearTimeout(timer);
            timer = setTimeout(run, delay);
        };

        input.addEventListener('focus', () => schedule(0));
        input.addEventListener('input', () => schedule());
        input.addEventListener('blur', () => {
            // Klick in die Liste abwarten
            setTimeout(() => {
                if (!panel.contains(document.activeElement)) {
                    close();
                }
            }, 150);
        });
        input.addEventListener('keydown', (e) => {
            const isOpen = panel.matches(':popover-open');
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                if (!isOpen) {
                    schedule(0);
                    return;
                }
                if (rows.length) {
                    const step = e.key === 'ArrowDown' ? 1 : -1;
                    select((active + step + rows.length) % rows.length);
                }
            } else if (e.key === 'Enter' && isOpen && rows[active]) {
                // gewählter Treffer: übernehmen (Auswahl-Modus) bzw. öffnen; sonst normale Suche
                e.preventDefault();
                const row = rows[active];
                (row.querySelector('.qn-media-pick') || row.querySelector('.qn-media-link')).click();
                close();
            } else if (e.key === 'Escape' && isOpen) {
                e.preventDefault();
                e.stopPropagation();
                close();
            }
        });
        // Liste schließen, wenn darin gewählt wurde
        list.addEventListener('click', (e) => {
            if (e.target.closest('a, button')) {
                setTimeout(close, 0);
            }
        });
        list.addEventListener('pointerdown', (e) => e.preventDefault()); // Fokus bleibt im Feld
        window.addEventListener('resize', () => panel.matches(':popover-open') && place());
        input.closest('.rex-overlay-body, .rex-main')?.addEventListener('scroll', () => panel.matches(':popover-open') && place(), { passive: true });
    };

    const scan = (root = document) => root.querySelectorAll?.('#be_search-media-name[data-qn-media]').forEach(init);

    scan();
    document.addEventListener('rex:ready', (e) => scan(e.target));
})();
