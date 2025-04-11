<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'Warehouse Management System') ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/admin/assets/css/style.css" rel="stylesheet">
</head>
<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="/admin">Warehouse System</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!">
            <i class="fas fa-bars"></i>
        </button>
        <div class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
            <div class="input-group">
                <input class="form-control" type="text" placeholder="Search..." id="globalSearch">
                <button class="btn btn-primary" type="button"><i class="fas fa-search"></i></button>
            </div>
        </div>
        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user fa-fw"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="/admin/profile">Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="/admin/logout">Logout</a></li>
                </ul>
            </li>
        </ul>
    </nav>

    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <div class="sb-sidenav-menu-heading">Core</div>
                        <a class="nav-link" href="/admin/dashboard">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Dashboard
                        </a>
                        
                        <div class="sb-sidenav-menu-heading">Inventory</div>
                        <a class="nav-link" href="/admin/products">
                            <div class="sb-nav-link-icon"><i class="fas fa-box"></i></div>
                            Products
                        </a>
                        <a class="nav-link" href="/admin/categories">
                            <div class="sb-nav-link-icon"><i class="fas fa-tags"></i></div>
                            Categories
                        </a>
                        <a class="nav-link" href="/admin/suppliers">
                            <div class="sb-nav-link-icon"><i class="fas fa-truck"></i></div>
                            Suppliers
                        </a>
                        
                        <div class="sb-sidenav-menu-heading">Operations</div>
                        <a class="nav-link" href="/admin/purchases">
                            <div class="sb-nav-link-icon"><i class="fas fa-shopping-cart"></i></div>
                            Purchases
                        </a>
                        <a class="nav-link" href="/admin/sales">
                            <div class="sb-nav-link-icon"><i class="fas fa-cash-register"></i></div>
                            Sales
                        </a>
                        <a class="nav-link" href="/admin/returns">
                            <div class="sb-nav-link-icon"><i class="fas fa-undo"></i></div>
                            Returns
                        </a>
                        
                        <div class="sb-sidenav-menu-heading">Reports</div>
                        <a class="nav-link" href="/admin/reports/inventory">
                            <div class="sb-nav-link-icon"><i class="fas fa-chart-bar"></i></div>
                            Inventory Report
                        </a>
                        <a class="nav-link" href="/admin/reports/sales">
                            <div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>
                            Sales Report
                        </a>
                    </div>
                </div>
                <div class="sb-sidenav-footer">
                    <div class="small">Logged in as:</div>
                    <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>
                </div>
            </nav>
        </div>
        <div id="layoutSidenav_content">
            <main> 