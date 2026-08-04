# CityLawyers – Legal Services Platform
### Complete Documentation

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Core Purpose](#2-core-purpose)
3. [User Types & Their Roles](#3-user-types--their-roles)
4. [Platform Architecture](#4-platform-architecture)
5. [Key Functional Modules](#5-key-functional-modules)
6. [Security Features](#6-security-features)
7. [Design Philosophy](#7-design-philosophy)
8. [Key Statistics & Metrics](#8-key-statistics--metrics)
9. [Unique Features](#9-unique-features)
10. [Future Enhancements](#10-future-enhancements-planned)
11. [Project Structure](#11-project-structure)
12. [Success Metrics](#12-success-metrics)
13. [Conclusion](#13-conclusion)
14. [Technical Stack Summary](#14-technical-stack-summary)

---

## 1. Project Overview

### What is CityLawyers?
CityLawyers is a comprehensive legal services marketplace platform that connects clients with verified lawyers across Pakistan. It serves as a digital bridge between legal professionals and individuals seeking legal assistance, making the process of finding, comparing, and booking legal services efficient, transparent, and accessible.

[↑ Back to top](#table-of-contents)

---

## 2. Core Purpose

The platform solves three main problems:

**For Clients (Individuals/Corporate)**
- Difficulty finding qualified lawyers in their city
- Lack of transparency about lawyer fees, experience, and ratings
- No centralized platform to compare legal professionals
- Complicated appointment booking process

**For Lawyers (Legal Professionals)**
- Limited visibility and client acquisition channels
- Difficulty managing appointments and client inquiries
- No professional profile management system
- Limited analytics about their practice

**For Platform Admin**
- Need to vet and approve lawyers to maintain quality
- Manage content and categories displayed on homepage
- Oversee platform operations and user management

[↑ Back to top](#table-of-contents)

---

## 3. User Types & Their Roles

### 3.1 Clients (Customers)
**Purpose:** Find and book legal services

**Key Features:**
- Search lawyers by city, specialization, experience, and fees
- View detailed lawyer profiles with ratings and reviews
- Book appointments with selected lawyers
- Manage their appointments (view, cancel, reschedule)
- Review lawyers after consultation
- View appointment history

### 3.2 Lawyers
**Purpose:** Offer legal services and manage practice

**Key Features:**
- Register with professional details (bar council number, experience)
- Create and manage professional profile
- Set fees and availability
- View and manage appointments from clients
- Track appointment history and statistics
- Manage profile visibility and featured status
- View client reviews

### 3.3 Administrators
**Purpose:** Manage the platform and ensure quality

**Key Features:**
- Approve/reject lawyer registrations
- Manage categories (CRUD operations)
- Select featured lawyers for homepage
- Oversee all appointments
- Manage content displayed on homepage
- Dashboard with key metrics and statistics

[↑ Back to top](#table-of-contents)

---

## 4. Platform Architecture

### Frontend
- **Technology:** PHP, HTML, CSS, JavaScript (Bootstrap)
- **Design Approach:** Responsive, mobile-first, professional legal theme

**Key Pages:**
- Public pages: Homepage, Lawyer profiles, Search results
- Client dashboard: Appointments, Profile management
- Lawyer dashboard: Appointments, Profile, Statistics
- Admin dashboard: Content management, User approval, Analytics

### Backend
- **Technology:** PHP (Native/PDO)

**Security:**
- Session-based authentication
- Password hashing (password_verify)
- Input validation and sanitization
- Prepared statements for SQL injection prevention

**Architecture:**
- Modular file structure
- Reusable components (header, footer, sidebar)
- Separate CSS for each section

### Database
- **Technology:** MySQL/PDO

**Design:**
- Normalized relational database
- Foreign key constraints for data integrity
- Indexed columns for performance optimization

[↑ Back to top](#table-of-contents)

---

## 5. Key Functional Modules

### 5.1 User Authentication & Authorization
- Login/registration for all user types
- Role-based access control
- Session management
- Password security with hashing

### 5.2 Lawyer Management
- Registration with approval workflow
- Profile management (photo, specialization, fees, experience)
- Featured lawyer selection by admin
- Status management (pending → approved → active/inactive)

### 5.3 Appointment System
- Book appointments with lawyers
- Status tracking (pending → confirmed → completed/cancelled)
- Calendar integration (planned)
- Email notifications (planned)

### 5.4 Content Management (Admin)
- Dynamic categories (CRUD)
- Homepage content management
- Featured lawyers selection
- Statistics and reporting dashboard

### 5.5 Search & Filter
- Multi-criteria search (city, specialization, fees)
- Filter by experience, ratings, appointments
- Pagination for search results

### 5.6 Reviews & Ratings
- Clients can review lawyers after appointments
- Average rating system
- Display on lawyer profiles and search results

[↑ Back to top](#table-of-contents)

---

## 6. Security Features

| Security Measure | Implementation |
|---|---|
| Password Security | password_verify() with bcrypt hashing |
| Session Security | PHP sessions with proper timeout |
| SQL Injection Prevention | PDO prepared statements |
| XSS Prevention | htmlspecialchars() on all output |
| CSRF Protection | (recommended addition) |
| Input Validation | Server-side validation on all forms |

[↑ Back to top](#table-of-contents)

---

## 7. Design Philosophy

### Visual Theme
- **Color Palette:** Professional, trust-focused (deep greens, golds, earth tones)
- **Typography:** Serif fonts (Cormorant Garamond) for elegance
- **Layout:** Clean, spacious, with clear visual hierarchy
- **Iconography:** Font Awesome icons for intuitive navigation

### User Experience
- **Mobile-First:** Fully responsive across all devices
- **Progressive Disclosure:** Information presented in digestible chunks
- **Consistent Patterns:** Similar UI elements across all pages
- **Feedback:** Clear success/error messages for all actions

[↑ Back to top](#table-of-contents)

---

## 8. Key Statistics & Metrics

| Metric | Description |
|---|---|
| Total Lawyers | All registered lawyers in system |
| Pending Approvals | Lawyers awaiting admin approval |
| Approved/Rejected | Status breakdown of lawyer registrations |
| Total Clients | Registered client accounts |
| Total Appointments | All appointments in system |
| Pending Appointments | Appointments awaiting confirmation |
| Completed Appointments | Successfully completed appointments |
| Total Categories | Active practice areas |

[↑ Back to top](#table-of-contents)

---

## 9. Unique Features

- **Fallback System:** If no featured lawyers exist, automatically shows top-rated lawyers
- **Dynamic Categories:** Admin can add/remove categories without code changes
- **Role-Based Views:** Same system behaves differently for clients, lawyers, and admins
- **Progressive Enhancement:** Works with or without JavaScript
- **Smart Pagination:** Preserves filters and selections across pages

[↑ Back to top](#table-of-contents)

---

## 10. Future Enhancements (Planned)

- **Payment Integration** – Online fee payment and booking
- **Email Notifications** – Appointment reminders and confirmations
- **Real-time Chat** – Direct communication between client and lawyer
- **Multi-language Support** – Urdu/English bilingual interface
- **Calendar Sync** – Google Calendar integration for appointments
- **Document Upload** – Share legal documents securely
- **Video Consultations** – Virtual meetings integration
- **Mobile App** – Native iOS/Android applications
- **Advanced Analytics** – Detailed statistics for lawyers
- **AI-powered Lawyer Recommendation** – Match based on case type
- **active/inactive status control by admin** - to control users action(e.g:spam activities)
- **inactivate further bookings by lawyer** - when more appointments in queue or simply disable slots

[↑ Back to top](#table-of-contents)

---

## 11. Project Structure

citylawyers/
│
├── admin/
│   ├── index.php                    # Admin Dashboard
│   ├── login.php                    # Admin Login
│   ├── manage-appointments.php      # Manage All Appointments
│   ├── manage-content.php           # Homepage Content (Featured Lawyers + Categories)
│   ├── manage-customers.php         # Manage Customer Accounts
│   ├── manage-lawyers.php           # Manage Lawyer Registrations & Approvals
│   ├── manage-reviews.php           # Manage All Reviews
│   └── notifications.php            # Admin Notifications
│
├── assets/
│   ├── css/
│   │   ├── auth.css                 # Login/Register Pages
│   │   ├── book-appointment.css     # Booking Page
│   │   ├── chat.css                 # Chat Interface
│   │   ├── dashboard-footer.css     # Dashboard Footer
│   │   ├── dashboard.css            # Dashboard Layout
│   │   ├── footer.css               # Public Footer
│   │   ├── forms.css                # Form Styles
│   │   ├── homepage.css             # Public Homepage
│   │   ├── lawyer-profile.css       # Lawyer Profile Page
│   │   ├── register.css             # Registration Pages
│   │   ├── search.css               # Search Results
│   │   ├── sidebar.css              # Dashboard Sidebar
│   │   └── tables.css               # Data Tables
│   │
│   ├── images/
│   │   ├── citylawyers_logo.png
│   │   ├── hero-img.png
│   │   └── [other images]
│   │
│   └── js/
│       └── how-it-works.js          # Homepage Interactive Guide
│
├── customer/
│   ├── book-appointment.php         # Book Appointment with Lawyer
│   ├── chat.php                     # Client Chat with Lawyer
│   ├── index.php                    # Customer Dashboard
│   ├── lawyer-profile.php           # View Lawyer Profile
│   ├── login.php                    # Customer Login
│   ├── my-appointments.php          # My Appointments List
│   ├── review.php                   # Submit Lawyer Review
│   └── search.php                   # Search Lawyers
│
├── database/
│   └── schema.sql                   # Complete Database Schema
│
├── includes/
│   ├── config.php                   # Database Configuration
│   ├── dashboard-footer.php         # Dashboard Footer
│   ├── dashboard-sidebar.php        # Dashboard Sidebar
│   ├── footer.php                   # Public Footer
│   ├── functions.php                # Helper Functions
│   ├── get-messages.php             # AJAX: Get Chat Messages
│   ├── header.php                   # Public Header
│   ├── mark-messages-read.php       # AJAX: Mark Messages as Read
│   ├── notifications.php            # AJAX: Get Notifications
│   └── send-messages.php            # AJAX: Send Chat Messages
│
├── lawyer/
│   ├── appointments.php             # Lawyer Appointments
│   ├── chat.php                     # Lawyer Chat with Clients
│   ├── index.php                    # Lawyer Dashboard
│   ├── login.php                    # Lawyer Login
│   ├── manage-slots.php             # Manage Availability Slots
│   ├── notifications.php            # Lawyer Notifications
│   ├── profile.php                  # Lawyer Profile Management
│   └── reviews.php                  # Lawyer Reviews (empty)
│
├── uploads/
│   └── lawyers/                     # Lawyer Profile Pictures
│       ├── [lawyer_id]_profile.jpg
│       └── [other uploaded files]
│
├── contact.php                      # Contact Page
├── index.php                        # Public Homepage
├── login.php                        # User Login (Role Selection)
├── logout.php                       # Logout Handler
└── register.php                     # User Registration (Role Selection)

[↑ Back to top](#table-of-contents)

---

## 12. Success Metrics

The platform's success is measured by:
- **User Adoption:** Number of registered lawyers and clients
- **Engagement:** Active appointments and searches
- **Quality:** Lawyer approval rate and client reviews
- **Growth:** New registrations month-over-month
- **Satisfaction:** Positive reviews and repeat bookings

[↑ Back to top](#table-of-contents)

---

## 13. Conclusion

CityLawyers represents a modern, comprehensive solution to the age-old problem of finding quality legal representation. By digitizing and streamlining the lawyer-client discovery and booking process, the platform increases transparency, improves access to justice, and creates a thriving digital ecosystem for legal professionals in Pakistan.

[↑ Back to top](#table-of-contents)

---

## 14. Technical Stack Summary

| Layer | Technology |
|---|---|
| Frontend | PHP, HTML, CSS, JavaScript (Bootstrap) |
| Backend | PHP (Native/PDO) |
| Database | MySQL/PDO |

[↑ Back to top](#table-of-contents)
