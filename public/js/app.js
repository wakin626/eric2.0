document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('select.filter-select').forEach(function (sel) {
        var wrapper = document.createElement('div');
        wrapper.className = 'searchable-dropdown position-relative';
        wrapper.style.cssText = 'display:inline-block;';

        var searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.className = 'form-control form-control-sm';
        searchInput.placeholder = 'Search ' + (sel.options[0] ? sel.options[0].textContent.replace('All ', '') : '...') + '...';
        searchInput.style.cssText = 'width:' + (sel.style.width || '200px') + ';display:none;position:absolute;top:-34px;left:0;z-index:10;';

        sel.parentNode.insertBefore(wrapper, sel);
        wrapper.appendChild(searchInput);
        wrapper.appendChild(sel);

        sel.style.width = '100%';

        var allOptions = Array.from(sel.options).map(function (o) {
            return { value: o.value, text: o.textContent };
        });

        function showSearch() {
            searchInput.style.display = 'block';
            searchInput.value = '';
            searchInput.focus();
        }

        function hideSearch() {
            searchInput.style.display = 'none';
            searchInput.value = '';
            restoreOptions();
        }

        function restoreOptions() {
            sel.innerHTML = '';
            allOptions.forEach(function (opt) {
                var o = document.createElement('option');
                o.value = opt.value;
                o.textContent = opt.text;
                sel.appendChild(o);
            });
        }

        sel.addEventListener('mousedown', function (e) {
            e.preventDefault();
            if (searchInput.style.display === 'none') {
                showSearch();
            } else {
                hideSearch();
            }
        });

        searchInput.addEventListener('input', function () {
            var query = searchInput.value.toLowerCase().trim();
            sel.innerHTML = '';

            allOptions.forEach(function (opt) {
                if (opt.value === '' || (query && opt.text.toLowerCase().indexOf(query) !== -1) || !query) {
                    var o = document.createElement('option');
                    o.value = opt.value;
                    o.textContent = opt.text;
                    sel.appendChild(o);
                }
            });

            if (sel.options.length > 1) {
                sel.selectedIndex = 1;
            }
        });

        searchInput.addEventListener('blur', function () {
            setTimeout(function () {
                hideSearch();
            }, 200);
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                hideSearch();
            }
        });

        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target)) {
                hideSearch();
            }
        });
    });
});
