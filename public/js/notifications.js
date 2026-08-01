document.addEventListener('DOMContentLoaded', function () {
    var bell = document.getElementById('notifBell');
    var dropdown = document.getElementById('notifDropdown');
    var badge = document.getElementById('notifBadge');
    var list = document.getElementById('notifList');
    var markAllBtn = document.getElementById('markAllReadBtn');

    if (!bell || !dropdown) return;

    var pollInterval = null;

    function toggleDropdown() {
        dropdown.classList.toggle('d-none');
        if (!dropdown.classList.contains('d-none')) {
            loadNotifications();
        }
    }

    bell.addEventListener('click', function (e) {
        e.stopPropagation();
        toggleDropdown();
    });

    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target) && !bell.contains(e.target)) {
            dropdown.classList.add('d-none');
        }
    });

    if (markAllBtn) {
        markAllBtn.addEventListener('click', function () {
            fetch('?controller=notification&action=markAllRead', { method: 'POST' })
                .then(function (r) { return r.json(); })
                .then(function () {
                    loadNotifications();
                });
        });
    }

    function loadNotifications() {
        fetch('?controller=notification&action=getUnread')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                renderBadge(data.count);
                renderList(data.notifications);
            })
            .catch(function () {
                renderBadge(0);
                renderList([]);
            });
    }

    function renderBadge(count) {
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }
    }

    function renderList(notifications) {
        if (!list) return;
        list.innerHTML = '';

        if (!notifications || notifications.length === 0) {
            var empty = document.createElement('div');
            empty.className = 'text-center text-muted py-4';
            empty.style.fontSize = '0.85rem';
            empty.textContent = 'No new notifications';
            list.appendChild(empty);
            return;
        }

        var iconMap = {
            delivery: 'bi-truck',
            production: 'bi-gear-wide-connected',
            po: 'bi-cart3',
            finance: 'bi-receipt',
            qc: 'bi-check-circle',
            backload: 'bi-arrow-return-left'
        };
        var colorMap = {
            delivery: '#3b82f6',
            production: '#f59e0b',
            po: '#8b5cf6',
            finance: '#10b981',
            qc: '#ef4444',
            backload: '#f97316'
        };

        notifications.forEach(function (n) {
            var item = document.createElement('a');
            item.href = n.target_url || '#';
            item.className = 'd-flex align-items-start px-3 py-2 border-bottom text-decoration-none notif-item';
            item.style.transition = 'background 0.15s';

            var icon = document.createElement('div');
            icon.className = 'me-2 mt-1';
            icon.style.width = '28px';
            icon.style.height = '28px';
            icon.style.borderRadius = '50%';
            icon.style.background = (colorMap[n.type] || '#6b7280') + '18';
            icon.style.display = 'flex';
            icon.style.alignItems = 'center';
            icon.style.justifyContent = 'center';
            icon.style.flexShrink = '0';
            icon.innerHTML = '<i class="bi ' + (iconMap[n.type] || 'bi-bell') + '" style="font-size:0.8rem;color:' + (colorMap[n.type] || '#6b7280') + '"></i>';

            var body = document.createElement('div');
            body.style.flex = '1';
            body.style.minWidth = '0';

            var title = document.createElement('div');
            title.style.fontSize = '0.8rem';
            title.style.fontWeight = '600';
            title.style.color = '#1e293b';
            title.textContent = n.title;

            var msg = document.createElement('div');
            msg.style.fontSize = '0.75rem';
            msg.style.color = '#64748b';
            msg.style.lineHeight = '1.3';
            msg.style.whiteSpace = 'nowrap';
            msg.style.overflow = 'hidden';
            msg.style.textOverflow = 'ellipsis';
            msg.textContent = n.message;

            var time = document.createElement('div');
            time.style.fontSize = '0.65rem';
            time.style.color = '#94a3b8';
            time.style.marginTop = '2px';
            time.textContent = formatTime(n.date_created);

            body.appendChild(title);
            body.appendChild(msg);
            body.appendChild(time);

            item.appendChild(icon);
            item.appendChild(body);

            item.addEventListener('mouseenter', function () { item.style.background = '#f8fafc'; });
            item.addEventListener('mouseleave', function () { item.style.background = ''; });
            item.addEventListener('click', function (e) {
                e.preventDefault();
                markAsRead(n.notification_id, n.target_url);
            });

            list.appendChild(item);
        });
    }

    function markAsRead(notificationId, url) {
        var formData = new FormData();
        formData.append('notification_id', notificationId);

        fetch('?controller=notification&action=markRead', {
            method: 'POST',
            body: formData
        }).then(function () {
            if (url) {
                window.location.href = url;
            } else {
                loadNotifications();
            }
        }).catch(function () {
            if (url) window.location.href = url;
        });
    }

    function formatTime(dateStr) {
        if (!dateStr) return '';
        var d = new Date(dateStr.replace(' ', 'T'));
        var now = new Date();
        var diff = Math.floor((now - d) / 1000);

        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';

        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return months[d.getMonth()] + ' ' + d.getDate();
    }

    loadNotifications();
    pollInterval = setInterval(loadNotifications, 30000);
});
