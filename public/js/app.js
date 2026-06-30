document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.form-select.filter-select').forEach(function (sel) {
        sel._searchTimer = null;
        sel._searchStr = '';

        sel.addEventListener('input', function () {
            var val = sel.value;
            if (val === '') {
                sel._searchStr = '';
                return;
            }
            var opts = Array.from(sel.options);
            var match = opts.find(function (o) {
                return o.value !== '' && o.text.toLowerCase().indexOf(val.toLowerCase()) === 0;
            });
            if (match) {
                sel.value = match.value;
            }
            sel.dispatchEvent(new Event('change'));
        });

        sel.addEventListener('keydown', function (e) {
            clearTimeout(sel._searchTimer);
            if (e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
                sel._searchStr += e.key.toLowerCase();
            } else if (e.key === 'Backspace') {
                sel._searchStr = sel._searchStr.slice(0, -1);
            } else {
                return;
            }
            sel._searchTimer = setTimeout(function () { sel._searchStr = ''; }, 600);

            if (sel._searchStr === '') return;
            var opts = Array.from(sel.options);
            var match = opts.find(function (o) {
                return o.value !== '' && o.text.toLowerCase().indexOf(sel._searchStr) === 0;
            });
            if (!match) {
                match = opts.find(function (o) {
                    return o.value !== '' && o.text.toLowerCase().indexOf(sel._searchStr) !== -1;
                });
            }
            if (match) {
                sel.value = match.value;
                sel.dispatchEvent(new Event('change'));
            }
        });
    });
});
