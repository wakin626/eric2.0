<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Admin Dashboard' ?></title>
    <link href="public/css/bootstrap.min.css" rel="stylesheet">
    <link href="public/css/bootstrap-icons.min.css" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #1a2332;
            --sidebar-hover: #2d3a4f;
            --accent: #4a90d9;
            --accent-hover: #3a7bc8;
            --sidebar-width: 250px;
        }
        body { background: #f1f5f9; font-family: 'Inter', sans-serif; }

        .sidebar {
            min-height: 100vh;
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            width: var(--sidebar-width);
        }
        .sidebar.collapsed { transform: translateX(-100%); }

        .main-wrapper {
            width: 100%;
            transition: padding-left 0.3s ease;
            padding-left: var(--sidebar-width);
        }
        .main-wrapper.sidebar-closed { padding-left: 0; }

        #sidebarToggle { z-index: 1001; }

        .sidebar .sidebar-menu { flex: 1; padding-top: 0.5rem; }
        .sidebar .nav-link {
            color: #94a3b8;
            padding: 0.65rem 1.25rem;
            margin: 2px 10px;
            border-radius: 6px;
            transition: all 0.2s ease;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        .sidebar .nav-link i { margin-right: 10px; width: 20px; text-align: center; }
        .sidebar .nav-link:hover { background: var(--sidebar-hover); color: #e2e8f0; }
        .sidebar .nav-link.active {
            background: rgba(74,144,217,0.15);
            color: #fff;
            font-weight: 600;
            border-left: 3px solid var(--accent);
            margin-left: 7px;
            padding-left: 10px;
        }
        .sidebar .nav-link .badge { font-size: 0.65rem; padding: 0.25em 0.55em; }

        .stat-card {
            background: #fff;
            border: none;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            overflow: hidden;
            border-left: 4px solid #e2e8f0;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }

        .data-card {
            background: #fff;
            border: none;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .data-card .card-header {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.85rem 1.25rem;
            font-weight: 600;
            color: #1e293b;
            font-size: 0.9rem;
        }
        .form-control:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(74,144,217,0.12); }
        .btn-primary { background: var(--accent); border-color: var(--accent); }
        .btn-primary:hover { background: var(--accent-hover); }
        .quick-add { border-left: 4px solid var(--accent); }
        .search-box { position: relative; }
        .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; z-index: 2; }
        .search-box input { padding-left: 38px; }
        th.sortable { cursor: pointer; user-select: none; }
        th.sortable:hover { background-color: #f1f5f9; }
        th.sortable i { font-size: 10px; margin-left: 4px; }
        .table tbody tr:hover > td { background: #e8f2fc !important; }
        .table tbody tr:nth-child(even) > td { background: #f1f5f9 !important; }
        .table tbody tr:nth-child(odd) > td { background: #ffffff !important; }
        .alert { animation: slideInDown 0.3s ease; }
        @keyframes slideInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .alert-success { border-left: 4px solid #22c55e; background: #f0fdf4; color: #166534; }
        .alert-danger { border-left: 4px solid #ef4444; background: #fef2f2; color: #991b1b; }
        .alert-warning { border-left: 4px solid #f97316; background: #fff7ed; color: #9a3412; }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- SIDEBAR NAV - Fixed position, slides in/out via CSS transform -->
            <nav class="sidebar" id="sidebar">
                <div class="text-center py-3 border-bottom" style="border-color: rgba(255,255,255,0.08) !important">
                    <h5 class="mb-0" style="color: #fff; font-weight: 700; letter-spacing: 0.5px; font-size: 1rem;"><?= ucfirst($_SESSION['department'] ?? 'User') ?></h5>
                    <small style="color: #64748b; font-size: 0.7rem;"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></small>
                </div>
<ul class="nav flex-column sidebar-menu">
<?php $currentAction = $_GET['action'] ?? ''; ?>
<li class="nav-item">
<a class="nav-link <?= $currentAction === '' ? 'active' : '' ?>" href="?controller=admin">
<i class="bi bi-speedometer2 me-2"></i>Dashboard
</a>
</li>
<li class="nav-item">
<a class="nav-link <?= $currentAction === 'purchaseOrders' ? 'active' : '' ?>" href="?controller=admin&action=purchaseOrders">
<i class="bi bi-cart3 me-2"></i>Customer PO
</a>
</li>
<li class="nav-item">
<a class="nav-link <?= $currentAction === 'delivered' ? 'active' : '' ?>" href="?controller=admin&action=delivered">
<i class="bi bi-truck me-2"></i>Deliveries
<?php if (!empty($deliveryReportsCount) && $deliveryReportsCount > 0): ?>
    <span class="badge bg-danger float-end"><?= $deliveryReportsCount ?></span>
<?php endif; ?>
</a>
</li>
<li class="nav-item">
<a class="nav-link <?= $currentAction === 'productionHistory' ? 'active' : '' ?>" href="?controller=admin&action=productionHistory">
<i class="bi bi-clock-history me-2"></i>Production History
<?php if (!empty($reportsCount) && $reportsCount > 0): ?>
    <span class="badge bg-warning text-dark float-end"><?= $reportsCount ?></span>
<?php endif; ?>
</a>
</li>
<li class="nav-item">
<a class="nav-link <?= in_array($currentAction, ['customers', 'customerCreate', 'customerEdit', 'customerDelete', 'customerUpdate', 'customerToggleStatus']) ? 'active' : '' ?>" href="?controller=admin&action=customers">
<i class="bi bi-people me-2"></i>Customers
</a>
</li>
<li class="nav-item">
<a class="nav-link <?= in_array($currentAction, ['items', 'itemCreate', 'itemEdit', 'itemDelete', 'itemToggleStatus', 'itemUpdate']) ? 'active' : '' ?>" href="?controller=admin&action=items">
<i class="bi bi-box-seam me-2"></i>Items
</a>
</li>
<li class="nav-item">
<a class="nav-link <?= $currentAction === 'excessProduction' ? 'active' : '' ?>" href="?controller=warehouse&action=excessProduction">
<i class="bi bi-exclamation-triangle me-2"></i>Excess Production
</a>
</li>
</ul>
            <div class="mt-auto p-3 border-top" style="border-color: rgba(255,255,255,0.08) !important">
                    <a href="?controller=auth&action=logout" class="nav-link text-center" style="color: #64748b; font-size: 0.875rem; transition: all 0.2s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#64748b'">
                        <i class="bi bi-box-arrow-left me-2"></i>Logout
                    </a>
                </div>
            </nav>
            
            <!-- MAIN CONTENT WRAPPER - Full width always, just adjusts padding -->
            <div class="main-wrapper" id="mainWrapper">
            <main class="p-4">
                <!-- HEADER ROW - Contains toggle button on the right side -->
                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                    <h4 class="mb-0"><?= $page_title ?? 'Dashboard' ?></h4>
                    <!-- TOGGLE BUTTON - Click to slide sidebar in/out -->
                    <button class="btn btn-outline-dark" id="sidebarToggle" title="Toggle Sidebar">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                </div>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle me-2"></i><?= $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-circle me-2"></i><?= $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($reportsCount) && $reportsCount > 0 && basename($_SERVER['PHP_SELF'] ?? '') !== 'index.php'): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong><?= $reportsCount ?></strong> lot number report(s) require action.
                        <a href="?controller=admin&action=productionHistory" class="alert-link ms-1">Review now</a>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($deliveryReportsCount) && $deliveryReportsCount > 0 && basename($_SERVER['PHP_SELF'] ?? '') !== 'index.php'): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-flag-fill me-2"></i>
                        <strong><?= $deliveryReportsCount ?></strong> delivery report(s) require action.
                        <a href="?controller=admin&action=delivered" class="alert-link ms-1">Review now</a>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?= $content ?? '' ?>
            </main>
            </div>
        </div>
    </div>
    <script src="public/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get DOM elements by their IDs
    const sidebar = document.getElementById('sidebar');
    const mainWrapper = document.getElementById('mainWrapper');
    const toggleBtn = document.getElementById('sidebarToggle');

    /* TOGGLE LOGIC:
       1. We use classList.toggle() to add/remove 'collapsed' class on sidebar
       2. We also toggle 'sidebar-closed' class on main-wrapper
       3. This triggers CSS transitions for smooth animation
    */
    toggleBtn.addEventListener('click', function() {
        // Toggle the 'collapsed' class on sidebar - triggers translateX transform
        sidebar.classList.toggle('collapsed');
        
        // Toggle 'sidebar-closed' class on main-wrapper - adjusts padding
        mainWrapper.classList.toggle('sidebar-closed');
        
        // Swap icon to show current state (list = open, layout = closed)
        const icon = toggleBtn.querySelector('i');
        if (sidebar.classList.contains('collapsed')) {
            icon.classList.remove('bi-list');
            icon.classList.add('bi-layout-text-sidebar-reverse');
        } else {
            icon.classList.remove('bi-layout-text-sidebar-reverse');
            icon.classList.add('bi-list');
        }
    });

    // Modal reset logic (existing code preserved)
    document.querySelectorAll('.modal').forEach(function(modal) {
        modal.addEventListener('hidden.bs.modal', function() {
            const form = this.querySelector('form');
            if (form) {
                form.reset();
                form.querySelectorAll('input[type="hidden"]').forEach(function(input) {
                    input.value = '';
                });
            }
        });
    });

    // Auto-dismiss flash alerts after 4 seconds
    const flashAlerts = document.querySelectorAll('.alert.alert-dismissible.fade.show');
    flashAlerts.forEach(function(alertElement) {
        setTimeout(function() {
            const alert = bootstrap.Alert.getOrCreateInstance(alertElement);
            alert.close();
        }, 4000);
    });
});
</script>
</body>
</html>