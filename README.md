# 🧠 ByteLab District Olympiad System (BOMS)

A lightweight web-based management system for district-level Olympiad exams in Lakhimpur district.

## 🎯 Purpose

- Student registration (manual entry)
- Payment tracking (admin controlled)
- Exam centre allocation
- Admit card generation (no login required)
- Result publishing
- Certificate generation

## 🏗️ System Architecture

```
COORDINATOR → Admin Panel → Database → Public Pages (Student Access)
```

## 👤 User Roles

- **ADMIN**: Master control (add/edit students, approve payments, assign centres, publish results)
- **COORDINATOR**: Data entry agent (manually register students)
- **STUDENT**: No login required (access via public pages)

## 📁 Directory Structure

```
Bytelab/
├── config/
│   └── db.php                 # Database connection
├── admin/
│   ├── dashboard.php
│   ├── students.php
│   ├── payment.php
│   ├── centres.php
│   ├── allocate.php
│   ├── results.php
│   └── logout.php
├── coordinator/
│   ├── add_student.php
│   ├── student_list.php
│   └── logout.php
├── public/
│   ├── index.php
│   ├── admit_card.php
│   ├── result.php
│   └── certificate.php
├── assets/
│   ├── css/style.css
│   ├── js/script.js
│   └── images/
├── sql/
│   └── database.sql
├── login.php
└── index.html
```

## 🚀 Getting Started

1. Set up database using `sql/database.sql`
2. Configure database connection in `config/db.php`
3. Access admin panel at `/admin/`
4. Start managing olympiad exams

## ✨ Key Features

✔ No student login required
✔ Simple manual workflow
✔ Scalable design
✔ Lightweight PHP-based
✔ Offline-friendly payment model

**Version**: 1.0.0-MVP
