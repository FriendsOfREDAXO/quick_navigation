/**
 * Quick Navigation – Befehlspalette im REDAXO-Backend.
 *
 * Knopf in der Kopfleiste oder ⌘K / Strg+K öffnet einen <dialog> mit Suchfeld (ARIA-Combobox) und Trefferliste.
 * Die Treffer liefert die API-Funktion quick_navigation (nach den Rechten des Benutzers); zuletzt geöffnete Treffer
 * merkt sich der Browser (localStorage). Keine Inline-Styles, keine Inline-Handler (CSP).
 */
(() => {
    'use strict';

    const trigger = document.querySelector('.qn-trigger[data-quick-navigation]');
    if (!trigger) {
        return;
    }

    const cfg = JSON.parse(trigger.dataset.quickNavigation);
    const t = cfg.i18n;
    const isMac = /Mac|iPhone|iPad/.test(navigator.userAgentData?.platform || navigator.platform || navigator.userAgent);
    const mod = isMac ? '⌘' : 'Strg';
    const RECENT_MAX = 8;
    const DEBOUNCE = 90;

    trigger.title = t.label + ' (' + (isMac ? '⌘K' : 'Strg+K') + ')';

    let dialog, input, list, live, scopeToggle, scopeMenu;
    let entries = [];     // [{item, el, group, actions: [el]}]
    let active = -1;      // Index in entries
    let actionIndex = -1; // gewählte Zusatzaktion des aktiven Treffers
    let timer = 0;
    let seq = 0;
    let controller = null;
    let lastFocus = null;

    // ---------------------------------------------------------------------------------------------------- Helfer

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

    const icon = (classes) => el('i', { class: classes || 'rex-icon rex-icon-article', 'aria-hidden': 'true' });

    /** Text mit <mark> für die Suchwörter – ohne innerHTML */
    const highlight = (text, words) => {
        const span = el('span');
        text = String(text ?? '');
        if (!words.length || !text) {
            span.textContent = text;
            return span;
        }
        const lower = text.toLowerCase();
        const ranges = [];
        for (const w of words) {
            let i = lower.indexOf(w);
            while (w && i !== -1) {
                ranges.push([i, i + w.length]);
                i = lower.indexOf(w, i + w.length);
            }
        }
        ranges.sort((a, b) => a[0] - b[0]);
        let pos = 0;
        for (const [from, to] of ranges) {
            if (from < pos) {
                continue;
            }
            span.append(text.slice(pos, from), el('mark', { text: text.slice(from, to) }));
            pos = to;
        }
        span.append(text.slice(pos));
        return span;
    };

    const words = (q) => q.replace(/^(>|[a-z]+:)\s*/i, '').toLowerCase().split(/\s+/).filter(Boolean);

    const language = () => {
        const p = new URLSearchParams(location.search);
        return p.get('language') || p.get('clang') || '';
    };

    // ------------------------------------------------------------------------------------- zuletzt geöffnet

    const recentLoad = () => {
        try {
            return JSON.parse(localStorage.getItem(cfg.storage) || '[]');
        } catch {
            return [];
        }
    };

    const recentSave = (item) => {
        // Befehle nicht merken (einige tragen ein CSRF-Token, das später nicht mehr gilt)
        if (item.external || item.command) {
            return;
        }
        const keep = { title: item.title, url: item.url, meta: item.meta, icon: item.icon, thumbnail: item.thumbnail };
        const list = [keep, ...recentLoad().filter((r) => r.url !== item.url)].slice(0, RECENT_MAX);
        try {
            localStorage.setItem(cfg.storage, JSON.stringify(list));
        } catch {
            // privates Fenster o. Ä. – dann eben ohne Verlauf
        }
    };

    const recentClear = () => {
        try {
            localStorage.removeItem(cfg.storage);
        } catch {
            // egal
        }
    };

    // ------------------------------------------------------------------------------------------------- Aufbau

    const build = () => {
        input = el('input', {
            class: 'qn-input',
            type: 'text',
            role: 'combobox',
            'aria-label': t.label,
            'aria-expanded': 'true',
            'aria-controls': 'qn-list',
            'aria-autocomplete': 'list',
            placeholder: t.placeholder,
            autocomplete: 'off',
            autocapitalize: 'off',
            spellcheck: 'false',
            enterkeyhint: 'go',
        });
        // Auswahl der Quelle (Dropdown des Cores): Name und klein das Kürzel – setzt das Kürzel ins Suchfeld
        const prefixText = (p) => (p === '>' ? '>' : p + ':');
        scopeToggle = el('button', { type: 'button', class: 'rex-button qn-scope-toggle', 'data-rex-dropdown': true, 'aria-haspopup': 'menu', 'aria-expanded': 'false', title: t.scopeLabel },
            el('span', { class: 'qn-scope-label', text: t.scopeAll }), el('span', { class: 'rex-caret', 'aria-hidden': 'true' }));
        scopeMenu = el('ul', { class: 'rex-dropdown-menu qn-scope-menu' },
            el('li', {}, el('a', { href: '#', 'data-prefix': '', 'aria-current': 'true' }, el('span', { text: t.scopeAll }))),
            ...cfg.prefixes.map((p) => el('li', {}, el('a', { href: '#', 'data-prefix': p.prefix, 'data-key': p.key },
                el('span', { text: p.label }), el('kbd', { text: prefixText(p.prefix) })))),
        );
        const scopeBox = el('div', { class: 'rex-dropdown qn-scope' }, scopeToggle, scopeMenu);
        list = el('div', { class: 'qn-list', id: 'qn-list', role: 'listbox', 'aria-label': t.results });
        live = el('div', { class: 'rex-u-visually-hidden', 'aria-live': 'polite', 'aria-atomic': 'true' });

        const hint = (keys, label) => el('span', { class: 'qn-hint' }, ...keys.map((k) => el('kbd', { text: k })), ' ' + label);
        const footer = el('div', { class: 'qn-footer' },
            el('div', { class: 'qn-hints' },
                hint(['↑', '↓', '↵'], t.open),
                hint([mod + '↵'], t.newTab),
                hint(['→'], t.actions),
                hint([mod + 'D'], t.favorite),
                hint(['esc'], t.close),
            ),
        );

        const close = el('button', { type: 'button', class: 'qn-close', 'aria-label': t.close }, el('kbd', { text: 'esc' }));

        dialog = el('dialog', { class: 'qn-dialog', 'aria-label': t.label },
            el('div', { class: 'qn-panel' },
                el('div', { class: 'qn-head' },
                    el('i', { class: 'rex-icon rex-icon-search qn-head-icon', 'aria-hidden': 'true' }),
                    scopeBox,
                    input,
                    el('span', { class: 'qn-spinner', 'aria-hidden': 'true' }),
                    close,
                ),
                list,
                footer,
                live,
            ),
        );
        document.body.append(dialog);

        input.addEventListener('input', () => schedule());
        input.addEventListener('keydown', onKey);
        close.addEventListener('click', () => hide());
        dialog.addEventListener('close', () => {
            controller?.abort();
            lastFocus?.focus?.();
        });
        // Esc: erst die Eingabe leeren, dann schließen
        dialog.addEventListener('cancel', (e) => {
            if (input.value) {
                e.preventDefault();
                input.value = '';
                schedule(0);
            }
        });
        // Klick neben das Panel schließt
        dialog.addEventListener('click', (e) => {
            if (e.target === dialog) {
                hide();
            }
        });
        scopeMenu.addEventListener('click', (e) => {
            const a = e.target.closest('a[data-prefix]');
            if (!a) {
                return;
            }
            e.preventDefault();
            // Suchbegriff behalten, nur das Kürzel tauschen
            const term = input.value.replace(/^(>|[a-z]+:)\s*/i, '');
            input.value = a.dataset.prefix ? (a.dataset.prefix === '>' ? '> ' : a.dataset.prefix + ': ') + term : term;
            input.focus();
            schedule(0);
        });
        list.addEventListener('pointermove', (e) => {
            const row = e.target.closest('.qn-row');
            if (row && row.dataset.index !== String(active)) {
                select(Number(row.dataset.index), false);
            }
        });
        list.addEventListener('click', (e) => {
            const clear = e.target.closest('.qn-clear');
            if (clear) {
                recentClear();
                schedule(0);
                return;
            }
            const link = e.target.closest('a');
            const row = e.target.closest('.qn-row');
            if (!link || !row) {
                return;
            }
            const entry = entries[Number(row.dataset.index)];
            if (link.getAttribute('href') === '#qn-favorite-page') {
                e.preventDefault();
                favoriteCurrentPage();
                return;
            }
            if (entry && link.classList.contains('qn-item')) {
                recentSave(entry.item);
            }
            if (!link.target && !e.metaKey && !e.ctrlKey && !e.shiftKey && e.button === 0) {
                hide();
            }
        });
    };

    // ---------------------------------------------------------------------------------------------- Darstellung

    const render = (groups, query, scope) => {
        const w = words(query);
        entries = [];
        list.replaceChildren();

        const current = cfg.prefixes.find((p) => p.key === scope);
        scopeToggle.querySelector('.qn-scope-label').textContent = current ? current.label : t.scopeAll;
        scopeToggle.classList.toggle('is-scoped', !!current);
        scopeMenu.querySelectorAll('a').forEach((a) => a.setAttribute('aria-current', String((a.dataset.key || '') === (current?.key || ''))));

        groups.forEach((group, g) => {
            const headId = 'qn-g' + g;
            const head = el('div', { class: 'qn-group-label', id: headId, role: 'presentation' }, group.label);
            if (group.key === '_recent') {
                head.append(el('button', { type: 'button', class: 'qn-clear', text: t.clear }));
            }
            const box = el('div', { class: 'qn-group', role: 'group', 'aria-labelledby': headId }, head);

            for (const item of group.items) {
                item.command = group.key === 'commands';
                const index = entries.length;
                const media = item.thumbnail
                    ? el('span', { class: 'qn-thumb' }, el('img', { src: item.thumbnail, alt: '', loading: 'lazy', decoding: 'async' }))
                    : el('span', { class: 'qn-icon' }, icon(item.icon));

                const main = el('a', {
                    class: 'qn-item',
                    id: 'qn-o' + index,
                    role: 'option',
                    href: item.url,
                    tabindex: '-1',
                    'aria-selected': 'false',
                    target: item.external ? '_blank' : null,
                    rel: item.external ? 'noopener noreferrer' : null,
                },
                media,
                el('span', { class: 'qn-text' },
                    el('span', { class: 'qn-title' }, highlight(item.title, w)),
                    item.meta ? el('span', { class: 'qn-meta' }, highlight(item.meta, w)) : null,
                ),
                item.badge ? el('span', { class: 'qn-badge', text: item.badge }) : null,
                );

                const actions = (item.actions || []).map((a) => el('a', {
                    class: 'qn-action',
                    href: a.url,
                    tabindex: '-1',
                    title: a.label,
                    'aria-label': a.label,
                    target: a.external ? '_blank' : null,
                    rel: a.external ? 'noopener noreferrer' : null,
                }, icon(a.icon)));

                // Stern: Favorit an/aus (⌘D)
                const star = item.favoritable ? el('button', {
                    type: 'button',
                    class: 'qn-fav' + (item.favorite ? ' is-on' : ''),
                    tabindex: '-1',
                    'aria-pressed': String(!!item.favorite),
                    title: (item.favorite ? t.favoriteRemove : t.favoriteAdd) + ' (' + mod + 'D)',
                    'aria-label': item.favorite ? t.favoriteRemove : t.favoriteAdd,
                }, icon('rex-icon fa-' + (item.favorite ? 'solid' : 'regular') + ' fa-star')) : null;

                const row = el('div', { class: 'qn-row', role: 'none', 'data-index': index },
                    main,
                    actions.length ? el('span', { class: 'qn-actions', role: 'none' }, ...actions) : null,
                    star,
                );
                box.append(row);
                const entry = { item, el: main, row, actions, star, group: group.key };
                star?.addEventListener('click', (e) => {
                    e.preventDefault();
                    toggleFavorite(entry);
                });
                entries.push(entry);
            }
            list.append(box);
        });

        if (!entries.length) {
            list.append(el('div', { class: 'qn-empty' },
                el('i', { class: 'rex-icon rex-icon-search', 'aria-hidden': 'true' }),
                el('p', { text: query.trim() ? t.empty : t.placeholder }),
            ));
        }

        active = -1;
        select(entries.length ? 0 : -1, false);
        list.scrollTop = 0;
        live.textContent = query.trim() ? t.results + ': ' + entries.length : '';
    };

    const select = (index, scroll = true) => {
        if (entries[active]) {
            entries[active].el.setAttribute('aria-selected', 'false');
            entries[active].row.classList.remove('is-active');
            entries[active].actions.forEach((a) => a.classList.remove('is-active'));
        }
        active = index;
        actionIndex = -1;
        const entry = entries[active];
        if (!entry) {
            input.removeAttribute('aria-activedescendant');
            return;
        }
        entry.el.setAttribute('aria-selected', 'true');
        entry.row.classList.add('is-active');
        input.setAttribute('aria-activedescendant', entry.el.id);
        if (scroll) {
            entry.row.scrollIntoView({ block: 'nearest' });
        }
    };

    const selectAction = (index) => {
        const entry = entries[active];
        if (!entry) {
            return;
        }
        entry.actions.forEach((a) => a.classList.remove('is-active'));
        actionIndex = Math.max(-1, Math.min(index, entry.actions.length - 1));
        const action = entry.actions[actionIndex];
        if (action) {
            action.classList.add('is-active');
            live.textContent = action.getAttribute('aria-label');
        } else {
            live.textContent = entry.item.title;
        }
    };

    // ---------------------------------------------------------------------------------------------- Suche

    const schedule = (delay = DEBOUNCE) => {
        clearTimeout(timer);
        timer = setTimeout(run, delay);
    };

    const recentGroup = (query) => {
        if (query.trim()) {
            return [];
        }
        const items = recentLoad();
        return items.length ? [{ key: '_recent', label: t.recent, items }] : [];
    };

    const run = async () => {
        const query = input.value;
        const mine = ++seq;
        controller?.abort();
        controller = new AbortController();
        dialog.classList.add('is-loading');

        // sofort: der eigene Verlauf, dann die Server-Treffer dazu
        if (!query.trim()) {
            render(recentGroup(query), query, null);
        }

        try {
            const category = new URLSearchParams(location.search).get('category_id');
            const url = cfg.url + '&q=' + encodeURIComponent(query) + (language() ? '&qn_language=' + encodeURIComponent(language()) : '')
                + (category ? '&qn_category=' + encodeURIComponent(category) : '');
            const res = await fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: controller.signal,
            });
            if (!res.ok) {
                throw new Error(String(res.status));
            }
            const data = await res.json();
            if (mine !== seq) {
                return;
            }
            // Favoriten ganz oben, dann der eigene Verlauf, dann die übrigen Quellen
            const favorites = data.groups.filter((g) => g.key === 'favorites');
            const others = data.groups.filter((g) => g.key !== 'favorites');
            render([...favorites, ...recentGroup(query), ...others], query, data.scope);
        } catch (e) {
            if (e.name === 'AbortError' || mine !== seq) {
                return;
            }
            list.replaceChildren(el('div', { class: 'qn-empty is-error' }, el('p', { text: t.error })));
            entries = [];
            select(-1);
        } finally {
            if (mine === seq) {
                dialog.classList.remove('is-loading');
            }
        }
    };

    // ---------------------------------------------------------------------------------------------- Favoriten

    const favoriteRequest = async (data) => {
        const body = new FormData();
        for (const [k, v] of Object.entries(data)) {
            body.append(k, v ?? '');
        }
        const res = await fetch(cfg.favoriteUrl, { method: 'POST', body, credentials: 'same-origin', headers: { Accept: 'application/json' } });
        if (!res.ok) {
            throw new Error(String(res.status));
        }
        return (await res.json()).favorite;
    };

    const toggleFavorite = async (entry) => {
        if (!entry?.star) {
            return;
        }
        const { item } = entry;
        try {
            const on = await favoriteRequest({ url: item.url, title: item.title, meta: item.meta, icon: item.icon });
            item.favorite = on;
            entry.star.classList.toggle('is-on', on);
            entry.star.setAttribute('aria-pressed', String(on));
            entry.star.title = (on ? t.favoriteRemove : t.favoriteAdd) + ' (' + mod + 'D)';
            entry.star.replaceChildren(icon('rex-icon fa-' + (on ? 'solid' : 'regular') + ' fa-star'));
            live.textContent = (on ? t.favoriteAdded : t.favoriteRemoved).replace('{0}', item.title);
            // Liste der Favoriten aktuell halten
            if (entry.group === 'favorites' || !input.value.trim()) {
                schedule(0);
            }
        } catch {
            live.textContent = t.error;
        }
    };

    // Befehl „Aktuelle Seite merken“: die Seite, auf der die Palette geöffnet wurde
    const favoriteCurrentPage = async () => {
        const title = (document.querySelector('.rex-pageheader-title')?.textContent || document.title.split(' · ')[0] || '').trim();
        // Bereich aus der Hauptnavigation, dazu der aktive Reiter der Seite (falls vorhanden)
        const crumbs = [...document.querySelectorAll('#rex-js-nav-main a[aria-current="page"], .rex-main [role="tablist"] [aria-selected="true"], .rex-main .rex-tabs [aria-current="page"]')]
            .map((a) => a.textContent.trim()).filter((text, i, all) => text && text !== title && all.indexOf(text) === i);
        try {
            const on = await favoriteRequest({ url: location.href, title, meta: crumbs.join(' › '), icon: 'rex-icon fa-solid fa-star' });
            live.textContent = (on ? t.favoriteAdded : t.favoriteRemoved).replace('{0}', title);
            input.value = '';
            schedule(0);
        } catch {
            live.textContent = t.error;
        }
    };

    // ---------------------------------------------------------------------------------------------- Tastatur

    const go = (newTab) => {
        const entry = entries[active];
        if (!entry) {
            return;
        }
        const action = entry.actions[actionIndex];
        const link = action || entry.el;
        if (link.getAttribute('href') === '#qn-favorite-page') {
            favoriteCurrentPage();
            return;
        }
        if (!action) {
            recentSave(entry.item);
        }
        if (newTab || link.target === '_blank') {
            window.open(link.href, '_blank', 'noopener');
            return;
        }
        hide();
        location.href = link.href;
    };

    const onKey = (e) => {
        if ((e.metaKey || e.ctrlKey) && !e.shiftKey && !e.altKey && e.key.toLowerCase() === 'd') {
            e.preventDefault();
            toggleFavorite(entries[active]);
            return;
        }
        switch (e.key) {
            case 'ArrowDown':
            case 'ArrowUp': {
                e.preventDefault();
                if (!entries.length) {
                    return;
                }
                const step = e.key === 'ArrowDown' ? 1 : -1;
                select((active + step + entries.length) % entries.length);
                break;
            }
            case 'PageDown':
            case 'PageUp': {
                // zur nächsten / vorigen Gruppe
                e.preventDefault();
                const group = entries[active]?.row.parentElement;
                const sibling = e.key === 'PageDown' ? group?.nextElementSibling : group?.previousElementSibling;
                const first = sibling?.querySelector('.qn-row');
                if (first) {
                    select(Number(first.dataset.index));
                }
                break;
            }
            case 'ArrowRight':
                if (entries[active]?.actions.length && input.selectionStart === input.value.length) {
                    e.preventDefault();
                    selectAction(actionIndex + 1);
                }
                break;
            case 'ArrowLeft':
                if (actionIndex >= 0) {
                    e.preventDefault();
                    selectAction(actionIndex - 1);
                }
                break;
            case 'Enter':
                e.preventDefault();
                go(e.metaKey || e.ctrlKey || e.shiftKey);
                break;
            case 'Backspace':
                // „m: “ als Ganzes entfernen
                if (/^(>|[a-z]+:)\s?$/i.test(input.value) && input.selectionStart === input.value.length) {
                    e.preventDefault();
                    input.value = '';
                    schedule(0);
                }
                break;
        }
    };

    // ---------------------------------------------------------------------------------------------- öffnen

    const show = () => {
        if (!dialog) {
            build();
        }
        if (dialog.open) {
            input.focus();
            input.select();
            return;
        }
        lastFocus = document.activeElement;
        dialog.showModal();
        input.value = '';
        input.focus();
        schedule(0);
    };

    const hide = () => {
        if (dialog?.open) {
            dialog.close();
        }
    };

    trigger.addEventListener('click', show);

    // andere Teile (z. B. das YForm-Panel) öffnen die Palette mit einer Eingabe: qn:search mit detail = Text
    document.addEventListener('qn:search', (e) => {
        show();
        input.value = String(e.detail ?? '');
        input.setSelectionRange(input.value.length, input.value.length);
        schedule(0);
    });

    document.addEventListener('keydown', (e) => {
        if ((e.metaKey || e.ctrlKey) && !e.altKey && !e.shiftKey && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            if (dialog?.open) {
                hide();
            } else {
                show();
            }
        }
    });

    // ======================================================================================== Strukturbaum

    const treeTrigger = document.querySelector('.qn-tree-trigger');
    if (treeTrigger && cfg.tree) {
        initTree(treeTrigger, cfg.tree);
    }

    function initTree(button, tc) {
        let panel, tree, langBox, search, domainSelect, domainWrap, titleEl;
        let domainKey = '';
        let query = '';
        let searchTimer = 0;
        let seq = 0;
        let languageId = Number(language()) || tc.languages[0]?.id || 0;

        const params = new URLSearchParams(location.search);
        const current = Number(params.get('article_id') || params.get('category_id') || 0);

        const typeIcon = { category: 'rex-icon rex-icon-category', article: 'rex-icon rex-icon-article', home: 'rex-icon rex-icon-sitestartarticle' };

        const fetchLevel = async (categoryId, open = 0, q = '') => {
            const url = tc.url + '&category_id=' + categoryId + '&qn_language=' + languageId + (open ? '&open=' + open : '')
                + (q ? '&q=' + encodeURIComponent(q) : '') + (domainKey ? '&domain=' + encodeURIComponent(domainKey) : '');
            const res = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!res.ok) {
                throw new Error(String(res.status));
            }
            return res.json();
        };

        const node = (n, level) => {
            const li = el('li', {
                class: 'qn-tree-item',
                role: 'treeitem',
                tabindex: '-1',
                'aria-level': String(level),
                'aria-expanded': n.children ? String(!!n.expanded) : null,
                'aria-current': n.id === current ? 'page' : null,
                'data-id': n.id,
                'data-url': n.url,
            });
            const row = el('div', { class: 'qn-node' + (n.online ? '' : ' is-offline') + (n.id === current ? ' is-current' : '') + (query && n.match ? ' is-match' : '') },
                n.children ? el('span', { class: 'qn-twisty', 'aria-hidden': 'true' }) : el('span', { class: 'qn-twisty is-leaf', 'aria-hidden': 'true' }),
                icon(typeIcon[n.type]),
                el('a', { class: 'qn-node-link', href: n.url, tabindex: '-1' }, query && n.match ? highlight(n.name, words(query)) : n.name),
                n.online ? null : el('span', { class: 'qn-node-badge', text: t.offline }),
                el('span', { class: 'qn-node-actions' },
                    n.edit ? el('a', { class: 'qn-action', href: n.edit, tabindex: '-1', title: t.edit, 'aria-label': t.edit }, icon('rex-icon rex-icon-edit')) : null,
                    n.view ? el('a', { class: 'qn-action', href: n.view, tabindex: '-1', target: '_blank', rel: 'noopener noreferrer', title: t.view, 'aria-label': t.view }, icon('rex-icon rex-icon-external-link')) : null,
                ),
            );
            li.append(row);
            if (n.children) {
                const group = el('ul', { class: 'qn-tree-group', role: 'group' });
                if (n.items) {
                    group.append(...n.items.map((c) => node(c, level + 1)));
                    li.dataset.loaded = '1';
                }
                li.append(group);
            }
            return li;
        };

        const visible = () => [...tree.querySelectorAll('.qn-tree-item')].filter((li) => {
            for (let p = li.parentElement.closest('.qn-tree-item'); p; p = p.parentElement.closest('.qn-tree-item')) {
                if (p.getAttribute('aria-expanded') !== 'true') {
                    return false;
                }
            }
            return true;
        });

        const focusItem = (li) => {
            if (!li) {
                return;
            }
            tree.querySelectorAll('.qn-tree-item[tabindex="0"]').forEach((x) => x.setAttribute('tabindex', '-1'));
            li.setAttribute('tabindex', '0');
            li.focus({ preventScroll: true });
            li.firstElementChild.scrollIntoView({ block: 'nearest' });
        };

        const expand = async (li, open) => {
            if (!li.hasAttribute('aria-expanded')) {
                return;
            }
            if (!open) {
                li.setAttribute('aria-expanded', 'false');
                return;
            }
            li.setAttribute('aria-expanded', 'true');
            if (li.dataset.loaded) {
                return;
            }
            li.classList.add('is-loading');
            try {
                const data = await fetchLevel(Number(li.dataset.id));
                const level = Number(li.getAttribute('aria-level')) + 1;
                li.querySelector('.qn-tree-group').replaceChildren(...data.items.map((c) => node(c, level)));
                li.dataset.loaded = '1';
            } catch {
                li.setAttribute('aria-expanded', 'false');
            } finally {
                li.classList.remove('is-loading');
            }
        };

        const go = (li, newTab) => {
            const url = li?.dataset.url;
            if (!url) {
                return;
            }
            if (newTab) {
                window.open(url, '_blank', 'noopener');
            } else {
                panel.hidePopover();
                location.href = url;
            }
        };

        // ohne Suche: Baum bis zur aktuellen Seite; mit Suche: nur Treffer und ihre Kategorien
        const load = async () => {
            const q = search.value.trim();
            const mine = ++seq;
            tree.setAttribute('aria-busy', 'true');
            panel.classList.add('is-loading');
            try {
                const data = await fetchLevel(0, q ? 0 : current, q);
                if (mine !== seq) {
                    return;
                }
                query = q;
                languageId = data.language;
                renderDomains(data.domains || [], data.domain);
                langBox?.querySelectorAll('button').forEach((b) => b.setAttribute('aria-pressed', String(Number(b.dataset.id) === languageId)));
                tree.replaceChildren(...data.items.map((n) => node(n, 1)));
                if (!data.items.length) {
                    tree.append(el('li', { class: 'qn-tree-empty', role: 'none', text: q ? t.empty : t.treeEmpty }));
                }
                const target = q
                    ? tree.querySelector('.qn-node.is-match')?.parentElement
                    : tree.querySelector('[aria-current="page"]') || tree.querySelector('.qn-tree-item');
                if (target) {
                    target.setAttribute('tabindex', '0');
                    target.firstElementChild.scrollIntoView({ block: q ? 'nearest' : 'center' });
                }
            } catch {
                tree.replaceChildren(el('li', { class: 'qn-tree-empty is-error', role: 'none', text: t.error }));
            } finally {
                if (mine === seq) {
                    tree.removeAttribute('aria-busy');
                    panel.classList.remove('is-loading');
                }
            }
        };

        // Auswahl der Domain (erst ab zwei Domains) – Select-Komponente des Cores, ersetzt den Titel im Kopf
        const renderDomains = (domains, selected) => {
            domainKey = selected || '';
            if (domains.length < 2) {
                domainWrap.hidden = true;
                titleEl.hidden = false;
                return;
            }
            const value = domainKey || '*';
            domainSelect.replaceChildren(...[{ key: '*', label: t.allDomains }, ...domains].map((d) => el('option', { value: d.key, selected: d.key === value, text: d.label })));
            domainSelect.value = value;
            domainWrap.hidden = false;
            titleEl.hidden = true;
        };

        const place = () => {
            const r = button.getBoundingClientRect();
            const width = Math.min(360, window.innerWidth - 16);
            const left = Math.max(8, Math.min(r.right - width, window.innerWidth - width - 8));
            // CSSOM, kein style-Attribut im Markup (CSP)
            panel.style.top = Math.round(r.bottom + 8) + 'px';
            panel.style.left = Math.round(left) + 'px';
            panel.style.width = width + 'px';
        };

        const build = () => {
            tree = el('ul', { class: 'qn-tree', id: 'qn-tree-list', role: 'tree', 'aria-label': t.tree });
            search = el('input', {
                type: 'search',
                class: 'qn-tree-input',
                placeholder: t.treeSearch,
                'aria-label': t.treeSearch,
                'aria-controls': 'qn-tree-list',
                autocomplete: 'off',
                spellcheck: 'false',
                enterkeyhint: 'go',
            });
            const searchRow = el('div', { class: 'qn-tree-search' }, el('i', { class: 'rex-icon rex-icon-search', 'aria-hidden': 'true' }), search);
            titleEl = el('strong', { text: t.tree });
            domainSelect = el('select', { class: 'rex-input', 'data-rex-select': true, 'aria-label': t.domain });
            domainSelect.addEventListener('change', () => {
                domainKey = domainSelect.value;
                load();
            });
            domainWrap = el('div', { class: 'qn-tree-domain', hidden: true }, domainSelect);
            const head = el('div', { class: 'qn-tree-head' }, titleEl, domainWrap);
            if (tc.languages.length > 1) {
                langBox = el('div', { class: 'qn-tree-langs', role: 'group' });
                for (const l of tc.languages) {
                    langBox.append(el('button', { type: 'button', 'data-id': l.id, title: l.name, 'aria-pressed': String(l.id === languageId), text: l.code }));
                }
                head.append(langBox);
                langBox.addEventListener('click', (e) => {
                    const b = e.target.closest('button');
                    if (b) {
                        languageId = Number(b.dataset.id);
                        load();
                    }
                });
            }
            head.append(el('a', { class: 'qn-tree-all', href: tc.structure }, t.treeOpen));

            panel = el('div', { id: 'qn-tree', class: 'qn-tree-panel', popover: 'auto' }, head, searchRow, el('div', { class: 'qn-tree-scroll' }, tree), el('span', { class: 'qn-spinner', 'aria-hidden': 'true' }));
            document.body.append(panel);
            button.popoverTargetElement = panel;

            panel.addEventListener('beforetoggle', (e) => {
                if (e.newState === 'open') {
                    place();
                    search.value = '';
                    // Select-Komponente des Cores für die Domain-Auswahl (erst jetzt: das Skript des Cores ist sicher geladen)
                    if (!domainSelect.closest('.rex-select')) {
                        domainWrap.dispatchEvent(new CustomEvent('rex:ready', { bubbles: true }));
                    }
                    load();
                }
            });
            panel.addEventListener('toggle', (e) => {
                button.setAttribute('aria-expanded', String(e.newState === 'open'));
                if (e.newState === 'open') {
                    search.focus();
                } else if (panel.contains(document.activeElement) || document.activeElement === document.body) {
                    button.focus();
                }
            });
            window.addEventListener('resize', () => panel.matches(':popover-open') && place());

            // Suche: tippen filtert, ↓ geht in den Baum, ↵ öffnet den ersten Treffer
            search.addEventListener('input', () => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(load, 160);
            });
            search.addEventListener('keydown', (e) => {
                const first = tree.querySelector('.qn-tree-item[tabindex="0"]') || tree.querySelector('.qn-tree-item');
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    focusItem(first);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    go(first, e.metaKey || e.ctrlKey || e.shiftKey);
                }
            });

            tree.addEventListener('click', (e) => {
                const li = e.target.closest('.qn-tree-item');
                if (!li) {
                    return;
                }
                if (e.target.closest('.qn-twisty') && !e.target.closest('.is-leaf')) {
                    expand(li, li.getAttribute('aria-expanded') !== 'true');
                    focusItem(li);
                    return;
                }
                if (e.target.closest('a') && !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.target.closest('a').target) {
                    panel.hidePopover();
                }
            });
            tree.addEventListener('dblclick', (e) => {
                const li = e.target.closest('.qn-tree-item');
                if (li && !e.target.closest('a')) {
                    expand(li, li.getAttribute('aria-expanded') !== 'true');
                }
            });

            tree.addEventListener('keydown', (e) => {
                const li = e.target.closest('.qn-tree-item');
                if (!li) {
                    return;
                }
                const items = visible();
                const i = items.indexOf(li);
                switch (e.key) {
                    case 'ArrowDown':
                        focusItem(items[i + 1]);
                        break;
                    case 'ArrowUp':
                        if (i === 0) {
                            search.focus();
                        } else {
                            focusItem(items[i - 1]);
                        }
                        break;
                    case 'Home':
                        focusItem(items[0]);
                        break;
                    case 'End':
                        focusItem(items[items.length - 1]);
                        break;
                    case 'ArrowRight':
                        if (li.getAttribute('aria-expanded') === 'false') {
                            expand(li, true);
                        } else if (li.getAttribute('aria-expanded') === 'true') {
                            focusItem(li.querySelector('.qn-tree-item'));
                        }
                        break;
                    case 'ArrowLeft':
                        if (li.getAttribute('aria-expanded') === 'true') {
                            expand(li, false);
                        } else {
                            focusItem(li.parentElement.closest('.qn-tree-item'));
                        }
                        break;
                    case 'Enter':
                        go(li, e.metaKey || e.ctrlKey || e.shiftKey);
                        break;
                    default:
                        // Tippen springt zum nächsten Eintrag mit diesem Anfangsbuchstaben
                        if (e.key.length === 1 && !e.metaKey && !e.ctrlKey && !e.altKey) {
                            const k = e.key.toLowerCase();
                            const next = [...items.slice(i + 1), ...items.slice(0, i)]
                                .find((x) => x.querySelector('.qn-node-link').textContent.trim().toLowerCase().startsWith(k));
                            focusItem(next);
                            break;
                        }
                        return;
                }
                e.preventDefault();
            });
        };

        // gleich anlegen (ohne Daten): so übernimmt popovertarget das Öffnen und Schließen per Knopf
        build();
    }

    // ======================================================================================== YForm-Tabellen

    const yformTrigger = document.querySelector('.qn-yform-trigger');
    if (yformTrigger && cfg.yform) {
        initYForm(yformTrigger, cfg.yform);
    }

    function initYForm(button, tables) {
        const filter = el('input', { type: 'search', class: 'qn-tree-input', placeholder: t.yformFilter, 'aria-label': t.yformFilter, autocomplete: 'off', spellcheck: 'false' });
        const list = el('ul', { class: 'qn-yform-list', 'aria-label': t.yform });
        const rows = tables.map((table) => {
            const li = el('li', { class: 'qn-yform-row', 'data-label': table.label.toLowerCase() },
                el('a', { class: 'qn-yform-link', href: table.url }, icon(table.icon), el('span', { text: table.label })),
                table.add ? el('a', { class: 'qn-action qn-yform-add', href: table.add, title: t.yformAdd + ': ' + table.label, 'aria-label': t.yformAdd + ': ' + table.label }, icon('rex-icon rex-icon-add')) : null,
            );
            list.append(li);
            return li;
        });
        const search = el('button', { type: 'button', class: 'qn-yform-search' }, icon('rex-icon rex-icon-search'), ' ' + t.yformSearch + ' ', el('kbd', { text: 'y:' }));

        const panel = el('div', { id: 'qn-yform', class: 'qn-tree-panel qn-yform-panel', popover: 'auto' },
            el('div', { class: 'qn-tree-head' }, el('strong', { text: t.yform })),
            tables.length > 6 ? el('div', { class: 'qn-tree-search' }, el('i', { class: 'rex-icon rex-icon-search', 'aria-hidden': 'true' }), filter) : null,
            el('div', { class: 'qn-tree-scroll' }, list),
            el('div', { class: 'qn-yform-foot' }, search),
        );
        document.body.append(panel);
        button.popoverTargetElement = panel;

        const place = () => {
            const r = button.getBoundingClientRect();
            const width = Math.min(320, window.innerWidth - 16);
            const left = Math.max(8, Math.min(r.right - width, window.innerWidth - width - 8));
            // CSSOM, kein style-Attribut im Markup (CSP)
            panel.style.top = Math.round(r.bottom + 8) + 'px';
            panel.style.left = Math.round(left) + 'px';
            panel.style.width = width + 'px';
        };
        const links = () => [...list.querySelectorAll('.qn-yform-row:not([hidden]) a, .qn-yform-search')].concat(search).filter((a, i, all) => all.indexOf(a) === i);

        panel.addEventListener('beforetoggle', (e) => {
            if (e.newState === 'open') {
                place();
                filter.value = '';
                rows.forEach((r) => { r.hidden = false; });
            }
        });
        panel.addEventListener('toggle', (e) => {
            button.setAttribute('aria-expanded', String(e.newState === 'open'));
            if (e.newState === 'open') {
                (panel.contains(filter) ? filter : list.querySelector('a'))?.focus();
            }
        });
        window.addEventListener('resize', () => panel.matches(':popover-open') && place());

        filter.addEventListener('input', () => {
            const q = filter.value.trim().toLowerCase();
            rows.forEach((r) => { r.hidden = q !== '' && !r.dataset.label.includes(q); });
        });
        // ↑ ↓ zwischen Filter, Tabellen und „In Datensätzen suchen“
        panel.addEventListener('keydown', (e) => {
            if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') {
                return;
            }
            const all = [...(panel.contains(filter) ? [filter] : []), ...links()];
            const i = all.indexOf(document.activeElement);
            const next = all[(i + (e.key === 'ArrowDown' ? 1 : -1) + all.length) % all.length];
            if (next) {
                e.preventDefault();
                next.focus();
            }
        });
        filter.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                list.querySelector('.qn-yform-row:not([hidden]) .qn-yform-link')?.click();
            }
        });
        search.addEventListener('click', () => {
            panel.hidePopover();
            document.dispatchEvent(new CustomEvent('qn:search', { detail: 'y: ' }));
        });
    }
})();
