# GYM MANAGEMENT SYSTEM (DBMS Mini-Project)

A simple, clean, and professional web-based **Gym Management System** built for a **B.Tech Database Management Systems (DBMS) Mini-Project**.

---

## 🛠️ Technology Stack

* **Frontend:** HTML5, Vanilla CSS3 (Custom Design System), JavaScript (ES6, Vanilla DOM)
* **Backend:** PHP (Native / Procedural with Prepared Statements)
* **Database:** MySQL
* **Local Development Server:** XAMPP (Apache & MySQL)

> **Note:** Zero external framework dependencies. No React, Node.js, Bootstrap, or TailwindCSS. Clean, beginner-friendly code adhering to university syllabus standards.

---

## 🎨 Color Palette & Design System

* **Primary Color:** Dark Navy / Dark Slate (`#0f172a`, `#1e293b`)
* **Secondary Color:** Pure White (`#ffffff`)
* **Accent Color:** Energetic Orange (`#ff6b35`)
* **Background Color:** Light Gray (`#f4f6f9`)
* **Status Badges:** Green (Active / Present / Working), Amber (Under Maintenance / Warning), Red (Expired / Absent / Needs Repair)

---

## 📁 Project Directory Structure

```text
gym_management/
│
├── index.php             # Login authentication page with demo credentials
├── dashboard.php         # Main dashboard with KPI cards and recent summary tables
├── members.php           # Member management (Add, View, Edit, Delete, Search)
├── trainers.php          # Trainer management (Add, Edit, Delete, Salary, Search)
├── membership.php        # Membership subscriptions (Plans, Durations, Status)
├── payments.php          # Payment transactions & printable receipts (UPI, Cash, Card)
├── attendance.php        # Daily attendance tracking & date-based filters
├── workout.php           # Personalized workout plan assignment (Member + Trainer)
├── equipment.php         # Gym equipment & machinery condition inventory
├── db.php                # Database connection & reusable helper functions
├── header.php            # Shared layout (Left Sidebar & Top Sticky Header)
├── footer.php            # Shared layout (Scripts & Footer)
├── logout.php            # Session destruction and logout handler
│
├── css/
│   └── style.css         # Complete vanilla stylesheet (No frameworks)
│
├── js/
│   └── script.js         # Vanilla JavaScript (Modals, search, date calculations)
│
└── sql/
    └── gym_management.sql # Complete database creation & sample data script
```

---

## 🗄️ Database Schema & Relational Model

The MySQL database is named **`gym_management`** and contains 8 tables connected with proper **Primary Keys**, **Foreign Keys (`ON DELETE CASCADE ON UPDATE CASCADE`)**, and constraints:

1. **`Admin`**: Stores administrative credentials for secure login.
   * `admin_id` (PK), `username`, `password`, `full_name`, `email`
2. **`Member`**: Core gym member personal details.
   * `member_id` (PK), `name`, `gender`, `age`, `phone`, `email`, `join_date`
3. **`Trainer`**: Gym instructors and specializations.
   * `trainer_id` (PK), `name`, `phone`, `specialization`, `salary`
4. **`Membership`**: Subscription plans.
   * `membership_id` (PK), `member_id` (FK &rarr; `Member`), `plan_name`, `start_date`, `end_date`, `amount`
5. **`Payment`**: Financial records and invoicing.
   * `payment_id` (PK), `member_id` (FK &rarr; `Member`), `payment_date`, `amount`, `payment_method`
6. **`Attendance`**: Daily attendance tracking.
   * `attendance_id` (PK), `member_id` (FK &rarr; `Member`), `attendance_date`, `status`
7. **`Workout_Plan`**: Training regimens prescribed to members by trainers.
   * `workout_id` (PK), `member_id` (FK &rarr; `Member`), `trainer_id` (FK &rarr; `Trainer`), `workout_name`, `workout_date`
8. **`Equipment`**: Inventory tracking for machines and free weights.
   * `equipment_id` (PK), `equipment_name`, `quantity`, `purchase_date`, `condition_status`

---

## 🚀 Setup & Execution Guide (Using XAMPP)

### Step 1: Start XAMPP
1. Open the **XAMPP Control Panel**.
2. Click **Start** next to **Apache**.
3. Click **Start** next to **MySQL**.

### Step 2: Import the Database
1. Open your web browser and go to: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. In the top navigation, click on the **Import** tab.
3. Click **Choose File** and browse to:
   `gym_management/sql/gym_management.sql` (or `e:/GymManagementSystem/sql/gym_management.sql`)
4. Click the **Import** (or **Go**) button at the bottom.
5. The database `gym_management` and all 8 tables with rich sample records will be created automatically.

### Step 3: Run the Application
1. The project has already been placed in your XAMPP web root:
   `C:\xampp\htdocs\gym_management`
2. Open your browser and navigate to:
   👉 **[http://localhost/gym_management](http://localhost/gym_management)**
3. Log in with the default credentials:
   * **Username:** `admin`
   * **Password:** `admin123`

---

## ✨ Features Included

* **Interactive Dashboard:**
  * 5 KPI Cards: Total Members, Active Memberships, Total Trainers, Today's Attendance, and Total Payments collected.
  * Recent Members table, Recent Payments table, and Today's Attendance overview.
* **Full CRUD Operations:** Create, Read, Update, and Delete on Members, Trainers, Memberships, Payments, Attendance, Workout Plans, and Equipment.
* **Instant Client-Side & Server-Side Search:** Search any table instantaneously as you type.
* **Receipt Generation & Printing:** One-click invoice modal and printer slip for member fee payments.
* **Automatic Membership Date & Fee Calculator:** Picking a plan automatically calculates end dates and suggests pricing.
* **Clean UI & Responsive Modals:** Modal dialogs for adding and editing records without clutter.
