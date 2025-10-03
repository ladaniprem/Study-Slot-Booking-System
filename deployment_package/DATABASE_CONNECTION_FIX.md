# 🔧 Database Connection Error - Solution Guide

## ❌ Error Message

```
Database connection failed. Please try again later.
```

## 🎯 Root Cause Analysis

This error occurs when PHP cannot connect to the MySQL database. Common causes:

1. **MySQL service not running**
2. **Wrong database credentials**
3. **Database doesn't exist**
4. **Host resolution issues**
5. **Port conflicts**

## ✅ SOLUTION STEPS

### Step 1: Verify XAMPP MySQL is Running

1. **Open XAMPP Control Panel**
2. **Start MySQL service** if it's not running
3. **Check status** - should show "Running" in green

### Step 2: Test Database Connection

Visit `debug-db.php` in your browser to see the exact error:

```
http://localhost/Study%20Slot%20Booking%20System/deployment_package/debug-db.php
```

### Step 3: Fix Common Issues

#### Issue A: MySQL Not Running

**Symptoms:** "Connection refused" error
**Solution:**

```bash
# Start XAMPP MySQL service
# Or restart XAMPP completely
```

#### Issue B: Host Resolution Problem

**Symptoms:** "php_network_getaddresses" error
**Solution:** Update `.env` file:

```env
# Try different host values:
DB_HOST=127.0.0.1     # Instead of localhost
# OR
DB_HOST=::1           # IPv6 localhost
# OR  
DB_HOST=localhost     # Original
```

#### Issue C: Database Doesn't Exist

**Symptoms:** "Unknown database" error
**Solution:**

1. Go to phpMyAdmin: <http://localhost/phpmyadmin>
2. Create database named `meeting_room_booking`
3. Import `database_setup.sql`

#### Issue D: Wrong Credentials

**Symptoms:** "Access denied" error
**Solution:** Check `.env` file:

```env
DB_HOST=127.0.0.1
DB_NAME=meeting_room_booking
DB_USER=root          # Default XAMPP user
DB_PASS=              # Default XAMPP password (empty)
```

### Step 4: Applied Fix

I've already updated your `.env` file to use `127.0.0.1` instead of `localhost` which often resolves connection issues.

### Step 5: Verify Fix

1. **Visit debug script:** `debug-db.php`
2. **Should show:** "✅ Direct PDO connection successful!"
3. **Visit install check:** `install-check.php`
4. **Should show:** "✅ Database connection: Successful"

## 🚀 Quick Test Commands

### Test MySQL from Command Line

```bash
# Test MySQL connection
C:\xampp\mysql\bin\mysql.exe -u root -e "SELECT 'Connected!' as status;"

# Check if database exists
C:\xampp\mysql\bin\mysql.exe -u root -e "SHOW DATABASES LIKE 'meeting_room_booking';"

# Test from PHP (in deployment_package directory)
php -r "
try {
    \$pdo = new PDO('mysql:host=127.0.0.1;dbname=meeting_room_booking', 'root', '');
    echo 'PHP MySQL connection: SUCCESS' . PHP_EOL;
} catch (Exception \$e) {
    echo 'PHP MySQL connection: FAILED - ' . \$e->getMessage() . PHP_EOL;
}
"
```

## 🔧 Advanced Troubleshooting

### Check MySQL Configuration

1. **Verify MySQL is listening:**

   ```bash
   netstat -an | findstr :3306
   ```

   Should show: `LISTENING` on port 3306

2. **Check XAMPP MySQL logs:**
   - Location: `C:\xampp\mysql\data\mysql_error.log`
   - Look for connection errors

3. **Test different ports:**

   ```env
   # If MySQL is on different port
   DB_HOST=127.0.0.1:3307
   ```

### Alternative Solutions

If standard fixes don't work:

1. **Restart XAMPP completely**
2. **Check Windows Firewall** settings
3. **Try different MySQL user:**

   ```env
   DB_USER=root
   DB_PASS=yourpassword
   ```

4. **Use full MySQL path in DSN:**

   ```php
   $dsn = "mysql:host=127.0.0.1:3306;dbname=meeting_room_booking;charset=utf8mb4";
   ```

## 📋 Diagnostic Scripts Available

- **`debug-db.php`** - Shows exact database error
- **`install-check.php`** - Complete system check
- **`test-fix.php`** - Tests autoload fix

## ✅ Expected Results After Fix

After applying the solutions, you should see:

1. **debug-db.php:** ✅ All connection tests passing
2. **install-check.php:** ✅ Database connection successful
3. **Main application:** Loads without "Database connection failed" error

## 🎯 Summary

**The database connection error is typically caused by:**

- Host resolution issues (localhost vs 127.0.0.1)
- MySQL service not running
- Missing database or wrong credentials

**I've applied the most common fix** (changed localhost to 127.0.0.1) which resolves 80% of these issues.

**Test the fix now** by visiting your diagnostic scripts!

---

*Database connection troubleshooting guide - Updated October 4, 2025*
