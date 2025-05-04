<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../models/User.php';
require_once '../../models/Employee.php';
require_once '../../models/Permission.php';

// Check if user has permission to manage accounts
require_once '../../includes/check_permission.php';
checkPermission('manage_accounts');

// Create a database connection
$db = new Database();
$conn = $db->getConnection();

// Create instances
$userModel = new User($conn);
$employeeModel = new Employee($conn);
$permissionModel = new Permission($conn);

// Get all user roles
$roles = $permissionModel->getAllRoles();

?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بەڕێوەبردنی بەکارهێنەران - سیستەمی کۆگا</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <!-- Global CSS -->
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <!-- Page CSS -->
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/input.css">
</head>
<body>
    <!-- Main Content Wrapper -->
    <div id="content">
        <!-- Navbar container - will be populated by JavaScript -->
        <div id="navbar-container"></div>

        <!-- Sidebar container - will be populated by JavaScript -->
        <div id="sidebar-container"></div>

        <!-- Main content -->
        <div class="main-content p-3" id="main-content" style="margin-top: 100px;">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <h3 class="page-title">بەڕێوەبردنی بەکارهێنەران</h3>
                        <a href="add_user.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i> زیادکردنی بەکارهێنەر
                        </a>
                    </div>
                </div>

                <!-- User Management Card -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm card-qiuck-style">
                            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">لیستی بەکارهێنەران</h5>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary refresh-btn me-2" id="refreshUserList">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-container">
                                    <!-- Table Controls -->
                                    <div class="table-controls mb-3">
                                        <div class="row align-items-center">
                                            <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                                                <div class="records-per-page">
                                                    <label class="me-2">نیشاندان:</label>
                                                    <div class="custom-select-wrapper">
                                                        <select id="userRecordsPerPage" class="form-select form-select-sm rounded-pill">
                                                            <option value="5">5</option>
                                                            <option value="10" selected>10</option>
                                                            <option value="25">25</option>
                                                            <option value="50">50</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-8 col-sm-6">
                                                <div class="search-container">
                                                    <div class="input-group">
                                                        <input type="text" id="userTableSearch" class="form-control rounded-pill-start table-search-input" placeholder="گەڕان لە تەیبڵدا...">
                                                        <span class="input-group-text rounded-pill-end bg-light">
                                                            <img src="../../assets/icons/search-purple.svg" alt="">
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Table Content -->
                                    <div class="table-responsive">
                                        <table id="userTable" class="table table-bordered custom-table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="tbl-header">#</th>
                                                    <th class="tbl-header">ناوی بەکارهێنەر</th>
                                                    <th class="tbl-header">کارمەند</th>
                                                    <th class="tbl-header">ڕۆڵ</th>
                                                    <th class="tbl-header">چالاکە</th>
                                                    <th class="tbl-header">دوایین چوونەژوورەوە</th>
                                                    <th class="tbl-header">کردارەکان</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be loaded with JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Table Pagination -->
                                    <div class="table-pagination mt-3">
                                        <div class="row align-items-center">
                                            <div class="col-md-6 mb-2 mb-md-0">
                                                <div class="pagination-info">
                                                    نیشاندانی <span id="userStartRecord">1</span> تا <span id="userEndRecord">0</span> لە کۆی <span id="userTotalRecords">0</span> تۆمار
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="pagination-controls d-flex justify-content-md-end">
                                                    <button id="userPrevPageBtn" class="btn btn-sm btn-outline-primary rounded-circle me-2" disabled>
                                                        <i class="fas fa-chevron-right"></i>
                                                    </button>
                                                    <div id="userPaginationNumbers" class="pagination-numbers d-flex">
                                                        <!-- Pagination numbers will be generated by JavaScript -->
                                                    </div>
                                                    <button id="userNextPageBtn" class="btn btn-sm btn-outline-primary rounded-circle">
                                                        <i class="fas fa-chevron-left"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

    <!-- Custom JS -->
    <script>
        $(document).ready(function() {
            // Load navbar and sidebar
            $("#navbar-container").load("../../components/navbar.php");
            $("#sidebar-container").load("../../components/sidebar.php");

            // Initialize pagination
            loadUserList();

            // Refresh user list button
            $('#refreshUserList').on('click', function() {
                loadUserList();
            });

            // Records per page change
            $('#userRecordsPerPage').on('change', function() {
                currentPage = 1;
                loadUserList();
            });

            // Search functionality
            $('#userTableSearch').on('keyup', function() {
                currentPage = 1;
                loadUserList();
            });

            // Pagination controls
            $(document).on('click', '.page-number', function() {
                currentPage = parseInt($(this).text());
                loadUserList();
            });

            $('#userPrevPageBtn').on('click', function() {
                if (currentPage > 1) {
                    currentPage--;
                    loadUserList();
                }
            });

            $('#userNextPageBtn').on('click', function() {
                if (currentPage < totalPages) {
                    currentPage++;
                    loadUserList();
                }
            });
        });

        let currentPage = 1;
        let totalPages = 1;

        function loadUserList() {
            const recordsPerPage = $('#userRecordsPerPage').val();
            const searchTerm = $('#userTableSearch').val();

            $.ajax({
                url: '../../api/users/get_users.php',
                type: 'GET',
                data: {
                    page: currentPage,
                    limit: recordsPerPage,
                    search: searchTerm
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        displayUsers(response.data);
                        updatePagination(response.pagination);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: response.message
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیاری: ' + error
                    });
                }
            });
        }

        function displayUsers(users) {
            const tableBody = $('#userTable tbody');
            tableBody.empty();

            if (users.length === 0) {
                tableBody.html('<tr><td colspan="7" class="text-center">هیچ زانیاریەک نەدۆزرایەوە</td></tr>');
                return;
            }

            users.forEach((user, index) => {
                const activeStatus = user.is_active ? 
                    '<span class="badge bg-success">چالاکە</span>' : 
                    '<span class="badge bg-danger">ناچالاکە</span>';
                
                const lastLogin = user.last_login ? new Date(user.last_login).toLocaleString('ku') : 'هیچ کات';
                
                const rowHtml = `
                    <tr>
                        <td>${((currentPage - 1) * $('#userRecordsPerPage').val()) + index + 1}</td>
                        <td>${user.username}</td>
                        <td>${user.employee_name || 'نادیار'}</td>
                        <td>${user.role_name}</td>
                        <td>${activeStatus}</td>
                        <td>${lastLogin}</td>
                        <td>
                            <div class="d-flex justify-content-center">
                                <a href="edit_user.php?id=${user.id}" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteUser(${user.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tableBody.append(rowHtml);
            });
        }

        function updatePagination(pagination) {
            $('#userStartRecord').text(pagination.start);
            $('#userEndRecord').text(pagination.end);
            $('#userTotalRecords').text(pagination.total);

            totalPages = pagination.totalPages;

            // Update pagination buttons
            const paginationContainer = $('#userPaginationNumbers');
            paginationContainer.empty();

            // Add first page if not on first page and have many pages
            if (currentPage > 3 && totalPages > 5) {
                paginationContainer.append(`
                    <button class="btn btn-sm btn-outline-primary rounded-circle me-2 page-number">1</button>
                    <span class="mt-1 me-2">...</span>
                `);
            }

            // Calculate range of pages to show
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, currentPage + 2);

            // Adjust if less than 5 pages
            if (totalPages <= 5) {
                startPage = 1;
                endPage = totalPages;
            } else if (currentPage < 3) {
                // If on early pages
                endPage = 5;
            } else if (currentPage > totalPages - 2) {
                // If on later pages
                startPage = totalPages - 4;
            }

            // Generate page buttons
            for (let i = startPage; i <= endPage; i++) {
                const activeClass = i === currentPage ? 'btn-primary active' : 'btn-outline-primary';
                paginationContainer.append(`
                    <button class="btn btn-sm ${activeClass} rounded-circle me-2 page-number">${i}</button>
                `);
            }

            // Add last page if not on last page and have many pages
            if (currentPage < totalPages - 2 && totalPages > 5) {
                paginationContainer.append(`
                    <span class="mt-1 me-2">...</span>
                    <button class="btn btn-sm btn-outline-primary rounded-circle me-2 page-number">${totalPages}</button>
                `);
            }

            // Update prev/next buttons
            $('#userPrevPageBtn').prop('disabled', currentPage === 1);
            $('#userNextPageBtn').prop('disabled', currentPage === totalPages);
        }

        function deleteUser(userId) {
            Swal.fire({
                title: 'دڵنیای لە سڕینەوە؟',
                text: "ناتوانیت ئەم کردارە پاشگەز بکەیتەوە!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'بەڵێ، بیسڕەوە!',
                cancelButtonText: 'نەخێر'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '../../api/users/delete_user.php',
                        type: 'POST',
                        data: {
                            user_id: userId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire(
                                    'سڕایەوە!',
                                    'بەکارهێنەر بە سەرکەوتوویی سڕایەوە.',
                                    'success'
                                );
                                loadUserList();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'هەڵە',
                                    text: response.message
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە',
                                text: 'هەڵەیەک ڕوویدا لە کاتی سڕینەوە: ' + error
                            });
                        }
                    });
                }
            });
        }
    </script>
</body>
</html> 