/**
 * Searchable Dropdown (global)
 * Source UX: Customer PO "All Customers" filter.
 * Enhances any <select class="filter-select"> into a button + searchable panel.
 *
 * Public API:
 *   window.initSearchableDropdown(sel)        - enhance one select (idempotent)
 *   window.initSearchableDropdowns(root)      - enhance all select.filter-select under root
 *   window.refreshSearchableDropdown(sel)     - re-snapshot options after dynamic fill
 *
 * Constraints: no external libraries; panel styles are inline (unchanged look).
 * Operational:
 *   - wrapper/button stretch to 100% of parent column (form alignment)
 *   - panel uses position:fixed + getBoundingClientRect (escapes overflow/clip)
 *   - panel z-index 1070 (above .modal 1055 / .modal-body)
 *   - required-select validation focuses the custom trigger button
 */
(function () {
    'use strict';

    var PANEL_Z = '1070';

    function inModal(sel) {
        return !!(sel.closest && sel.closest('.modal'));
    }

    function snapshotOptions(sel) {
        return Array.prototype.slice.call(sel.options).map(function (o) {
            return { value: o.value, text: o.textContent, disabled: !!o.disabled };
        });
    }

    function closeAllPanels() {
        document.querySelectorAll('.searchable-dropdown .dropdown-panel').forEach(function (p) {
            p.style.display = 'none';
        });
    }

    /**
     * Form validation guard: when a form fails checkValidity / submit /
     * invalid, shift focus to the custom trigger of any enhanced required
     * select that is still empty (the native select is display:none).
     * Re-applied on a timeout so it wins over the browser focusing the
     * first invalid text control.
     */
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.checkValidity || form.checkValidity()) return;
        guardFormValidity(form);
        setTimeout(function () { guardFormValidity(form); }, 0);
    }, true);

    document.addEventListener('invalid', function (e) {
        var el = e.target;
        if (!el) return;
        var form = el.form;
        if (el.tagName === 'SELECT') {
            var wrap = el.closest && el.closest('.searchable-dropdown');
            if (wrap) e.preventDefault();
        }
        if (form) {
            setTimeout(function () { guardFormValidity(form); }, 0);
        }
    }, true);

    function guardFormValidity(form) {
        if (!form || !form.querySelectorAll) return false;
        var requiredSelects = form.querySelectorAll('select.filter-select[required]');
        for (var i = 0; i < requiredSelects.length; i++) {
            var sel = requiredSelects[i];
            if (!sel.value) {
                var wrap = sel.closest('.searchable-dropdown');
                var btn = wrap && wrap.querySelector('button');
                if (btn) {
                    btn.focus();
                    btn.classList.add('is-invalid');
                    (function (b) {
                        setTimeout(function () { b.classList.remove('is-invalid'); }, 2000);
                    })(btn);
                    return true;
                }
            }
        }
        return false;
    }

    window.guardSearchableFormValidity = guardFormValidity;

    /**
     * cloneNode(true) copies data-searchable="1" and the wrapper DOM but not
     * _searchableApi or event listeners. Unwrap the dead wrapper so init can rebuild.
     */
    function unwrapSearchableClone(sel) {
        var wrap = sel.closest && sel.closest('.searchable-dropdown');
        if (wrap && wrap.parentNode) {
            wrap.parentNode.insertBefore(sel, wrap);
            wrap.parentNode.removeChild(wrap);
        }
        sel.removeAttribute('data-searchable');
        sel.style.display = '';
        try { delete sel._searchableApi; } catch (e) { sel._searchableApi = undefined; }
    }

    function initSearchableDropdown(sel) {
        if (!sel || sel.tagName !== 'SELECT') return null;
        if (sel.getAttribute('data-searchable') === '1') {
            if (sel._searchableApi) return sel._searchableApi;
            unwrapSearchableClone(sel);
        }
        if (!sel.parentNode) return null;

        var placeholder = sel.options[0] ? sel.options[0].textContent : 'Select...';
        var allOptions = snapshotOptions(sel);
        var isSm = sel.classList.contains('form-select-sm');
        var inlineW = (sel.style.width || '').trim();
        var isFull = !inlineW || inlineW === 'auto';
        var positioned = false;

        var currentText = sel.selectedIndex >= 0 && sel.options[sel.selectedIndex]
            ? sel.options[sel.selectedIndex].textContent
            : placeholder;

        var container = document.createElement('div');
        container.className = 'searchable-dropdown searchable-dropdown-wrapper' +
            (isFull ? ' is-full' : '');
        container.style.cssText = isFull
            ? 'display:block;position:relative;width:100%;'
            : 'display:inline-block;position:relative;vertical-align:middle;width:' + inlineW + ';';

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn' + (isSm ? ' btn-sm' : '') +
            ' btn-outline-secondary dropdown-toggle text-start searchable-dropdown-toggle';
        btn.style.cssText = 'width:100%;display:flex;align-items:center;justify-content:space-between;' +
            'text-align:left;min-height:' + (isSm ? '31px' : '38px') +
            ';overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
        btn.textContent = currentText;

        var panel = document.createElement('div');
        panel.className = 'dropdown-panel searchable-dropdown-menu';
        panel.style.cssText = 'display:none;position:fixed;top:0;left:0;z-index:' + PANEL_Z +
            ';background:#fff;border:1px solid #dee2e6;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.15);width:100%;max-height:280px;';

        var searchWrap = document.createElement('div');
        searchWrap.style.cssText = 'padding:6px;border-bottom:1px solid #eee;';

        var searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.className = 'form-control form-control-sm';
        searchInput.placeholder = 'Search...';
        searchInput.style.cssText = 'width:100%;';
        searchInput.setAttribute('autocomplete', 'off');
        searchWrap.appendChild(searchInput);

        var listWrap = document.createElement('div');
        listWrap.className = 'options-list';
        listWrap.style.cssText = 'overflow-y:auto;max-height:220px;';

        sel.style.display = 'none';
        sel.setAttribute('data-searchable', '1');
        sel.parentNode.insertBefore(container, sel);
        container.appendChild(btn);
        container.appendChild(panel);
        panel.appendChild(searchWrap);
        panel.appendChild(listWrap);
        panel.appendChild(sel);

        function labelFor(value) {
            for (var i = 0; i < allOptions.length; i++) {
                if (allOptions[i].value === value) return allOptions[i].text;
            }
            return placeholder;
        }

        function updateButtonLabel() {
            btn.textContent = sel.selectedIndex >= 0 && sel.options[sel.selectedIndex]
                ? sel.options[sel.selectedIndex].textContent
                : placeholder;
        }

        function resnapshot() {
            allOptions = snapshotOptions(sel);
            updateButtonLabel();
            if (panel.style.display === 'block') {
                renderOptions(searchInput.value);
            }
        }

        function renderOptions(query) {
            listWrap.innerHTML = '';
            var q = (query || '').toLowerCase().trim();
            allOptions.forEach(function (opt) {
                if (q && opt.text.toLowerCase().indexOf(q) === -1) return;
                var item = document.createElement('div');
                item.className = 'dropdown-item-custom';
                item.textContent = opt.text;
                if (opt.disabled) {
                    item.style.cssText = 'padding:6px 10px;cursor:not-allowed;font-size:13px;color:#adb5bd;';
                    listWrap.appendChild(item);
                    return;
                }
                item.style.cssText = 'padding:6px 10px;cursor:pointer;font-size:13px;';
                if (opt.value === sel.value) {
                    item.style.background = '#e9ecef';
                    item.style.fontWeight = '600';
                }
                item.addEventListener('mouseenter', function () { item.style.background = '#f0f0f0'; });
                item.addEventListener('mouseleave', function () {
                    item.style.background = opt.value === sel.value ? '#e9ecef' : '';
                });
                item.addEventListener('click', function (e) {
                    e.stopPropagation();
                    sel.value = opt.value;
                    btn.textContent = opt.text;
                    btn.classList.remove('is-invalid');
                    closePanel();
                    sel.dispatchEvent(new Event('change'));
                });
                listWrap.appendChild(item);
            });
            if (listWrap.children.length === 0) {
                var noResult = document.createElement('div');
                noResult.textContent = 'No results found';
                noResult.style.cssText = 'padding:8px 10px;color:#999;font-size:13px;text-align:center;';
                listWrap.appendChild(noResult);
            }
        }

        function positionPanel() {
            var rect = btn.getBoundingClientRect();
            var w = Math.max(rect.width, 160);
            panel.style.position = 'fixed';
            panel.style.width = w + 'px';
            panel.style.zIndex = PANEL_Z;
            panel.style.left = '0px';
            panel.style.top = '0px';

            var h = panel.offsetHeight;
            if (!h) h = 280;

            var spaceBelow = window.innerHeight - rect.bottom;
            var spaceAbove = rect.top;
            var top;
            if (h + 8 > spaceBelow && spaceAbove > spaceBelow) {
                top = Math.max(4, rect.top - h - 2);
            } else {
                top = rect.bottom + 2;
                if (top + h > window.innerHeight - 4) {
                    top = Math.max(4, window.innerHeight - h - 4);
                }
            }

            var left = rect.left;
            if (left + w > window.innerWidth - 4) {
                left = Math.max(4, window.innerWidth - w - 4);
            }
            if (left < 4) left = 4;

            if (top < 4) top = 4;
            if (top + h > window.innerHeight - 4) {
                top = Math.max(4, window.innerHeight - h - 4);
            }

            panel.style.left = left + 'px';
            panel.style.top = top + 'px';
        }

        function onViewportChange() {
            if (positioned && panel.style.display === 'block') positionPanel();
        }

        function openPanel() {
            closeAllPanels();
            positioned = true;
            panel.style.display = 'block';
            searchInput.value = '';
            renderOptions('');
            positionPanel();
            searchInput.focus();
            positionPanel();
            window.addEventListener('scroll', onViewportChange, true);
            window.addEventListener('resize', onViewportChange);
        }

        function closePanel() {
            positioned = false;
            panel.style.display = 'none';
            searchInput.value = '';
            window.removeEventListener('scroll', onViewportChange, true);
            window.removeEventListener('resize', onViewportChange);
        }

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (panel.style.display === 'none') {
                openPanel();
            } else {
                closePanel();
            }
        });

        searchInput.addEventListener('input', function () {
            renderOptions(searchInput.value);
        });

        searchInput.addEventListener('click', function (e) {
            e.stopPropagation();
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closePanel();
        });

        document.addEventListener('click', function (e) {
            if (!container.contains(e.target)) closePanel();
        });

        if (typeof MutationObserver !== 'undefined') {
            var mo = new MutationObserver(function () { resnapshot(); });
            mo.observe(sel, { childList: true, subtree: true });
        }

        renderOptions('');
        updateButtonLabel();

        var api = {
            refresh: resnapshot,
            open: openPanel,
            close: closePanel,
            button: btn
        };
        sel._searchableApi = api;
        return api;
    }

    function initSearchableDropdowns(root) {
        root = root || document;
        if (!root.querySelectorAll) return;
        root.querySelectorAll('select.filter-select').forEach(function (sel) {
            initSearchableDropdown(sel);
        });
    }

    function refreshSearchableDropdown(sel) {
        if (sel && sel._searchableApi && typeof sel._searchableApi.refresh === 'function') {
            sel._searchableApi.refresh();
        } else if (sel && sel.tagName === 'SELECT') {
            initSearchableDropdown(sel);
        }
    }

    window.initSearchableDropdown = initSearchableDropdown;
    window.initSearchableDropdowns = initSearchableDropdowns;
    window.refreshSearchableDropdown = refreshSearchableDropdown;

    document.addEventListener('DOMContentLoaded', function () {
        initSearchableDropdowns(document);
    });
})();
