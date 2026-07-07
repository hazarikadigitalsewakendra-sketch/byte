# 🧠 ByteLab Olympiad Management System (BOMS)

A comprehensive web-based management system for district-level Olympiad exams in Lakhimpur district. Streamlines student registration, payment verification, exam centre allocation, admit card generation, result publishing, and certificate distribution.

## 📋 Table of Contents

- [Features](#-features)
- [System Architecture](#-system-architecture)
- [User Roles](#-user-roles)
- [Directory Structure](#-directory-structure)
- [Installation](#-installation)
- [Database Schema](#-database-schema)
- [API & Pages Documentation](#-api--pages-documentation)
- [Admin Features](#-admin-features)
- [Coordinator Features](#-coordinator-features)
- [Public Features](#-public-features)
- [Configuration](#-configuration)
- [Security](#-security)
- [Troubleshooting](#-troubleshooting)

## ✨ Features

### ✅ Complete Features
- ✔️ **Student Registration** - Manual entry by coordinators with auto-generated registration numbers
- ✔️ **Payment Tracking** - Admin-controlled payment verification with status tracking
- ✔️ **Exam Centre Management** - Add, edit, and manage exam centres
- ✔️ **Centre Allocation** - Intelligently assign students to exam centres
- ✔️ **Admit Card Generation** - Download without login using Reg No + Mobile
- ✔️ **Result Entry & Publishing** - Admin enters marks, auto-calculates ranks, publishes results
- ✔️ **Certificate Generation** - Auto-generate certificates on result publication
- ✔️ **Coordinator Management** - Admin can add, activate/deactivate coordinators
- ✔️ **Activity Logging** - Track all admin actions and system changes
- ✔️ **Error Handling** - Centralized error logging and user-friendly error pages
- ✔️ **System Settings** - Configure exam dates, deadlines, payment amounts
- ✔️ **Roll Number Management** - Auto-generate or manually assign roll numbers
- ✔️ **User Authentication** - Secure login with password hashing

### 🎯 No Login Required For
- Admit card download
- Result checking
- Certificate download

## 🏗️ System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     PUBLIC PORTAL                            │
│  (Admit Card, Results, Certificates - No Login Required)    │
└────────────────────┬────────────────────────────────────────┘
                     │
        ┌────────────┴────────────┐
        │                         │
   ┌────▼────┐          ┌────────▼────┐
   │COORDINATOR│        │    ADMIN     │
   │ Panel    │        │   Dashboard  │
   │(Data     │        │   (Full      │
   │Entry)    │        │    Control)  │
   └────┬────┘        └────────┬────┘
        │                      │
        └──────────┬───────────┘
                   │
            ┌──────▼──────┐
            │  DATABASE   │
            │   MySQL     │
            └─────────────┘
```

## 👤 User Roles

| Role | Permissions | Access |
|------|-------------|--------|
| **ADMIN** | Full system control | `/admin/*` |
| **COORDINATOR** | Register students, view list | `/coordinator/*` |
| **STUDENT** | View admit card, results, certificate | `/public/*` (No login) |

### Admin Capabilities
- Add/edit/delete students
- Verify payments
- Manage exam centres
- Allocate students to centres
- Enter and publish results
- Generate roll numbers
- Manage coordinators
- Configure system settings
- View activity logs
- View error logs

### Coordinator Capabilities
- Register new students
- View student list with search
- View payment status
- View centre allocations

### Student/Public Capabilities
- Download admit card (Reg No + Mobile)
- Check results (Reg No + Mobile)
- Download certificate (Reg No + Mobile)

## 📁 Directory Structure

```
byte/
├── config/
│   └── db.php                      # Database connection & helpers
├── admin/
│   ├── dashboard.php               # Admin dashboard
│   ├── students.php                # Manage students (add/view)
│   ├── payment.php                 # Payment verification
│   ├── verify_payment.php          # Payment verification handler (AJAX)
│   ├── centres.php                 # Manage exam centres
│   ├── allocate.php                # Allocate students to centres
│   ├── results.php                 # Enter marks, publish results
│   ├── coordinators.php            # Manage coordinators
│   ├── settings.php                # System configuration
│   ├── activity_logs.php           # View admin activity logs
│   ├── error_logs.php              # View system errors
│   ├── roll_numbers.php            # Auto-generate roll numbers
│   └── logout.php                  # Session termination
├── coordinator/
│   ├── add_student.php             # Register new students
│   ├── student_list.php            # View all registered students
│   └── logout.php                  # Session termination
├── public/
│   ├── index.php                   # Public home page
│   ├── admit_card.php              # Download admit card (no login)
│   ├── result.php                  # View results (no login)
│   └── certificate.php             # Download certificate (no login)
├── assets/
│   ├── css/
│   │   └── style.css               # Global styles
│   ├── js/
│   │   └── script.js               # JavaScript utilities
│   └── images/                     # Image assets
├── sql/
│   └── database.sql                # Database schema & sample data
├── error_handler.php               # Centralized error handling
├── 404.php                         # Custom 404 error page
├── login.php                       # Unified login page
├── logout.php                      # Global logout handler
└── README.md                       # This file
```

## 🚀 Installation

### Prerequisites
- PHP 7.4+
- MySQL 5.7+
- Web server (Apache/Nginx)
- Browser with JavaScript enabled

### Step-by-Step Setup

1. **Clone Repository**
   ```bash
   git clone https://github.com/hazarikadigitalsewakendra-sketch/byte.git
   cd byte
   ```

2. **Create Database**
   ```bash
   mysql -u root < sql/database.sql
   ```

3. **Configure Database Connection**
   Edit `config/db.php`:
   ```php
   define('DB_HOST', 'localhost');     // Your DB host
   define('DB_USER', 'root');          // Your DB user
   define('DB_PASSWORD', '');          // Your DB password
   define('DB_NAME', 'bytelab_olympiad'); // Database name
   ```

4. **Start PHP Server**
   ```bash
   php -S localhost:8000
   ```

5. **Access System**
   - Admin: http://localhost:8000/login.php
   - Public: http://localhost:8000/public/index.php

## 📊 Database Schema

### Core Tables

| Table | Purpose | Key Fields |
|-------|---------|-----------|
| `users` | Admin & Coordinator accounts | id, username, password, role, email, status, created_at |
| `students` | Student registrations | id, registration_no, name, class, school_name, mobile, dob, payment_status, centre_id, roll_no, created_at |
| `payments` | Payment records | id, student_id, amount, payment_method, status, verification_date, verified_by |
| `exam_centres` | Exam locations | id, centre_name, location, capacity, incharge_name, incharge_phone |
| `results` | Exam results | id, student_id, marks, rank, status, created_at, updated_at |
| `certificates` | Achievement certificates | id, student_id, certificate_no, issued_date, file_path |
| `settings` | System configuration | id, setting_key, setting_value, updated_at |

### Logging Tables

| Table | Purpose |
|-------|---------|
| `activity_logs` | Track admin actions (create, update, delete, login) |
| `error_logs` | Store system errors and exceptions |

## 📚 API & Pages Documentation

### Authentication
- **Login**: `/login.php` - Unified login for Admin/Coordinator
- **Logout**: `/logout.php` - Session termination

### Admin API Endpoints
```
GET  /admin/dashboard.php           - Dashboard overview
GET  /admin/students.php            - View/Add students
POST /admin/verify_payment.php      - Payment verification (AJAX)
GET  /admin/centres.php             - Manage exam centres
GET  /admin/allocate.php            - Allocate students
GET  /admin/results.php             - Enter marks & publish
GET  /admin/coordinators.php        - Manage coordinators
GET  /admin/settings.php            - System configuration
GET  /admin/activity_logs.php       - View activity logs
GET  /admin/roll_numbers.php        - Generate roll numbers
```

### Coordinator API Endpoints
```
GET  /coordinator/add_student.php   - Register student form
GET  /coordinator/student_list.php  - View all students
```

### Public API Endpoints (No Login)
```
GET  /public/index.php              - Home page
GET  /public/admit_card.php         - Download admit card
GET  /public/result.php             - View results
GET  /public/certificate.php        - Download certificate
```

### Error Handling
```
/404.php                 - Page not found
```

## 🎯 Admin Features

### 1. Student Management
- Add students manually or bulk import
- View all registered students
- Edit student details
- Delete student records
- Search & filter students by class, school, payment status
- Track registration date & status

### 2. Payment Verification
- View pending payments
- Mark payments as verified
- Track payment details (amount, method, verification date)
- Batch verify payments

### 3. Exam Centre Management
- Add new exam centres
- Set centre capacity
- Assign incharge details
- View centre allocation summary

### 4. Student Allocation
- Allocate students to centres
- View allocation summary
- Rebalance allocations
- Filter by class and centre

### 5. Results Management
- Enter marks for students (0-100)
- Auto-calculate student ranks
- Publish results for public viewing
- View result statistics

### 6. Coordinator Management
- Add new coordinators with unique username
- Activate/Deactivate coordinators
- Delete coordinators
- View coordinator statistics

### 7. System Settings
- Configure exam date & time
- Set exam duration
- Set registration deadline
- Configure payment amount
- Set result publication date

### 8. Roll Number Management
- Auto-generate roll numbers by class (ROLL[CLASS][NUMBER])
- Manually assign roll numbers
- Reset roll numbers for a class
- View assignment progress

### 9. Activity Logging
- Track all admin actions (create, update, delete)
- View logs by user, action, entity
- Filter logs by date range
- Clear old logs

### 10. Error Logs
- View system errors
- Filter errors by type
- Clear old error logs
- Monitor PHP errors and exceptions

## 👨‍💼 Coordinator Features

### 1. Student Registration
- Register new students with form
- Auto-generate registration numbers (BDO26[5-digit])
- Enter student details (name, DOB, mobile, class, school)
- Immediate status confirmation

### 2. Student List View
- Search students by name, reg no, mobile
- Filter by payment status
- View centre allocation status
- See registration date
- Quick statistics (total, pending, verified, allocated)

## 🌐 Public Features (No Login Required)

### 1. Admit Card Download
- Search by registration number + mobile
- Display student details
- Show exam date, time, centre
- Download/Print admit card

### 2. Result Checking
- Search by registration number + mobile
- Display marks & percentile
- Show rank & performance feedback
- Print result card

### 3. Certificate Download
- Search by registration number + mobile
- Auto-generate certificate on first access
- Display achievement details
- Download/Print certificate

## ⚙️ Configuration

### System Settings (Admin Panel)
Located at `/admin/settings.php`

```php
// Exam Schedule
exam_date        // Date of exam (YYYY-MM-DD)
exam_time        // Time of exam (HH:MM)
exam_duration    // Duration in minutes (default: 120)

// Registration Settings
registration_deadline  // Last date for registration (YYYY-MM-DD)
payment_amount         // Amount to be paid (default: 100)

// Results
result_publication_date // When results will be published (YYYY-MM-DD)
```

### Database Configuration
File: `config/db.php`

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'bytelab_olympiad');
define('DB_PORT', 3306);
```

### Timezone
```php
define('TIMEZONE', 'Asia/Kolkata');
```

## 🔒 Security

### Password Security
- Passwords hashed using `PASSWORD_BCRYPT` algorithm
- Minimum 6 characters required
- Salted hashing for protection

### SQL Injection Prevention
- Prepared statements for all queries
- Input escaping with `real_escape_string()`
- Type binding in prepared statements

### Session Security
- Session timeout: 3600 seconds (1 hour)
- Session validation on each page
- Role-based access control

### Error Handling
- Centralized error handler (`error_handler.php`)
- All errors logged to database
- User-friendly error messages
- Stack traces hidden from users

## 📝 Demo Credentials

### Admin Account
```
Username: admin
Password: admin123
URL: http://localhost:8000/login.php
```

### Coordinator Account
```
Username: coordinator
Password: coord123
URL: http://localhost:8000/login.php
```

## 🔧 Troubleshooting

### Database Connection Issues
```
Error: "Connection Failed"
Solution: Check config/db.php for correct database credentials
```

### Payment Verification Not Working
```
Error: "Error saving payment"
Solution: Ensure payments table exists in database
Check: mysql> DESCRIBE payments;
```

### Roll Numbers Not Generating
```
Error: "No students without roll numbers"
Solution: Ensure students exist and don't have roll numbers assigned
```

### Activity Logs Empty
```
Solution: Logs are created for admin actions only
Check database: SELECT * FROM activity_logs ORDER BY created_at DESC;
```

### 404 Page Not Working
```
Solution: Configure .htaccess to route 404s to 404.php
Add to .htaccess: ErrorDocument 404 /404.php
```

## 📦 Dependencies

- **PHP 7.4+** - Server-side language
- **MySQL 5.7+** - Database
- **Apache/Nginx** - Web server
- **Browsers** - Modern JavaScript support

## 🎓 Usage Workflow

### Complete Registration Workflow

1. **Coordinator Registers Student**
   - Login as Coordinator
   - Add student via `/coordinator/add_student.php`
   - System auto-generates Reg No (e.g., BDO260001)

2. **Admin Verifies Payment**
   - Admin reviews pending payments
   - Marks payment as verified
   - Student's payment_status → 'paid'

3. **Admin Allocates to Centre**
   - Admin assigns student to exam centre
   - Student's centre_id is set

4. **Admin Generates Roll Numbers**
   - Admin auto-generates or manually assigns
   - Format: ROLL[CLASS][NUMBER] (e.g., ROLL10001)

5. **Admin Publishes Results**
   - Enter marks for each student
   - Calculate ranks
   - Publish results

6. **Student Downloads Certificate**
   - Use Reg No + Mobile at `/public/certificate.php`
   - Certificate auto-generated with:
     - Student name & achievements
     - Marks & rank
     - Certificate number

## 📞 Support & Contact

For issues or feature requests:
- Email: hazarikadigitalsewakendra@gmail.com
- Repository: https://github.com/hazarikadigitalsewakendra-sketch/byte

## 📄 License

This project is proprietary and intended for use by ByteLab Olympiad Management.

## 🤝 Contributing

Contributions are welcome! Please follow these steps:
1. Fork the repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📈 Version History

### v1.0.0 (Current) - Complete Release
- ✅ Full student registration workflow
- ✅ Payment verification system
- ✅ Exam centre management
- ✅ Admit card generation & download
- ✅ Results entry & publication
- ✅ Certificate generation & download
- ✅ Coordinator management
- ✅ Activity & error logging
- ✅ System settings configuration
- ✅ Roll number management
- ✅ Centralized error handling
- ✅ Custom 404 page

## 🎯 Features Status

| Feature | Status | Notes |
|---------|--------|-------|
| Student Registration | ✅ Complete | Manual entry via coordinators |
| Payment Tracking | ✅ Complete | Admin verification model |
| Exam Centres | ✅ Complete | Full CRUD operations |
| Centre Allocation | ✅ Complete | Assign students to centres |
| Admit Cards | ✅ Complete | Download without login |
| Results Management | ✅ Complete | Auto-rank calculation |
| Certificates | ✅ Complete | Auto-generation on result publish |
| Coordinator Mgmt | ✅ Complete | Add/activate/deactivate |
| Activity Logs | ✅ Complete | Track all admin actions |
| Error Handling | ✅ Complete | Centralized logging |
| Settings Config | ✅ Complete | System-wide parameters |
| Roll Numbers | ✅ Complete | Auto-generate or manual |
| Search & Filter | ✅ Complete | In-page search implemented |
| Pagination | ✅ Complete | For large datasets |
| User Authentication | ✅ Complete | Secure login system |

---

**Version**: 1.0.0  
**Last Updated**: 2026-07-07  
**Status**: Production Ready ✅
