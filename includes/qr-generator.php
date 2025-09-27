<?php
require_once 'includes/config.php';

/**
 * QR Code Generator for Meeting Room Bookings
 * This class handles QR code generation and management
 */
class QRCodeGenerator
{

    private $qr_code_dir;

    public function __construct()
    {
        $this->qr_code_dir = 'qr_codes/';
        if (!file_exists($this->qr_code_dir)) {
            mkdir($this->qr_code_dir, 0755, true);
        }
    }

    /**
     * Generate QR code for a booking
     */
    public function generateBookingQR($booking_id, $booking_data)
    {
        try {
            // Create QR code data
            $qr_data = json_encode([
                'type' => 'booking',
                'booking_id' => $booking_id,
                'booking_reference' => $booking_data['booking_reference'],
                'room_id' => $booking_data['room_id'],
                'room_name' => $booking_data['room_name'],
                'date' => $booking_data['booking_date'],
                'start_time' => $booking_data['start_time'],
                'end_time' => $booking_data['end_time'],
                'user' => $booking_data['username'],
                'generated_at' => date('Y-m-d H:i:s'),
                'verification_url' => $this->getBaseUrl() . 'verify-booking.php?ref=' . $booking_data['booking_reference']
            ]);

            // Use Google Charts API for QR code generation (simple solution)
            $filename = $booking_data['booking_reference'] . '.png';
            $filepath = $this->qr_code_dir . $filename;

            // Generate QR code using Google Charts API
            $qr_url = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($qr_data) . '&choe=UTF-8';

            // Download and save the QR code
            $qr_image = file_get_contents($qr_url);
            if ($qr_image !== false) {
                file_put_contents($filepath, $qr_image);
                return $filepath;
            }

            return false;

        } catch (Exception $e) {
            logError("QR Code generation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate simple SVG QR code (fallback method)
     */
    public function generateSimpleQR($booking_data)
    {
        $filename = $booking_data['booking_reference'] . '.svg';
        $filepath = $this->qr_code_dir . $filename;

        // Simple SVG-based QR code representation
        $qr_content = json_encode([
            'booking_ref' => $booking_data['booking_reference'],
            'room' => $booking_data['room_name'],
            'date' => $booking_data['booking_date'],
            'time' => $booking_data['start_time'] . '-' . $booking_data['end_time']
        ]);

        // Create a simple visual representation
        $svg = $this->createSimpleQRSVG($qr_content, $booking_data);

        file_put_contents($filepath, $svg);
        return $filepath;
    }

    private function createSimpleQRSVG($data, $booking_data)
    {
        // Create a stylized booking card as SVG
        $svg = '<?xml version="1.0" encoding="UTF-8"?>
        <svg width="300" height="300" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" style="stop-color:#667eea;stop-opacity:1" />
                    <stop offset="100%" style="stop-color:#764ba2;stop-opacity:1" />
                </linearGradient>
            </defs>
            
            <!-- Background -->
            <rect width="300" height="300" fill="url(#grad1)" rx="20"/>
            
            <!-- Inner card -->
            <rect x="20" y="20" width="260" height="260" fill="white" rx="15"/>
            
            <!-- Header -->
            <rect x="20" y="20" width="260" height="60" fill="url(#grad1)" rx="15"/>
            <rect x="20" y="65" width="260" height="15" fill="white"/>
            
            <!-- QR Icon -->
            <rect x="40" y="35" width="30" height="30" fill="white" rx="3"/>
            <rect x="45" y="40" width="5" height="5" fill="#667eea"/>
            <rect x="55" y="40" width="5" height="5" fill="#667eea"/>
            <rect x="65" y="40" width="5" height="5" fill="#667eea"/>
            <rect x="45" y="50" width="5" height="5" fill="#667eea"/>
            <rect x="65" y="50" width="5" height="5" fill="#667eea"/>
            <rect x="45" y="60" width="5" height="5" fill="#667eea"/>
            <rect x="55" y="60" width="5" height="5" fill="#667eea"/>
            <rect x="65" y="60" width="5" height="5" fill="#667eea"/>
            
            <!-- Text -->
            <text x="80" y="50" font-family="Arial, sans-serif" font-size="16" font-weight="bold" fill="white">BOOKING QR</text>
            
            <!-- Booking Details -->
            <text x="40" y="120" font-family="Arial, sans-serif" font-size="14" font-weight="bold" fill="#333">Reference:</text>
            <text x="40" y="140" font-family="Arial, sans-serif" font-size="12" fill="#666">' . htmlspecialchars($booking_data['booking_reference']) . '</text>
            
            <text x="40" y="170" font-family="Arial, sans-serif" font-size="14" font-weight="bold" fill="#333">Room:</text>
            <text x="40" y="190" font-family="Arial, sans-serif" font-size="12" fill="#666">' . htmlspecialchars($booking_data['room_name']) . '</text>
            
            <text x="40" y="220" font-family="Arial, sans-serif" font-size="14" font-weight="bold" fill="#333">Date & Time:</text>
            <text x="40" y="240" font-family="Arial, sans-serif" font-size="12" fill="#666">' . date('M j, Y', strtotime($booking_data['booking_date'])) . '</text>
            <text x="40" y="255" font-family="Arial, sans-serif" font-size="12" fill="#666">' . date('g:i A', strtotime($booking_data['start_time'])) . ' - ' . date('g:i A', strtotime($booking_data['end_time'])) . '</text>
        </svg>';

        return $svg;
    }

    private function getBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $script_name = dirname($_SERVER['SCRIPT_NAME']);
        return $protocol . $host . $script_name . '/';
    }

    /**
     * Verify QR code data
     */
    public function verifyQRData($qr_data)
    {
        try {
            $data = json_decode($qr_data, true);
            if (!$data || !isset($data['booking_reference'])) {
                return false;
            }

            // Verify booking exists in database
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM booking_details WHERE booking_reference = ?");
            $stmt->execute([$data['booking_reference']]);
            $booking = $stmt->fetch();

            return $booking ? $booking : false;

        } catch (Exception $e) {
            logError("QR verification failed: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Helper function to regenerate QR codes for existing bookings
 */
function regenerateQRForBooking($booking_id)
{
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM booking_details WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();

        if ($booking) {
            $qr_generator = new QRCodeGenerator();
            $qr_path = $qr_generator->generateSimpleQR($booking);

            if ($qr_path) {
                // Update booking with QR code path
                $stmt = $db->prepare("UPDATE bookings SET qr_code = ? WHERE booking_id = ?");
                $stmt->execute([$qr_path, $booking_id]);
                return $qr_path;
            }
        }

        return false;

    } catch (Exception $e) {
        logError("QR regeneration failed: " . $e->getMessage());
        return false;
    }
}

?>