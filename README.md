# RentO - Equipment Rental Management System

**Module:** CMM007 Intranet Systems Development

A web-based equipment rental management system built for a university intranet. It lets admins manage equipment and users, while students can browse available items, rent them, and track their rentals. Built to run locally on XAMPP.

## Tech Stack

- HTML, CSS, JavaScript
- PHP (no frameworks)
- MySQL
- Bootstrap 5

## Setup Instructions

1. **Install XAMPP** - Download and install from [apachefriends.org](https://www.apachefriends.org/)
2. **Clone or download this repo** into your XAMPP `htdocs` folder so the path is `htdocs/rento`
3. **Start Apache and MySQL** from the XAMPP Control Panel
4. **Import the database** - Open phpMyAdmin (`http://localhost/phpmyadmin`), go to the Import tab, and import the file `sql/schema.sql`. This creates the database, tables, and sample data automatically.
5. **Open the app** - Go to `http://localhost/rento` in your browser
6. **Login** with one of the test accounts below

## Test Login Credentials

| Role  | Username | Password      |
|-------|----------|---------------|
| Admin | admin    | admin123      |
| User  | alice    | password123   |
| User  | bob      | password456   |

## Project Structure

```
rento/
├── admin/              # Admin pages (dashboard, equipment, users)
│   ├── dashboard.php
│   ├── equipment.php
│   └── users.php
├── assets/
│   ├── css/style.css   # Custom styles
│   └── js/app.js       # Client-side JavaScript
├── config/
│   └── db.php          # Database connection
├── includes/           # Shared PHP files
│   ├── auth.php        # Login/session checks
│   ├── functions.php   # Helper functions
│   ├── header.php      # Page header
│   └── footer.php      # Page footer
├── sql/
│   └── schema.sql      # Database schema + sample data
├── user/               # User pages (dashboard, browse, rentals)
│   ├── dashboard.php
│   ├── browse.php
│   └── rentals.php
├── index.php           # Login page
└── logout.php          # Logout handler
```

## Features

- **Login system** with role-based access (admin vs user)
- **Admin dashboard** with overview stats
- **Equipment management** - add, edit, and remove equipment (admin)
- **User management** - manage user accounts (admin)
- **Browse equipment** - users can view available items and rent them
- **Rental tracking** - users can see their active and past rentals
- **Return equipment** - process returns and update availability
- **Rental limits** - users have a max number of concurrent rentals
- Password hashing and prepared statements for security

## Third-Party Libraries

- [Bootstrap 5](https://getbootstrap.com/) - CSS framework for layout and styling
