<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Warehouse Dashboard' ?></title>
    <link href="public/css/bootstrap.min.css" rel="stylesheet">
    <link href="public/css/bootstrap-icons.min.css" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
    <style>
        /* Fixed sidebar width */
        :root { --sidebar-width: 250px; }
        
        /* SIDEBAR - Fixed position creates overlay that slides in/out */
        .sidebar { 
            /* transition: Enables smooth sliding animation */
            transition: transform 0.3s ease;
            /* position: fixed removes from document flow - sits on top */
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            width: var(--sidebar-width);
        }
        
        /* COLLAPSED STATE - translateX(-100%) moves sidebar off-screen left */
        .sidebar.collapsed {
            transform: translateX(-100%);
        }
        
        /* MAIN CONTENT - Always full width, adjusts padding only */
        .main-wrapper {
            width: 100%;
            /* Smoothly adjusts padding when sidebar toggles */
            transition: padding-left 0.3s ease;
            padding-left: var(--sidebar-width);
        }
        
        /* When sidebar is closed, remove left padding */
        .main-wrapper.sidebar-closed {
            padding-left: 0;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- SIDEBAR NAV - Slides in/out via CSS transform -->
            <nav class="sidebar" id="sidebar">
                <div class="text-center text-white py-4 border-bottom border-secondary">
                    <h5><?= ucfirst($_SESSION['department'] ?? 'Warehouse') ?></h5>
                    <small><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></small>
                </div>
                <?php $currentAction = $_GET['action'] ?? ''; ?>
                <ul class="nav flex-column mt-2 sidebar-menu">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentAction === '' ? 'active' : '' ?>" href="?controller=warehouse">
                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentAction === 'purchaseOrders' ? 'active' : '' ?>" href="?controller=warehouse&action=purchaseOrders">
                            <i class="bi bi-cart3 me-2"></i>Customer PO
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= in_array($currentAction, ['deliveries', 'createDelivery']) ? 'active' : '' ?>" href="?controller=warehouse&action=deliveries">
                            <i class="bi bi-truck me-2"></i>Deliveries
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentAction === 'productionHistory' ? 'active' : '' ?>" href="?controller=warehouse&action=productionHistory">
                            <i class="bi bi-clock-history me-2"></i>Production History
                            <?php if (!empty($reportsCount) && $reportsCount > 0): ?>
                                <span class="badge bg-warning text-dark float-end"><?= $reportsCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item border-top border-secondary mt-3 pt-2">
                        <a class="nav-link text-white-50" href="?controller=auth&action=logout">
                            <i class="bi bi-box-arrow-left me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </nav>
            
            <!-- MAIN CONTENT - Full width always -->
            <div class="main-wrapper" id="mainWrapper">
            <main class="p-4">
                <!-- Header with toggle button -->
                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                    <h4 class="mb-0"><?= $page_title ?? 'Dashboard' ?></h4>
                    <!-- TOGGLE BUTTON - Click to slide sidebar -->
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
                <?php if (!empty($reportsCount) && $reportsCount > 0): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong><?= $reportsCount ?></strong> lot number report(s) require action.
                        <a href="?controller=warehouse&action=productionHistory" class="alert-link ms-1">Review now</a>
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
    // Get DOM elements
    const sidebar = document.getElementById('sidebar');
    const mainWrapper = document.getElementById('mainWrapper');
    const toggleBtn = document.getElementById('sidebarToggle');

    /* TOGGLE LOGIC:
       1. classList.toggle() adds/removes 'collapsed' on sidebar
       2. Triggers CSS transition: transform for smooth slide
       3. main-wrapper adjusts padding to fill gap when sidebar hidden
    */
    toggleBtn.addEventListener('click', function() {
        // Toggle 'collapsed' class - triggers translateX transform
        sidebar.classList.toggle('collapsed');
        
        // Toggle padding adjustment on main content
        mainWrapper.classList.toggle('sidebar-closed');
        
        // Swap icon to reflect current state
        const icon = toggleBtn.querySelector('i');
        if (sidebar.classList.contains('collapsed')) {
            icon.classList.remove('bi-list');
            icon.classList.add('bi-layout-text-sidebar-reverse');
        } else {
            icon.classList.remove('bi-layout-text-sidebar-reverse');
            icon.classList.add('bi-list');
        }
    });

    // Modal reset logic (existing code)
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