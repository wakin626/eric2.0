document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('select.filter-select').forEach(function (sel) {
        var wrapper = document.createElement('div');
        wrapper.className = 'searchable-dropdown position-relative mb-2';
        wrapper.style.cssText = 'display:inline-block;';

        var searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.className = 'form-control form-control-sm';
        searchInput.placeholder = 'Search ' + (sel.options[0] ? sel.options[0].textContent.replace('All ', '') : '...') + '...';
        searchInput.style.cssText = 'width:' + (sel.style.width || '200px') + ';margin-bottom:4px;';

        sel.parentNode.insertBefore(wrapper, sel);
        wrapper.appendChild(searchInput);
        wrapper.appendChild(sel);

        sel.style.width = '100%';

        var allOptions = Array.from(sel.options).map(function (o) {
            return { value: o.value, text: o.textContent, selected: o.selected };
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
                sel.selectedIndex = sel.options.length > 1 ? 1 : 0;
            }

            sel.dispatchEvent(new Event('change'));
        });

        searchInput.addEventListener('focus', function () {
            searchInput.select();
        });
    });
});
