<?php
require_once 'includes/config.php';

$booking_ref = $_GET['ref'] ?? '';
$error = '';
$booking = null;

if (empty($booking_ref)) {
    $error = "No booking reference provided.";
} else {
    try {
        $db = Database::getInstance()->getConnection();

        // Get booking by reference (no login required for verification)
        $stmt = $db->prepare("SELECT * FROM booking_details WHERE booking_reference = ?");
        $stmt->execute([$booking_ref]);
        $booking = $stmt->fetch();

        if (!$booking) {
            $error = "Booking reference not found.";
        }

    } catch (Exception $e) {
        $error = "Error retrieving booking information.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Verification - <?= htmlspecialchars($booking_ref) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .verification-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .verification-card {
            border: 2px solid #28a745;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        }

        .status-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 20px;
        }

        .booking-details {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: left;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="verification-container">
            <?php if ($error): ?>
                <div class="verification-card border-danger">
                    <div class="status-icon text-danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h2 class="text-danger">❌ Verification Failed</h2>
                    <p class="lead"><?= htmlspecialchars($error) ?></p>
                    <p class="text-muted">Please check your booking reference and try again.</p>
                    <a href="auth.php" class="btn btn-primary mt-3">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to View Bookings
                    </a>
                </div>
            <?php else: ?>
                <div class="verification-card">
                    <div class="status-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 class="text-success">✅ Booking Verified</h2>
                    <p class="lead">This is a valid booking!</p>
                </div>

                <div class="booking-details">
                    <h4 class="text-center mb-4">
                        <i class="fas fa-calendar-check me-2"></i>Booking Details
                    </h4>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <i class="fas fa-hashtag text-primary me-2"></i>
                                <strong>Reference:</strong><br>
                                <span
                                    class="badge bg-primary fs-6"><?= htmlspecialchars($booking['booking_reference']) ?></span>
                            </div>

                            <div class="mb-3">
                                <i class="fas fa-door-open text-primary me-2"></i>
                                <strong>Room:</strong><br>
                                <?= htmlspecialchars($booking['room_name']) ?>
                            </div>

                            <div class="mb-3">
                                <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                <strong>Location:</strong><br>
                                <?= htmlspecialchars($booking['location']) ?>
                            </div>

                            <div class="mb-3">
                                <i class="fas fa-calendar text-primary me-2"></i>
                                <strong>Date:</strong><br>
                                <?= date('l, F j, Y', strtotime($booking['booking_date'])) ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <i class="fas fa-clock text-primary me-2"></i>
                                <strong>Time:</strong><br>
                                <?= date('g:i A', strtotime($booking['start_time'])) ?> -
                                <?= date('g:i A', strtotime($booking['end_time'])) ?>
                            </div>

                            <div class="mb-3">
                                <i class="fas fa-user text-primary me-2"></i>
                                <strong>Booked by:</strong><br>
                                <?= htmlspecialchars($booking['full_name']) ?>
                            </div>

                            <div class="mb-3">
                                <i class="fas fa-users text-primary me-2"></i>
                                <strong>Attendees:</strong><br>
                                <?= $booking['attendees'] ?> people
                            </div>

                            <div class="mb-3">
                                <i class="fas fa-info-circle text-primary me-2"></i>
                                <strong>Status:</strong><br>
                                <span
                                    class="badge bg-<?= $booking['status'] === 'confirmed' ? 'success' : ($booking['status'] === 'cancelled' ? 'danger' : 'warning') ?>">
                                    <?= ucfirst($booking['status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <?php if ($booking['purpose']): ?>
                        <div class="mt-3 pt-3 border-top">
                            <i class="fas fa-comment text-primary me-2"></i>
                            <strong>Purpose:</strong><br>
                            <em><?= htmlspecialchars($booking['purpose']) ?></em>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="text-center">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Verification Time:</strong> <?= date('F j, Y \a\t g:i A') ?>
                    </div>

                    <?php if ($booking['status'] === 'confirmed'): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            This booking is <strong>confirmed</strong> and valid for entry.
                        </div>
                    <?php elseif ($booking['status'] === 'cancelled'): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-ban me-2"></i>
                            This booking has been <strong>cancelled</strong>.
                        </div>
                    <?php endif; ?>

                    <a href="auth.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to Meeting Room System
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>