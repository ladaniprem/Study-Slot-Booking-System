-- Database setup for Meeting Room Booking System
-- Run this in phpMyAdmin to create the database structure

CREATE DATABASE meeting_room_booking;
GO
USE meeting_room_booking;
CREATE TABLE users (
    user_id INT IDENTITY(1,1) PRIMARY KEY,
    username NVARCHAR(50) NOT NULL UNIQUE,
    email NVARCHAR(100) NOT NULL UNIQUE,
    password_hash NVARCHAR(255) NOT NULL,
    full_name NVARCHAR(100) NOT NULL,
    phone NVARCHAR(20),
    department NVARCHAR(50),
    role NVARCHAR(10) NOT NULL DEFAULT 'user',
    is_active BIT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT GETDATE(),
    updated_at DATETIME NOT NULL DEFAULT GETDATE()
);
CREATE TABLE rooms (
    room_id NVARCHAR(10) PRIMARY KEY,
    room_name NVARCHAR(100) NOT NULL,
    capacity INT NOT NULL,
    location NVARCHAR(100),
    description NVARCHAR(MAX),
    amenities NVARCHAR(MAX),
    is_active BIT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT GETDATE(),
    updated_at DATETIME NOT NULL DEFAULT GETDATE()
);
CREATE TABLE bookings (
    booking_id INT IDENTITY(1,1) PRIMARY KEY,
    user_id INT NOT NULL,
    room_id NVARCHAR(10) NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    purpose NVARCHAR(MAX),
    attendees INT NOT NULL,
    status NVARCHAR(20) NOT NULL DEFAULT 'confirmed',
    qr_code NVARCHAR(255),
    booking_reference NVARCHAR(50) UNIQUE,
    created_at DATETIME NOT NULL DEFAULT GETDATE(),
    updated_at DATETIME NOT NULL DEFAULT GETDATE()
);
ALTER TABLE bookings ADD CONSTRAINT FK_bookings_user_id FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE;
ALTER TABLE bookings ADD FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE;
ALTER TABLE bookings ADD FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE;
CREATE INDEX idx_booking_date ON bookings (booking_date);
CREATE INDEX idx_room_date ON bookings (room_id, booking_date);
CREATE TABLE qr_scans (
    scan_id INT IDENTITY(1,1) PRIMARY KEY,
    booking_id INT NOT NULL,
    scanned_by INT NOT NULL,
    scan_time DATETIME NOT NULL DEFAULT GETDATE(),
    scan_location NVARCHAR(100),
    device_info NVARCHAR(MAX)
);

CREATE TABLE user_sessions (
    session_id NVARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address NVARCHAR(45),
    user_agent NVARCHAR(MAX),
    created_at DATETIME NOT NULL DEFAULT GETDATE(),
    expires_at DATETIME NOT NULL,
    is_active BIT NOT NULL DEFAULT 1
);

INSERT INTO rooms (room_id, room_name, capacity, location, description, amenities) VALUES
('7.21', 'Conference Room A', 5, 'Floor 7', 'Small meeting room with projector', 'projector, whiteboard, wifi'),
('9.15', 'Conference Room B', 7, 'Floor 9', 'Medium meeting room with video conferencing', 'projector, video_conference, whiteboard, wifi'),
('3.53', 'Large Conference Room', 12, 'Floor 3', 'Large meeting room for presentations', 'projector, sound_system, whiteboard, wifi, microphone'),
('3.15', 'Training Room', 12, 'Floor 3', 'Training room with multiple screens', 'multiple_screens, sound_system, whiteboard, wifi'),
('6.72', 'Board Room', 15, 'Floor 6', 'Executive board room with premium amenities', 'projector, video_conference, sound_system, whiteboard, wifi, catering_facility');

INSERT INTO users (username, email, password_hash, full_name, role, is_active) VALUES
('admin', 'admin@company.com', '$2y$10$92IXUNpkjO0rOQ5byMiYe4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', 1);

GO

CREATE VIEW booking_details AS
SELECT 
    b.booking_id,
    b.booking_reference,
    b.booking_date,
    b.start_time,
    b.end_time,
    b.purpose,
    b.attendees,
    b.status,
    b.qr_code,
    u.username,
    u.full_name,
    u.email,
    r.room_name,
    r.location,
    r.capacity,
    b.created_at
FROM bookings b
JOIN users u ON b.user_id = u.user_id
JOIN rooms r ON b.room_id = r.room_id;

GO

-- SQL Server does not support BEFORE INSERT triggers or DELIMITER.
-- You can use an AFTER INSERT trigger for booking_reference generation.

CREATE TRIGGER trg_generate_booking_reference
ON bookings
AFTER INSERT
AS
BEGIN
    UPDATE b
    SET booking_reference = 'BK' + CONVERT(VARCHAR(8), GETDATE(), 112) + RIGHT('0000' + CAST(b.booking_id AS VARCHAR(4)), 4)
    FROM bookings b
    INNER JOIN inserted i ON b.booking_id = i.booking_id
    WHERE b.booking_reference IS NULL;
END

GO