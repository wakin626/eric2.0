document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('select.filter-select').forEach(function (sel) {
        var placeholder = sel.options[0] ? sel.options[0].textContent : 'Select...';

        var allOptions = Array.from(sel.options).map(function (o) {
            return { value: o.value, text: o.textContent };
        });

        var currentText = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].textContent : placeholder;

        var container = document.createElement('div');
        container.className = 'searchable-dropdown';
        container.style.cssText = 'display:inline-block;position:relative;vertical-align:middle;';

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-sm btn-outline-secondary dropdown-toggle text-start';
        btn.style.cssText = 'width:' + (sel.style.width || '200px') + ';overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
        btn.textContent = currentText === placeholder ? placeholder : currentText;

        var panel = document.createElement('div');
        panel.className = 'dropdown-panel';
        panel.style.cssText = 'display:none;position:absolute;top:100%;left:0;z-index:1050;background:#fff;border:1px solid #dee2e6;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.15);width:' + (sel.style.width || '200px') + ';max-height:280px;';

        var searchWrap = document.createElement('div');
        searchWrap.style.cssText = 'padding:6px;border-bottom:1px solid #eee;';

        var searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.className = 'form-control form-control-sm';
        searchInput.placeholder = 'Search...';
        searchInput.style.cssText = 'width:100%;';
        searchWrap.appendChild(searchInput);

        var listWrap = document.createElement('div');
        listWrap.style.cssText = 'overflow-y:auto;max-height:220px;';

        sel.style.display = 'none';
        sel.parentNode.insertBefore(container, sel);
        container.appendChild(btn);
        container.appendChild(panel);
        panel.appendChild(searchWrap);
        panel.appendChild(listWrap);
        panel.appendChild(sel);

        function renderOptions(query) {
            listWrap.innerHTML = '';
            var q = (query || '').toLowerCase().trim();
            allOptions.forEach(function (opt) {
                if (q && opt.text.toLowerCase().indexOf(q) === -1) return;
                var item = document.createElement('div');
                item.className = 'dropdown-item-custom';
                item.textContent = opt.text;
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
                    btn.textContent = opt.text === placeholder ? placeholder : opt.text;
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

        function openPanel() {
            closeAllPanels();
            panel.style.display = 'block';
            searchInput.value = '';
            renderOptions('');
            searchInput.focus();
        }

        function closePanel() {
            panel.style.display = 'none';
            searchInput.value = '';
        }

        function closeAllPanels() {
            document.querySelectorAll('.searchable-dropdown .dropdown-panel').forEach(function (p) {
                p.style.display = 'none';
            });
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

        renderOptions('');
    });
});
