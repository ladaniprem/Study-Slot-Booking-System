<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
    header('Location: auth.php');
    exit();
}

$db = Database::getInstance()->getConnection();
$message = '';
$message_type = '';

// Handle room booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_room'])) {
    try {
        $booking_date = $_POST['booking_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $attendees = (int) $_POST['attendees'];
        $purpose = Security::sanitizeInput($_POST['purpose']);

        // Validation
        $errors = [];

        if (empty($booking_date) || $booking_date < date('Y-m-d')) {
            $errors[] = "Please select a valid future date.";
        }

        if (strtotime($start_time) >= strtotime($end_time)) {
            $errors[] = "End time must be after start time.";
        }

        if ($attendees < 1 || $attendees > 50) {
            $errors[] = "Number of attendees must be between 1 and 50.";
        }

        if (empty($errors)) {
            $db->beginTransaction();

            // Find available rooms
            $stmt = $db->prepare("
                SELECT r.room_id, r.room_name, r.capacity, r.location, r.amenities
                FROM rooms r 
                WHERE r.capacity >= ? AND r.is_active = 1
                AND r.room_id NOT IN (
                    SELECT b.room_id 
                    FROM bookings b 
                    WHERE b.booking_date = ? 
                    AND b.status = 'confirmed'
                    AND (
                        (? >= b.start_time AND ? < b.end_time) OR
                        (? > b.start_time AND ? <= b.end_time) OR
                        (? <= b.start_time AND ? >= b.end_time)
                    )
                )
                ORDER BY r.capacity ASC
                LIMIT 1
            ");

            $stmt->execute([
                $attendees,
                $booking_date,
                $start_time,
                $start_time,
                $end_time,
                $end_time,
                $start_time,
                $end_time
            ]);

            $available_room = $stmt->fetch();

            if ($available_room) {
                // Generate booking reference
                $booking_reference = 'BK' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

                // Create booking
                $stmt = $db->prepare("
                    INSERT INTO bookings (user_id, room_id, booking_date, start_time, end_time, purpose, attendees, booking_reference) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    SessionManager::getUserId(),
                    $available_room['room_id'],
                    $booking_date,
                    $start_time,
                    $end_time,
                    $purpose,
                    $attendees,
                    $booking_reference
                ]);

                $booking_id = $db->lastInsertId();

                // Generate QR code using our simple QR generator
                require_once 'includes/simple-qr.php';
                $qr_manager = new BookingQRManager();

                // Prepare booking data for QR generation
                $booking_data = [
                    'booking_id' => $booking_id,
                    'booking_reference' => $booking_reference,
                    'room_id' => $available_room['room_id'],
                    'room_name' => $available_room['room_name'],
                    'booking_date' => $booking_date,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'username' => SessionManager::getUsername()
                ];

                $qr_code_path = $qr_manager->generateBookingQR($booking_data);
                $qr_manager->generateHTMLQR($booking_data); // Also generate HTML version

                // Update booking with QR code
                $stmt = $db->prepare("UPDATE bookings SET qr_code = ? WHERE booking_id = ?");
                $stmt->execute([$qr_code_path, $booking_id]);

                $db->commit();

                $message = "Study slot <strong>{$available_room['room_name']}</strong> successfully booked for <strong>{$booking_date}</strong> from <strong>{$start_time}</strong> to <strong>{$end_time}</strong>. Booking Reference: <strong>{$booking_reference}</strong>";
                $message_type = 'success';

            } else {
                $db->rollback();
                $message = "Sorry, no rooms available for the selected date and time with capacity for {$attendees} people.";
                $message_type = 'danger';
            }
        } else {
            $message = implode('<br>', $errors);
            $message_type = 'danger';
        }

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollback();
        }
        logError("Booking failed: " . $e->getMessage());
        $message = "Booking failed. Please try again.";
        $message_type = 'danger';
    }
}

// Get available rooms for display
$stmt = $db->prepare("SELECT * FROM rooms WHERE is_active = 1 ORDER BY capacity ASC");
$stmt->execute();
$all_rooms = $stmt->fetchAll();

// Get today's bookings
$stmt = $db->prepare("
    SELECT bd.* 
    FROM booking_details bd 
    WHERE bd.booking_date = CURDATE() AND bd.status = 'confirmed'
    ORDER BY bd.start_time ASC
");
$stmt->execute();
$todays_bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Study Slot - <?php echo APP_NAME; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .main-content {
            padding: 2rem 0;
        }

        .booking-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: none;
        }

        .room-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .room-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }

        .room-card.selected {
            border-color: #667eea;
            background-color: #f8f9ff;
        }

        .btn-book {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            padding: 12px 30px;
            font-weight: 600;
        }

        .btn-book:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .amenity-badge {
            background: #e9ecef;
            color: #495057;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
            margin: 2px;
        }

        .time-slot {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 8px 12px;
            margin: 2px;
            font-size: 0.9rem;
        }

        .time-slot.occupied {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-door-open me-2"></i>
                <?php echo APP_NAME; ?>
            </a>

            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="book-room.php">
                            <i class="fas fa-plus me-1"></i>Book Study Slot
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my-bookings.php">
                            <i class="fas fa-calendar me-1"></i>My Bookings
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php?logout=1">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="h3 mb-0">
                <i class="fas fa-plus text-primary me-2"></i>Book a Study Slot
            </h1>
            <p class="text-muted mb-0">Select date, time, and room preferences for your study session</p>
        </div>

        <!-- Display message -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Booking Form -->
            <div class="col-lg-8 mb-4">
                <div class="booking-card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-calendar-plus text-primary me-2"></i>Booking Details
                        </h5>

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="booking_date" class="form-label">
                                        <i class="fas fa-calendar me-1"></i>Date
                                    </label>
                                    <input type="date" class="form-control" id="booking_date" name="booking_date"
                                        min="<?php echo date('Y-m-d'); ?>" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="attendees" class="form-label">
                                        <i class="fas fa-users me-1"></i>Number of Attendees
                                    </label>
                                    <select class="form-select" id="attendees" name="attendees" required>
                                        <option value="">Select attendees</option>
                                        <?php for ($i = 1; $i <= 25; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?>
                                                person<?php echo $i > 1 ? 's' : ''; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_time" class="form-label">
                                        <i class="fas fa-clock me-1"></i>Start Time
                                    </label>
                                    <select class="form-select" id="start_time" name="start_time" required>
                                        <option value="">Select start time</option>
                                        <?php
                                        for ($hour = 8; $hour <= 22; $hour++) {
                                            $time = sprintf('%02d:00', $hour);
                                            echo "<option value='{$time}'>{$time}</option>";
                                            $time = sprintf('%02d:30', $hour);
                                            echo "<option value='{$time}'>{$time}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="end_time" class="form-label">
                                        <i class="fas fa-clock me-1"></i>End Time
                                    </label>
                                    <select class="form-select" id="end_time" name="end_time" required>
                                        <option value="">Select end time</option>
                                        <?php
                                        for ($hour = 8; $hour <= 23; $hour++) {
                                            $time = sprintf('%02d:00', $hour);
                                            echo "<option value='{$time}'>{$time}</option>";
                                            $time = sprintf('%02d:30', $hour);
                                            echo "<option value='{$time}'>{$time}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="purpose" class="form-label">
                                    <i class="fas fa-comment me-1"></i>Meeting Purpose (Optional)
                                </label>
                                <textarea class="form-control" id="purpose" name="purpose" rows="3"
                                    placeholder="Brief description of the meeting purpose..."></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="book_room" class="btn btn-book btn-lg">
                                    <i class="fas fa-check me-2"></i>Book Study Slot
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Available Rooms -->
            <div class="col-lg-4 mb-4">
                <div class="booking-card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-door-open text-primary me-2"></i>Available Rooms
                        </h5>

                        <?php foreach ($all_rooms as $room): ?>
                            <div class="room-card mb-3 p-3">
                                <h6 class="mb-2"><?php echo htmlspecialchars($room['room_name']); ?></h6>
                                <p class="small text-muted mb-2">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars($room['location']); ?>
                                </p>
                                <p class="small text-muted mb-2">
                                    <i class="fas fa-users me-1"></i>
                                    Capacity: <?php echo $room['capacity']; ?> people
                                </p>

                                <?php if ($room['amenities']): ?>
                                    <div class="amenities">
                                        <?php
                                        $amenities = json_decode($room['amenities'], true);
                                        if ($amenities) {
                                            foreach ($amenities as $amenity) {
                                                echo '<span class="amenity-badge">' . htmlspecialchars(str_replace('_', ' ', $amenity)) . '</span>';
                                            }
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Today's Schedule -->
                <?php if (!empty($todays_bookings)): ?>
                    <div class="booking-card mt-4">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="fas fa-calendar-day text-primary me-2"></i>Today's Schedule
                            </h6>

                            <?php foreach ($todays_bookings as $booking): ?>
                                <div class="time-slot occupied mb-2">
                                    <strong><?php echo htmlspecialchars($booking['room_name']); ?></strong><br>
                                    <small>
                                        <?php echo date('g:i A', strtotime($booking['start_time'])); ?> -
                                        <?php echo date('g:i A', strtotime($booking['end_time'])); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function () {
            const startTimeSelect = document.getElementById('start_time');
            const endTimeSelect = document.getElementById('end_time');

            function updateEndTimeOptions() {
                const startTime = startTimeSelect.value;
                const endTimeOptions = endTimeSelect.options;

                // Enable all options first
                for (let i = 0; i < endTimeOptions.length; i++) {
                    endTimeOptions[i].disabled = false;
                    endTimeOptions[i].style.display = '';
                }

                if (startTime) {
                    const startHour = parseInt(startTime.split(':')[0]);
                    const startMinute = parseInt(startTime.split(':')[1]);
                    const startTotalMinutes = startHour * 60 + startMinute;

                    // Disable end time options that are before or equal to start time
                    for (let i = 1; i < endTimeOptions.length; i++) {
                        const endTime = endTimeOptions[i].value;
                        const endHour = parseInt(endTime.split(':')[0]);
                        const endMinute = parseInt(endTime.split(':')[1]);
                        const endTotalMinutes = endHour * 60 + endMinute;

                        if (endTotalMinutes <= startTotalMinutes) {
                            endTimeOptions[i].disabled = true;
                            endTimeOptions[i].style.display = 'none';
                        }
                    }

                    // Reset end time if it's invalid
                    if (endTimeSelect.value && startTime >= endTimeSelect.value) {
                        endTimeSelect.value = '';
                    }
                }
            }

            startTimeSelect.addEventListener('change', updateEndTimeOptions);

            // Set minimum date to today
            const dateInput = document.getElementById('booking_date');
            const today = new Date().toISOString().split('T')[0];
            dateInput.min = today;
            dateInput.value = today;
        });
    </script>
</body>

</html>