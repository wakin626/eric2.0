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
            --sidebar-bg: #2c3e50;
            --sidebar-hover: #34495e;
            --accent: #3498db;
            /* Fixed sidebar width - matches col-md-2 (16.666667%) */
            --sidebar-width: 250px;
        }
        body { background: #f4f6f9; }
        
        /* SIDEBAR - Fixed positioning creates an overlay that slides in/out */
        .sidebar { 
            min-height: 100vh; 
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            /* transition: Enables smooth sliding animation when class changes */
            /* transform: Allows us to slide the sidebar horizontally */
            transition: transform 0.3s ease;
            /* position: fixed removes from document flow - sits on top of content */
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            width: var(--sidebar-width);
        }
        
        /* COLLAPSED STATE - translateX(-100%) moves sidebar completely off-screen left */
        /* Negative 100% means shift left by its own full width */
        .sidebar.collapsed {
            transform: translateX(-100%);
        }
        
        /* MAIN CONTENT - Always full width, never affected by sidebar state */
        /* We don't use margin-left because we want content to always fill the screen */
        .main-wrapper {
            width: 100%;
            /* transition: Smoothly adjusts padding when toggle button appears/disappears */
            transition: padding-left 0.3s ease;
            padding-left: var(--sidebar-width);
        }
        
        /* When sidebar is collapsed, remove left padding so content starts at edge */
        .main-wrapper.sidebar-closed {
            padding-left: 0;
        }
        
        /* Toggle button styling - always visible */
        #sidebarToggle {
            /* Ensures button is always on top of other elements */
            z-index: 1001;
        }

        .sidebar .sidebar-menu { flex: 1; }
        .sidebar .nav-link { 
            color: #ecf0f1; 
            padding: 10px 15px;
            margin: 2px 8px;
            border-radius: 5px;
        }
        .sidebar .nav-link:hover { background: var(--sidebar-hover); }
        .sidebar .nav-link.active { background: var(--accent); }
        .stat-card {
            background: #fff;
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .data-card {
            background: #fff;
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .form-control:focus { border-color: var(--accent); box-shadow: 0 0 0 0.2rem rgba(52,152,219,.25); }
        .btn-primary { background: var(--accent); border-color: var(--accent); }
        .btn-primary:hover { background: #2980b9; }
        .quick-add { border-left: 4px solid var(--accent); }
        .search-box { position: relative; }
        .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #adb5bd; z-index: 2; }
        .search-box input { padding-left: 38px; }
        th.sortable { cursor: pointer; user-select: none; }
        th.sortable:hover { background-color: #f8f9fa; }
        th.sortable i { font-size: 10px; margin-left: 4px; }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- SIDEBAR NAV - Fixed position, slides in/out via CSS transform -->
            <nav class="sidebar" id="sidebar">
                <div class="text-center text-white py-4 border-bottom border-secondary">
                    <h5><?= ucfirst($_SESSION['department'] ?? 'User') ?></h5>
                    <small><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></small>
                </div>
<ul class="nav flex-column mt-2 sidebar-menu">
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
</ul>
            <div class="mt-auto p-3 border-top border-secondary">
                    <a href="?controller=auth&action=logout" class="nav-link text-white-50 text-center">
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
});
</script>
</body>
</html>