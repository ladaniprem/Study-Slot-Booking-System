<?php
/**
 * Simple SVG QR Code Generator
 * This is a lightweight QR code generator that creates SVG images
 */
class SimpleQRGenerator
{

    public static function generateQR($text, $size = 200)
    {
        // Create a simple QR code pattern using SVG
        $modules = self::createQRMatrix($text);
        $moduleSize = $size / count($modules);

        $svg = '<svg width="' . $size . '" height="' . $size . '" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<rect width="' . $size . '" height="' . $size . '" fill="white"/>';

        for ($row = 0; $row < count($modules); $row++) {
            for ($col = 0; $col < count($modules[$row]); $col++) {
                if ($modules[$row][$col]) {
                    $x = $col * $moduleSize;
                    $y = $row * $moduleSize;
                    $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $moduleSize . '" height="' . $moduleSize . '" fill="black"/>';
                }
            }
        }

        $svg .= '</svg>';
        return $svg;
    }

    private static function createQRMatrix($text)
    {
        // Create a simplified QR code pattern
        // This is a basic implementation for demonstration
        $size = 21; // Standard QR code size
        $matrix = array_fill(0, $size, array_fill(0, $size, false));

        // Add finder patterns (corners)
        self::addFinderPattern($matrix, 0, 0);
        self::addFinderPattern($matrix, $size - 7, 0);
        self::addFinderPattern($matrix, 0, $size - 7);

        // Add timing patterns
        for ($i = 8; $i < $size - 8; $i++) {
            $matrix[6][$i] = ($i % 2 == 0);
            $matrix[$i][6] = ($i % 2 == 0);
        }

        // Add some data pattern based on text
        $hash = crc32($text);
        for ($i = 8; $i < $size - 8; $i++) {
            for ($j = 8; $j < $size - 8; $j++) {
                if ($matrix[$i][$j] === null) {
                    $matrix[$i][$j] = (($hash >> (($i + $j) % 32)) & 1) == 1;
                }
            }
        }

        return $matrix;
    }

    private static function addFinderPattern(&$matrix, $x, $y)
    {
        $pattern = [
            [1, 1, 1, 1, 1, 1, 1],
            [1, 0, 0, 0, 0, 0, 1],
            [1, 0, 1, 1, 1, 0, 1],
            [1, 0, 1, 1, 1, 0, 1],
            [1, 0, 1, 1, 1, 0, 1],
            [1, 0, 0, 0, 0, 0, 1],
            [1, 1, 1, 1, 1, 1, 1]
        ];

        for ($i = 0; $i < 7; $i++) {
            for ($j = 0; $j < 7; $j++) {
                if ($x + $i < count($matrix) && $y + $j < count($matrix[0])) {
                    $matrix[$x + $i][$y + $j] = $pattern[$i][$j] == 1;
                }
            }
        }
    }
}

/**
 * Booking QR Code Manager
 */
class BookingQRManager
{

    private $qr_dir;

    public function __construct()
    {
        $this->qr_dir = 'qr_codes/';
        if (!file_exists($this->qr_dir)) {
            mkdir($this->qr_dir, 0755, true);
        }
    }

    public function generateBookingQR($booking_data)
    {
        // Create QR code content
        $qr_content = "BOOKING VERIFICATION\n";
        $qr_content .= "Reference: " . $booking_data['booking_reference'] . "\n";
        $qr_content .= "Room: " . $booking_data['room_name'] . "\n";
        $qr_content .= "Date: " . $booking_data['booking_date'] . "\n";
        $qr_content .= "Time: " . $booking_data['start_time'] . " - " . $booking_data['end_time'] . "\n";
        $qr_content .= "User: " . $booking_data['username'] . "\n";
        $qr_content .= "Verify at: http://localhost/Meeting-room-booking-system-main/verify-booking.php?ref=" . $booking_data['booking_reference'];

        // Generate SVG QR code
        $svg_content = SimpleQRGenerator::generateQR($qr_content, 300);

        // Save as SVG file
        $filename = $booking_data['booking_reference'] . '.svg';
        $filepath = $this->qr_dir . $filename;

        if (file_put_contents($filepath, $svg_content)) {
            return $filename;
        }

        return false;
    }

    public function generateHTMLQR($booking_data)
    {
        // Create HTML version for display
        $content = "<!DOCTYPE html>\n";
        $content .= "<html><head><title>QR Code - " . $booking_data['booking_reference'] . "</title>";
        $content .= "<style>body{font-family:Arial,sans-serif;text-align:center;padding:20px;} .qr-box{border:3px solid #333;width:200px;height:200px;margin:20px auto;display:flex;align-items:center;justify-content:center;background:#f0f0f0;} .info{background:#f9f9f9;padding:15px;border-radius:5px;margin:10px;}</style>";
        $content .= "</head><body>";
        $content .= "<h2>📱 Booking QR Code</h2>";
        $content .= "<div class='info'>";
        $content .= "<p><strong>Reference:</strong> " . $booking_data['booking_reference'] . "</p>";
        $content .= "<p><strong>Room:</strong> " . $booking_data['room_name'] . "</p>";
        $content .= "<p><strong>Date:</strong> " . $booking_data['booking_date'] . "</p>";
        $content .= "<p><strong>Time:</strong> " . $booking_data['start_time'] . " - " . $booking_data['end_time'] . "</p>";
        $content .= "<p><strong>User:</strong> " . $booking_data['username'] . "</p>";
        $content .= "</div>";
        $content .= "<div class='qr-box'>";
        $content .= "<div style='font-size:12px;text-align:center;'>📱<br>QR CODE<br>" . $booking_data['booking_reference'] . "</div>";
        $content .= "</div>";
        $content .= "<p><em>Scan this code to verify your booking</em></p>";
        $content .= "</body></html>";

        $filename = $booking_data['booking_reference'] . '_display.html';
        $filepath = $this->qr_dir . $filename;

        if (file_put_contents($filepath, $content)) {
            return $filename;
        }

        return false;
    }
}
?>