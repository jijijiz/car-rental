<?php
require 'config.php';

// Verify admin identity
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle search and filtering
$search = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clauses = [];
$params = [];

// Search by review content or username
if ($search) {
    $where_clauses[] = "(r.comment LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Assemble query conditions
$where_sql = $where_clauses ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get review data
$stmt = $pdo->prepare("
    SELECT 
        r.*,
        u.name as user_name,
        c.name as car_name,
        o.start_date,
        o.end_date
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN cars c ON r.car_id = c.id
    LEFT JOIN orders o ON r.order_id = o.id
    $where_sql
    ORDER BY r.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Get total review count
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM reviews r 
    LEFT JOIN users u ON r.user_id = u.id
    $where_sql
");
$stmt->execute($params);
$total_reviews = $stmt->fetchColumn();
$total_pages = ceil($total_reviews / $limit);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reviews Management - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .sidebar { min-height: 100vh; background: #343a40; }
        .nav-link { color: #fff; }
        .nav-link:hover { background: #495057; }
        .nav-link.active { background: #495057; }
        .review-content { 
            max-height: 100px; 
            overflow-y: auto; 
        }
        .rating-stars {
            color: #ffc107;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'admin_sidebar.php'; ?>

            <!-- Main Content Area -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-comments me-2"></i>Reviews Management</h2>
                </div>

                <!-- Search Bar -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" name="search" class="form-control" 
                                           placeholder="Search reviews or users" 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i>Search
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Review List -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Car</th>
                                        <th>Rating</th>
                                        <th>Review</th>
                                        <th>Rental Period</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reviews as $review): ?>
                                    <tr data-review-id="<?php echo $review['id']; ?>">
                                        <td>#<?php echo str_pad($review['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($review['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($review['car_name']); ?></td>
                                        <td>
                                            <div class="rating-stars">
                                                <?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    echo $i <= $review['rating'] ? '★' : '☆';
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="review-content">
                                                <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo date('Y-m-d', strtotime($review['start_date'])); ?>
                                            <br>to<br>
                                            <?php echo date('Y-m-d', strtotime($review['end_date'])); ?>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($review['created_at'])); ?></td>
                                        <td>
                                            <button onclick="viewReview(<?php echo $review['id']; ?>)" 
                                                    class="btn btn-sm btn-info mb-1">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button onclick="deleteReview(<?php echo $review['id']; ?>)" 
                                                    class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- 分页 -->
                        <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Review Modal -->
    <div class="modal fade" id="viewReviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Review Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="reviewDetailContent">
                    <!-- 评论详情将通过 AJAX 加载 -->
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteReviewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Confirm Delete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                        <h5>Are you sure?</h5>
                        <p class="text-muted">
                            You are about to delete review #<span id="deleteReviewId"></span>. 
                            This action cannot be undone.
                        </p>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">
                        <i class="fas fa-trash-alt me-2"></i>Delete Review
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let reviewIdToDelete = null;

    function viewReview(reviewId) {
        $('#reviewDetailContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
        $('#viewReviewModal').modal('show');
        
        $.get('get_review_details.php?id=' + reviewId, function(response) {
            try {
                const review = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (review.error) {
                    throw new Error(review.error);
                }
                
                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>User Information</h6>
                            <p><strong>Name:</strong> ${review.user_name}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Car Information</h6>
                            <p><strong>Car:</strong> ${review.car_name}</p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <h6>Rating</h6>
                            <div class="rating-stars h4 mb-3">
                                ${'★'.repeat(review.rating)}${'☆'.repeat(5-review.rating)}
                            </div>
                            <h6>Review</h6>
                            <p>${review.comment.replace(/\n/g, '<br>')}</p>
                            <small class="text-muted">Posted on ${review.created_at}</small>
                        </div>
                    </div>
                `;
                $('#reviewDetailContent').html(html);
            } catch (e) {
                console.error('Error:', e);
                $('#reviewDetailContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Error loading review details
                    </div>
                `);
            }
        });
    }

    function deleteReview(reviewId) {
        reviewIdToDelete = reviewId;
        $('#deleteReviewId').text(String(reviewId).padStart(5, '0'));
        $('#deleteReviewModal').modal('show');
    }

    $(document).ready(function() {
        $('#confirmDelete').click(function() {
            if (!reviewIdToDelete) return;
            
            const $btn = $(this);
            const originalText = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin me-2"></i>Deleting...').prop('disabled', true);
            
            $.ajax({
                url: 'delete_review.php',
                method: 'POST',
                data: { id: reviewIdToDelete },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#deleteReviewModal').modal('hide');
                        
                        const alert = $(`
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                Review #${String(reviewIdToDelete).padStart(5, '0')} has been deleted successfully
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        `).insertBefore('.card:first');
                        
                        $(`tr[data-review-id="${reviewIdToDelete}"]`).fadeOut(400, function() {
                            $(this).remove();
                            
                            if ($('table tbody tr').length === 0) {
                                $('table tbody').append(`
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="fas fa-comments fa-3x mb-3"></i>
                                            <p class="mb-0">No reviews found</p>
                                        </td>
                                    </tr>
                                `);
                            }
                        });
                        
                        setTimeout(() => {
                            alert.alert('close');
                        }, 3000);
                    } else {
                        $('#deleteReviewModal .modal-body').prepend(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                ${response.message || 'Failed to delete review'}
                            </div>
                        `);
                    }
                },
                error: function() {
                    $('#deleteReviewModal .modal-body').prepend(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Error occurred while deleting review
                        </div>
                    `);
                },
                complete: function() {
                    $btn.html(originalText).prop('disabled', false);
                }
            });
        });
        
        $('#deleteReviewModal').on('hidden.bs.modal', function() {
            reviewIdToDelete = null;
            $(this).find('.alert').remove();
        });
    });
    </script>
</body>
</html> 