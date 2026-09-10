(function() {
    var PING_INTERVAL = 10000;
    var PING_TIMEOUT = 5000;
    var PING_URL = 'api/ping.php';

    var isOnline = true;
    var pingTimer = null;
    var pillEl = null;
    var bannerEl = null;
    var disabledButtons = [];

    function createPill() {
        pillEl = document.createElement('span');
        pillEl.id = 'networkStatusPill';
        pillEl.className = 'badge bg-success text-white';
        pillEl.style.cssText = 'font-size:0.65rem; cursor:default;';
        pillEl.innerHTML = '<i class="bi bi-wifi me-1"></i>Online';

        var headerRight = document.querySelector('.d-flex.align-items-center.gap-2');
        if (headerRight) {
            var toggleBtn = headerRight.querySelector('#sidebarToggle');
            if (toggleBtn) {
                headerRight.insertBefore(pillEl, toggleBtn);
            } else {
                headerRight.appendChild(pillEl);
            }
        }
    }

    function createBanner() {
        bannerEl = document.createElement('div');
        bannerEl.id = 'networkBanner';
        bannerEl.className = 'text-center text-white fw-bold py-2';
        bannerEl.style.cssText = 'position:fixed;top:0;left:0;width:100%;z-index:9999;background:#dc3545;display:none;';
        bannerEl.innerHTML = '<i class="bi bi-wifi-off me-2"></i>Local network connection lost. Form submissions paused until connection is restored.';
        document.body.appendChild(bannerEl);
    }

    function setOnline() {
        if (isOnline) return;
        isOnline = true;

        if (pillEl) {
            pillEl.className = 'badge bg-success text-white';
            pillEl.style.cssText = 'font-size:0.65rem; cursor:default;';
            pillEl.innerHTML = '<i class="bi bi-wifi me-1"></i>Online';
        }
        if (bannerEl) bannerEl.style.display = 'none';

        disabledButtons.forEach(function(btn) {
            btn.disabled = false;
        });
        disabledButtons = [];
    }

    function setOffline() {
        if (!isOnline) return;
        isOnline = false;

        if (pillEl) {
            pillEl.className = 'badge bg-danger text-white';
            pillEl.style.cssText = 'font-size:0.65rem; cursor:default;';
            pillEl.innerHTML = '<i class="bi bi-wifi-off me-1"></i>Disconnected';
        }
        if (bannerEl) bannerEl.style.display = '';

        disabledButtons = [];
        document.querySelectorAll('form button[type="submit"]').forEach(function(btn) {
            if (!btn.disabled) {
                btn.disabled = true;
                disabledButtons.push(btn);
            }
        });
        var actionBtns = ['confirmSaveBtn', 'confirmDeliveryBtn'];
        actionBtns.forEach(function(id) {
            var btn = document.getElementById(id);
            if (btn && !btn.disabled) {
                btn.disabled = true;
                disabledButtons.push(btn);
            }
        });
    }

    function ping() {
        var controller = new AbortController();
        var timeoutId = setTimeout(function() { controller.abort(); }, PING_TIMEOUT);

        fetch(PING_URL, {
            method: 'HEAD',
            cache: 'no-store',
            signal: controller.signal
        })
        .then(function(r) {
            clearTimeout(timeoutId);
            if (r.ok) {
                setOnline();
            } else {
                setOffline();
            }
        })
        .catch(function() {
            clearTimeout(timeoutId);
            setOffline();
        });
    }

    function startHeartbeat() {
        stopHeartbeat();
        ping();
        pingTimer = setInterval(ping, PING_INTERVAL);
    }

    function stopHeartbeat() {
        if (pingTimer) {
            clearInterval(pingTimer);
            pingTimer = null;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        createPill();
        createBanner();
        startHeartbeat();
    });

    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopHeartbeat();
        } else {
            startHeartbeat();
        }
    });
})();
