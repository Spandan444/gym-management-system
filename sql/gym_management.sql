-- ====================================================================
-- GYM MANAGEMENT SYSTEM - DATABASE CREATION & SAMPLE DATA SCRIPT
-- Course: B.Tech DBMS Mini-Project
-- Database: gym_management
-- RDBMS: MySQL (XAMPP / phpMyAdmin compatible)
-- ====================================================================

-- 1. Create Database if it does not already exist
CREATE DATABASE IF NOT EXISTS `gym_management` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `gym_management`;

-- Disable foreign key checks during drop and recreate
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `Workout_Plan`;
DROP TABLE IF EXISTS `Attendance`;
DROP TABLE IF EXISTS `Payment`;
DROP TABLE IF EXISTS `Membership`;
DROP TABLE IF EXISTS `Equipment`;
DROP TABLE IF EXISTS `Trainer`;
DROP TABLE IF EXISTS `Member`;
DROP TABLE IF EXISTS `Admin`;

SET FOREIGN_KEY_CHECKS = 1;

-- ====================================================================
-- TABLE 0: Admin (System Authentication)
-- ====================================================================
CREATE TABLE IF NOT EXISTS `Admin` (
    `admin_id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ====================================================================
-- TABLE 1: Member
-- Stores personal details of gym members
-- ====================================================================
CREATE TABLE IF NOT EXISTS `Member` (
    `member_id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `age` INT NOT NULL,
    `phone` VARCHAR(15) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `join_date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ====================================================================
-- TABLE 2: Trainer
-- Stores fitness trainer profiles, specializations, and salaries
-- ====================================================================
CREATE TABLE IF NOT EXISTS `Trainer` (
    `trainer_id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(15) NOT NULL,
    `specialization` VARCHAR(100) NOT NULL,
    `salary` DECIMAL(10,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ====================================================================
-- TABLE 3: Membership
-- Tracks member subscriptions and plan durations
-- Foreign Key: member_id -> Member(member_id)
-- ====================================================================
CREATE TABLE IF NOT EXISTS `Membership` (
    `membership_id` INT AUTO_INCREMENT PRIMARY KEY,
    `member_id` INT NOT NULL,
    `plan_name` ENUM('Monthly', 'Quarterly', 'Half Yearly', 'Yearly') NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_membership_member` 
        FOREIGN KEY (`member_id`) REFERENCES `Member`(`member_id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ====================================================================
-- TABLE 4: Payment
-- Records all financial transactions for memberships
-- Foreign Key: member_id -> Member(member_id)
-- ====================================================================
CREATE TABLE IF NOT EXISTS `Payment` (
    `payment_id` INT AUTO_INCREMENT PRIMARY KEY,
    `member_id` INT NOT NULL,
    `payment_date` DATE NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `payment_method` ENUM('Cash', 'UPI', 'Card') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_payment_member` 
        FOREIGN KEY (`member_id`) REFERENCES `Member`(`member_id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ====================================================================
-- TABLE 5: Attendance
-- Logs daily attendance for gym members
-- Foreign Key: member_id -> Member(member_id)
-- ====================================================================
CREATE TABLE IF NOT EXISTS `Attendance` (
    `attendance_id` INT AUTO_INCREMENT PRIMARY KEY,
    `member_id` INT NOT NULL,
    `attendance_date` DATE NOT NULL,
    `status` ENUM('Present', 'Absent') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_attendance_member` 
        FOREIGN KEY (`member_id`) REFERENCES `Member`(`member_id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ====================================================================
-- TABLE 6: Workout_Plan
-- Assigns personalized workout schedules to members by trainers
-- Foreign Keys: member_id -> Member(member_id), trainer_id -> Trainer(trainer_id)
-- ====================================================================
CREATE TABLE IF NOT EXISTS `Workout_Plan` (
    `workout_id` INT AUTO_INCREMENT PRIMARY KEY,
    `member_id` INT NOT NULL,
    `trainer_id` INT NOT NULL,
    `workout_name` VARCHAR(100) NOT NULL,
    `workout_date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_workout_member` 
        FOREIGN KEY (`member_id`) REFERENCES `Member`(`member_id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_workout_trainer` 
        FOREIGN KEY (`trainer_id`) REFERENCES `Trainer`(`trainer_id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ====================================================================
-- TABLE 7: Equipment
-- Tracks gym machinery, inventory quantities, and maintenance condition
-- ====================================================================
CREATE TABLE IF NOT EXISTS `Equipment` (
    `equipment_id` INT AUTO_INCREMENT PRIMARY KEY,
    `equipment_name` VARCHAR(100) NOT NULL,
    `quantity` INT NOT NULL,
    `purchase_date` DATE NOT NULL,
    `condition_status` ENUM('Working', 'Under Maintenance', 'Needs Repair') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ====================================================================
-- SAMPLE DATA INSERTIONS
-- Realistic records for project demonstration and testing
-- ====================================================================

-- 1. Insert Default Admin User (Password: admin123)
-- Stored as plain text and verified safely with fallback in PHP
INSERT INTO `Admin` (`admin_id`, `username`, `password`, `full_name`, `email`) VALUES
(1, 'admin', 'admin123', 'Gym Administrator', 'admin@gymmanagement.com');

-- 2. Insert Sample Members
INSERT INTO `Member` (`member_id`, `name`, `gender`, `age`, `phone`, `email`, `join_date`) VALUES
(1, 'Aarav Sharma', 'Male', 24, '9876543210', 'aarav.sharma@example.com', '2024-01-15'),
(2, 'Priya Patel', 'Female', 28, '9823456781', 'priya.patel@example.com', '2024-02-01'),
(3, 'Rohan Verma', 'Male', 31, '9890123456', 'rohan.verma@example.com', '2024-02-20'),
(4, 'Sneha Reddy', 'Female', 22, '9765432109', 'sneha.reddy@example.com', '2024-03-05'),
(5, 'Vikram Malhotra', 'Male', 35, '9845123450', 'vikram.m@example.com', '2024-03-18'),
(6, 'Ananya Iyer', 'Female', 26, '9912345678', 'ananya.iyer@example.com', '2024-04-02'),
(7, 'Kabir Mehta', 'Male', 29, '9811223344', 'kabir.mehta@example.com', '2024-04-12'),
(8, 'Tanvi Joshi', 'Female', 25, '9877889900', 'tanvi.joshi@example.com', '2024-05-01');

-- 3. Insert Sample Trainers
INSERT INTO `Trainer` (`trainer_id`, `name`, `phone`, `specialization`, `salary`) VALUES
(1, 'Marcus Vance', '9812300001', 'Strength & Hypertrophy', 45000.00),
(2, 'Deepak Rawat', '9812300002', 'CrossFit & Conditioning', 42000.00),
(3, 'Simran Kaur', '9812300003', 'Cardio, HIIT & Weight Loss', 38000.00),
(4, 'Arjun Deshmukh', '9812300004', 'Functional Fitness & Calisthenics', 40000.00),
(5, 'Neha Kapoor', '9812300005', 'Yoga & Posture Rehabilitation', 36000.00);

-- 4. Insert Sample Memberships
INSERT INTO `Membership` (`membership_id`, `member_id`, `plan_name`, `start_date`, `end_date`, `amount`) VALUES
(1, 1, 'Yearly', '2024-01-15', '2025-01-14', 15000.00),
(2, 2, 'Half Yearly', '2024-02-01', '2024-08-01', 8500.00),
(3, 3, 'Quarterly', '2024-02-20', '2024-05-20', 4800.00),
(4, 4, 'Monthly', '2024-03-05', '2024-04-05', 1800.00),
(5, 5, 'Yearly', '2024-03-18', '2025-03-17', 15000.00),
(6, 6, 'Quarterly', '2024-04-02', '2024-07-02', 4800.00),
(7, 7, 'Monthly', '2024-04-12', '2024-05-12', 1800.00),
(8, 8, 'Half Yearly', '2024-05-01', '2024-11-01', 8500.00);

-- 5. Insert Sample Payments
INSERT INTO `Payment` (`payment_id`, `member_id`, `payment_date`, `amount`, `payment_method`) VALUES
(1, 1, '2024-01-15', 15000.00, 'UPI'),
(2, 2, '2024-02-01', 8500.00, 'Card'),
(3, 3, '2024-02-20', 4800.00, 'Cash'),
(4, 4, '2024-03-05', 1800.00, 'UPI'),
(5, 5, '2024-03-18', 15000.00, 'Card'),
(6, 6, '2024-04-02', 4800.00, 'UPI'),
(7, 7, '2024-04-12', 1800.00, 'Cash'),
(8, 8, '2024-05-01', 8500.00, 'UPI');

-- 6. Insert Sample Attendance (Using CURDATE() so current dashboard displays active entries)
INSERT INTO `Attendance` (`attendance_id`, `member_id`, `attendance_date`, `status`) VALUES
(1, 1, CURDATE(), 'Present'),
(2, 2, CURDATE(), 'Present'),
(3, 3, CURDATE(), 'Absent'),
(4, 4, CURDATE(), 'Present'),
(5, 5, CURDATE(), 'Present'),
(6, 6, CURDATE(), 'Absent'),
(7, 7, CURDATE(), 'Present'),
(8, 8, CURDATE(), 'Present');

-- 7. Insert Sample Workout Plans
INSERT INTO `Workout_Plan` (`workout_id`, `member_id`, `trainer_id`, `workout_name`, `workout_date`) VALUES
(1, 1, 1, 'Push Day: Chest, Shoulders & Triceps', '2024-05-10'),
(2, 2, 3, 'High Intensity Interval Training (HIIT)', '2024-05-10'),
(3, 3, 2, 'Leg Hypertrophy & Core Stability', '2024-05-11'),
(4, 4, 5, 'Vinyasa Flow & Deep Mobility', '2024-05-11'),
(5, 5, 1, 'Pull Day: Back, Rear Delts & Biceps', '2024-05-12'),
(6, 6, 4, 'Functional Calisthenics & Ring Work', '2024-05-12'),
(7, 7, 2, 'Deadlift Mechanics & Posterior Chain', '2024-05-13'),
(8, 8, 3, 'Endurance Cardio & Kettlebell Circuit', '2024-05-13');

-- 8. Insert Sample Equipment
INSERT INTO `Equipment` (`equipment_id`, `equipment_name`, `quantity`, `purchase_date`, `condition_status`) VALUES
(1, 'Olympic Barbell & Bumper Plate Set (140kg)', 6, '2023-01-10', 'Working'),
(2, 'Commercial Heavy Duty Treadmill T-900', 4, '2023-03-15', 'Working'),
(3, 'Adjustable Dumbbell Rack (2.5kg - 40kg)', 2, '2023-02-20', 'Working'),
(4, 'Dual Cable Crossover Pulley Station', 2, '2023-04-05', 'Working'),
(5, 'Linear Incline Leg Press Machine 45°', 2, '2023-05-18', 'Working'),
(6, 'Concept2 Model D Indoor Rowing Machine', 3, '2023-06-22', 'Under Maintenance'),
(7, 'Multi-Grip Commercial Smith Machine', 1, '2023-07-09', 'Working'),
(8, 'Stationary Air Resistance Assault Bike', 3, '2023-08-14', 'Needs Repair'),
(9, 'Adjustable Incline / Decline Utility Bench', 8, '2023-09-01', 'Working'),
(10, 'Lat Pulldown & Seated Cable Row Combo', 2, '2023-10-10', 'Working');
