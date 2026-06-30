<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Finance Dashboard' ?></title>
    <link href="public/css/bootstrap.min.css" rel="stylesheet">
    <link href="public/css/bootstrap-icons.min.css" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 250px; }
        
        .sidebar { 
            transition: transform 0.3s ease;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            width: var(--sidebar-width);
        }
        
        .sidebar.collapsed {
            transform: translateX(-100%);
        }
        
        .main-wrapper {
            width: 100%;
            transition: padding-left 0.3s ease;
            padding-left: var(--sidebar-width);
        }
        
        .main-wrapper.sidebar-closed {
            padding-left: 0;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <nav class="sidebar" id="sidebar">
                <div class="text-center text-white py-4 border-bottom border-secondary">
                    <h5>Finance</h5>
                    <small><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></small>
                </div>
                <ul class="nav flex-column mt-2 sidebar-menu">
                    <?php $currentAction = $_GET['action'] ?? ''; ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentAction === '' ? 'active' : '' ?>" href="?controller=finance">
                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentAction === 'purchaseOrders' ? 'active' : '' ?>" href="?controller=finance&action=purchaseOrders">
                            <i class="bi bi-cart3 me-2"></i>Customer PO
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentAction === 'readyToDeliver' ? 'active' : '' ?>" href="?controller=finance&action=readyToDeliver">
                            <i class="bi bi-box-seam me-2"></i>Ready to Deliver
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= in_array($currentAction, ['deliveries', 'viewDelivery']) ? 'active' : '' ?>" href="?controller=finance&action=deliveries">
                            <i class="bi bi-truck me-2"></i>Deliveries
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= in_array($currentAction, ['priceList', 'priceListCreate', 'priceListUpdate', 'priceListToggle']) ? 'active' : '' ?>" href="?controller=finance&action=priceList">
                            <i class="bi bi-tag me-2"></i>Price List
                        </a>
                    </li>
                    <li class="nav-item border-top border-secondary mt-3 pt-2">
                        <a class="nav-link text-white-50" href="?controller=auth&action=logout">
                            <i class="bi bi-box-arrow-left me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="main-wrapper" id="mainWrapper">
            <main class="p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                    <h4 class="mb-0"><?= $page_title ?? 'Dashboard' ?></h4>
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
                <?= $content ?? '' ?>
            </main>
            </div>
        </div>
    </div>
    <script src="public/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mainWrapper = document.getElementById('mainWrapper');
    const toggleBtn = document.getElementById('sidebarToggle');

    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        mainWrapper.classList.toggle('sidebar-closed');
        const icon = toggleBtn.querySelector('i');
        if (sidebar.classList.contains('collapsed')) {
            icon.classList.remove('bi-list');
            icon.classList.add('bi-layout-text-sidebar-reverse');
        } else {
            icon.classList.remove('bi-layout-text-sidebar-reverse');
            icon.classList.add('bi-list');
        }
    });

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
