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

    trigger.querySelector('.qn-trigger-kbd').textContent = isMac ? '⌘K' : 'Strg K';

    let dialog, input, list, live, scopeChip, chips;
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

    const words = (q) => q.replace(/^[a-z]+:\s*/i, '').toLowerCase().split(/\s+/).filter(Boolean);

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
        if (item.external) {
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
        scopeChip = el('span', { class: 'qn-scope', hidden: true });
        list = el('div', { class: 'qn-list', id: 'qn-list', role: 'listbox', 'aria-label': t.results });
        live = el('div', { class: 'rex-u-visually-hidden', 'aria-live': 'polite', 'aria-atomic': 'true' });

        chips = el('div', { class: 'qn-prefixes' });
        for (const p of cfg.prefixes) {
            const b = el('button', { type: 'button', class: 'qn-prefix', 'data-prefix': p.prefix, title: t.scope.replace('%s', p.label) },
                el('kbd', { text: p.prefix + ':' }), ' ' + p.label);
            chips.append(b);
        }

        const hint = (keys, label) => el('span', { class: 'qn-hint' }, ...keys.map((k) => el('kbd', { text: k })), ' ' + label);
        const footer = el('div', { class: 'qn-footer' },
            el('div', { class: 'qn-hints' },
                hint(['↑', '↓', '↵'], t.open),
                hint([mod + '↵'], t.newTab),
                hint(['→'], t.actions),
                hint(['esc'], t.close),
            ),
            chips,
        );

        const close = el('button', { type: 'button', class: 'qn-close', 'aria-label': t.close }, el('kbd', { text: 'esc' }));

        dialog = el('dialog', { class: 'qn-dialog', 'aria-label': t.label },
            el('div', { class: 'qn-panel' },
                el('div', { class: 'qn-head' },
                    el('i', { class: 'rex-icon rex-icon-search qn-head-icon', 'aria-hidden': 'true' }),
                    scopeChip,
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
        chips.addEventListener('click', (e) => {
            const b = e.target.closest('.qn-prefix');
            if (b) {
                input.value = b.dataset.prefix + ': ';
                input.focus();
                schedule(0);
            }
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

        scopeChip.hidden = !scope;
        scopeChip.textContent = scope ? (cfg.prefixes.find((p) => p.key === scope)?.label ?? scope) : '';

        groups.forEach((group, g) => {
            const headId = 'qn-g' + g;
            const head = el('div', { class: 'qn-group-label', id: headId, role: 'presentation' }, group.label);
            if (group.key === '_recent') {
                head.append(el('button', { type: 'button', class: 'qn-clear', text: t.clear }));
            }
            const box = el('div', { class: 'qn-group', role: 'group', 'aria-labelledby': headId }, head);

            for (const item of group.items) {
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

                const row = el('div', { class: 'qn-row', role: 'none', 'data-index': index },
                    main,
                    actions.length ? el('span', { class: 'qn-actions', role: 'none' }, ...actions) : null,
                );
                box.append(row);
                entries.push({ item, el: main, row, actions });
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
            const url = cfg.url + '&q=' + encodeURIComponent(query) + (language() ? '&qn_language=' + encodeURIComponent(language()) : '');
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
            render([...recentGroup(query), ...data.groups], query, data.scope);
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

    // ---------------------------------------------------------------------------------------------- Tastatur

    const go = (newTab) => {
        const entry = entries[active];
        if (!entry) {
            return;
        }
        const action = entry.actions[actionIndex];
        const link = action || entry.el;
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
                if (/^[a-z]+:\s?$/i.test(input.value) && input.selectionStart === input.value.length) {
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
})();
