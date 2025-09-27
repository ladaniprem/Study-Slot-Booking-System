<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
    header('Location: auth.php');
    exit();
}

// Get booking reference from URL parameter
$booking_ref = $_GET['ref'] ?? '';

if (empty($booking_ref)) {
    header('Location: my-bookings.php');
    exit();
}

// Get booking details
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM booking_details WHERE booking_reference = ?");
    $stmt->execute([$booking_ref]);
    $booking = $stmt->fetch();

    if (!$booking) {
        header('Location: my-bookings.php?error=booking_not_found');
        exit();
    }
} catch (Exception $e) {
    header('Location: my-bookings.php?error=database_error');
    exit();
}

// Create QR data in JSON format for better parsing
$qr_data = json_encode([
    'type' => 'booking_verification',
    'booking_reference' => $booking['booking_reference'],
    'room_name' => $booking['room_name'],
    'booking_date' => $booking['booking_date'],
    'start_time' => $booking['start_time'],
    'end_time' => $booking['end_time'],
    'user' => $booking['full_name'],
    'verify_url' => 'http://localhost/Meeting-room-booking-system-main/verify-booking.php?ref=' . $booking['booking_reference']
]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code - <?php echo htmlspecialchars($booking['booking_reference']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/gh/davidshimjs/qrcodejs/qrcode.min.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }

        .qr-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            text-align: center;
        }

        .qr-code-display {
            max-width: 300px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            border: 2px solid #dee2e6;
            border-radius: 10px;
        }

        @media print {
            .no-print {
                display: none;
            }

            .qr-container {
                box-shadow: none;
                border: 1px solid #000;
            }
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- Navigation -->
                <div class="no-print mb-3">
                    <a href="my-bookings.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to My Bookings
                    </a>
                    <button onclick="window.print()" class="btn btn-primary float-end">
                        <i class="fas fa-print me-2"></i>Print QR Code
                    </button>
                </div>

                <!-- QR Code Container -->
                <div class="qr-container">
                    <h2 class="mb-4">
                        <i class="fas fa-qrcode text-primary me-2"></i>
                        Booking QR Code
                    </h2>

                    <!-- Booking Details -->
                    <div class="row mb-4">
                        <div class="col-md-6 text-start">
                            <h6>Booking Reference:</h6>
                            <strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong>
                        </div>
                        <div class="col-md-6 text-start">
                            <h6>Room:</h6>
                            <strong><?php echo htmlspecialchars($booking['room_name']); ?></strong>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 text-start">
                            <h6>Date:</h6>
                            <strong><?php echo date('F j, Y', strtotime($booking['booking_date'])); ?></strong>
                        </div>
                        <div class="col-md-6 text-start">
                            <h6>Time:</h6>
                            <strong>
                                <?php echo date('g:i A', strtotime($booking['start_time'])); ?> -
                                <?php echo date('g:i A', strtotime($booking['end_time'])); ?>
                            </strong>
                        </div>
                    </div>

                    <!-- QR Code Display -->
                    <div class="qr-code-display mb-4 mx-auto" style="max-width: 280px; padding: 15px;">
                        <div id="qr-code"></div>
                    </div>

                    <p class="text-muted mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Scan this QR code at the room entrance or show it to the librarian for verification
                    </p>

                    <!-- Alternative Text Format -->
                    <div class="no-print mt-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">QR Code Data (for manual entry)</h6>
                            </div>
                            <div class="card-body">
                                <textarea class="form-control" rows="6" readonly><?php echo $qr_data; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Generate QR code using qrcode.js library
        document.addEventListener('DOMContentLoaded', function () {
            const qrData = `<?php echo $qr_data; ?>`;

            new QRCode(document.getElementById('qr-code'), {
                text: qrData,
                width: 300,
                height: 300,
                colorDark: '#000000',
                colorLight: '#FFFFFF',
                correctLevel: QRCode.CorrectLevel.H
            });

            // The qrcode.js library does not have a direct callback for errors like qrcode.min.js
             // We will assume it generates successfully if no immediate errors occur.
         });
    </script>
</body>

</html>