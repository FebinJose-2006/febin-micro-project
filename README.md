# ParkSys Fresh: Smart Parking System

## Overview

**ParkSys Fresh** is a web-based Smart Parking System designed to manage parking lots, slots, and bookings efficiently. It provides a simple customer interface for searching and instantly booking available slots, along with a secure, feature-rich administrator panel for system management.

The system is built using PHP, MySQL, and the Bootstrap 5 framework.

## Features

### Customer Features

  * **User Authentication:** Secure registration and login for customer accounts.
  * **Search Slots:** Users can select a parking lot and view the real-time availability of slots via an API call.
  * **Instant Booking:** Available slots can be booked immediately for a default duration of 1 hour. The booking process uses database transactions to ensure data consistency and prevent conflicts.
  * **My Bookings:** View a list of all current, completed, and cancelled bookings.

### Administrator Features

  * **Secure Admin Panel:** Access is restricted to users with the `admin` role.
  * **Manage Lots:** Add, view, and delete parking lot locations. Deleting a lot automatically removes all associated slots and bookings (via `ON DELETE CASCADE`).
  * **Manage Slots:** Filter slots by lot, add new slots, toggle the status of existing slots, and delete slots.
  * **Manage Bookings:** View all system bookings and manually update their status (`booked`, `completed`, `cancelled`).
  * **Manage Users:** View and delete user accounts (with safeguards to prevent deleting one's own account or the only remaining admin).
  * **Reports:** Access reports on daily bookings, top parking lots, and slot availability/occupancy overview.

## Prerequisites

To run this project, you need a web server environment with:

  * **PHP** (with PDO extension)
  * **MySQL / MariaDB**
  * **Composer** (Recommended for managing PHP dependencies, although not explicitly used, it's standard practice)

## Installation and Setup

### 1\. Database Setup

The database connection is configured in `parking_system_fresh/config/db.php`.

1.  **Create Database:** Create a new MySQL database named `parking_system_db`.
    ```sql
    CREATE DATABASE parking_system_db;
    ```
2.  **Configure Database Credentials:** Open `parking_system_fresh/config/db.php` and update the credentials:
    ```php
    $db_host = 'localhost'; // Or 127.0.0.1
    $db_name = 'parking_system_db';
    $db_user = 'root'; // Your database username
    $db_pass = '';     // Your database password
    ```
3.  **Run Schema:** Import the database structure and initial data using `parking_system_fresh/sql/schema.sql`:
    ```sql
    -- Use the database
    USE parking_system_db;
    -- Paste the contents of parking_system_fresh/sql/schema.sql here
    ```

### 2\. Initial Admin User

The schema file automatically inserts a default administrator account.

| Role | Email | Password |
| :--- | :--- | :--- |
| **Admin** | `admin@parksys.com` | `adminpass` |

> **Note:** The password is set using a hashed value for "adminpass" (`$2y$10$9.B2yJ6kZ4L4K4O4I4E4fOu5n5o5k5A5d5m5i5n5P5a5s5s`).

### 3\. Application Configuration

The core application settings are in `parking_system_fresh/config/init.php`.

  * The script attempts to automatically determine the `BASE_URL`. If you run into redirection issues, ensure your server configuration (e.g., Apache Virtual Host or .htaccess) correctly points to the project's root folder.
  * The application's name is defined as `ParkSys Fresh`.

## Usage

1.  Navigate to the project root in your web browser (e.g., `http://localhost/parking_system_fresh/`).
2.  **Customer Access:** Use the main navigation bar to **Register** or **Login** to search for slots.
3.  **Admin Access:** Log in with the default admin credentials and access the **Admin Panel** link from the header, or directly navigate to `/admin/index.php`.

## Dependencies

The project relies on external libraries via Content Delivery Networks (CDNs):

  * **Bootstrap 5.3.3:** For styling and interactive components.
  * **Font Awesome 6.5.1:** For icons.
