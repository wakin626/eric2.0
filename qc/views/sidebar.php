<ul class="nav flex-column sidebar-menu">
    <li class="nav-item">
        <a class="nav-link <?= (($page_title ?? '') === 'Receiving Inspection') ? 'active' : '' ?>" href="?controller=qc&action=receivingInspection">
            <i class="bi bi-box-seam me-2"></i>Receiving Inspection
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= (($page_title ?? '') === 'For Delivery Inspection') ? 'active' : '' ?>" href="?controller=qc&action=deliveryInspection">
            <i class="bi bi-truck me-2"></i>For Delivery Inspection
        </a>
    </li>
    <li class="nav-item mt-auto">
        <a class="nav-link" href="?controller=auth&action=logout" style="color: #64748b; transition: all 0.2s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#64748b'">
            <i class="bi bi-box-arrow-left me-2"></i>Logout
        </a>
    </li>
</ul>
