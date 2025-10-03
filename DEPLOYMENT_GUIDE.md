# Deployment Guide for Study Slot Booking System

## Local Development Setup ✅ (COMPLETED)

Your local setup is now complete with:

- ✅ `.env` file for environment variables
- ✅ Composer and PHP dotenv package installed
- ✅ Database configuration updated to use environment variables
- ✅ MySQL database already set up
- ✅ Apache server running on localhost

## Access Your Website

**Local URL:** <http://localhost/Study%20Slot%20Booking%20System/>

## Configuration Files

### `.env` File

Contains all your environment variables:

- Database credentials
- Application settings
- Security keys

**Important:** Never commit the `.env` file to version control!

### Database Connection

The system now uses environment variables from `.env` for database connection:

- Host: localhost
- Database: meeting_room_booking
- User: root
- Password: (empty for XAMPP default)

## Hosting Options

### 1. Shared Hosting

1. Upload all files except `.env` to your hosting provider
2. Create a new `.env` file on the server with your hosting database credentials
3. Update `.env` with your hosting database details:

   ```
   DB_HOST=your_host
   DB_NAME=your_database_name
   DB_USER=your_username
   DB_PASS=your_password
   ```

### 2. VPS/Cloud Hosting

1. Install PHP, Apache/Nginx, and MySQL
2. Clone your repository
3. Run `composer install` to install dependencies
4. Create `.env` file with production settings
5. Import `database_setup.sql` to create database structure

### 3. Heroku/Similar Platforms

1. Add environment variables in the platform's dashboard
2. Use the same variable names as in your `.env` file
3. Configure database connection (usually PostgreSQL on Heroku)

## Security Checklist

- ✅ `.env` file is in `.gitignore`
- ✅ Database credentials are not hardcoded
- ✅ PDO prepared statements for SQL injection protection
- ⚠️  Update `ENCRYPTION_KEY` in `.env` with a strong random key
- ⚠️  Change default database password for production

## Next Steps

1. **Update Encryption Key**: Generate a strong random key for `ENCRYPTION_KEY` in `.env`
2. **Test All Features**: Login, booking, QR code generation
3. **Backup Database**: Export your current database before deployment
4. **Choose Hosting Provider**: Select based on your needs and budget

## Troubleshooting

If you encounter issues:

1. Check PHP error logs in `/logs/error.log`
2. Verify database connection in phpMyAdmin
3. Ensure all Composer dependencies are installed
4. Check file permissions on hosting server

## Database Management

- **phpMyAdmin URL:** <http://localhost/phpmyadmin/>
- **Database Name:** meeting_room_booking
- **Import SQL:** Use the `database_setup.sql` file for fresh installations
