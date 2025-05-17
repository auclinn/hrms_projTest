CREATE DATABASE hrms_db;
use hrms_db;
----------------------
-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'hr', 'manager', 'employee') NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Employees table
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    dob DATE NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(20) NOT NULL,
    department VARCHAR(50) NOT NULL,
    position VARCHAR(50) NOT NULL,
    hire_date DATE NOT NULL,
    profile_image VARCHAR(255) NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Attendance table
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    time_in TIME NOT NULL,
    time_out TIME NULL,
    status ENUM('present', 'absent', 'late', 'on_leave') NOT NULL,
    notes TEXT NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Leave requests table
CREATE TABLE leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    type ENUM('vacation', 'sick', 'personal', 'other') NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_by INT NULL,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Audit logs table
CREATE TABLE auditlogs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (username) REFERENCES users(username) ON DELETE CASCADE
);

-- attendance correction table
CREATE TABLE attendance_corrections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attendance_id INT NOT NULL,
    employee_id INT NOT NULL,
    correction_type ENUM('time_in', 'time_out') NOT NULL,
    corrected_time TIME NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by INT DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attendance_id) REFERENCES attendance(id),
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
);

-- eval table
CREATE TABLE evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    evaluator_id INT NOT NULL,
    scheduled_date DATE NOT NULL,
    period ENUM('quarterly', 'bi-annually', 'annually') NOT NULL,
    evaluation_type ENUM('self', 'supervisor') NOT NULL,
    quality_of_work INT,
    productivity INT,
    attendance INT,
    teamwork INT,
    total_score DECIMAL(5,2),
    comments TEXT,
    status ENUM('pending', 'submitted', 'approved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (evaluator_id) REFERENCES users(id)
);


---------------------------------------------------
---------------------------------------------------
-- !! wait !! do not run these yetttttt, make hashed passwords muna


-- base users for testing
-- admin pw: admin123
-- hr pw: hr123
-- employee pw: emp2
-- manager pw: manager

--for the hashed passwords, punta muna sa gen_hash.php then copy paste to the corresponding user↓
--when done, insert these into the database


-- Insert into users table (with hashed passwords)
INSERT INTO users (id, username, password, role, email, created_at, last_login) VALUES
(4, 'admin', '$2y$10$A5WbBsWhgDlBocvFShOJtugC.f2l8j0TYUGQl3PQXtHFpV9g61.b6', 'admin', 'admin@example.com', '2025-05-09 01:49:02', '2025-05-16 03:51:37'),
(5, 'hr1', '$2y$10$u0eSzBtYCZXnS9iSslc4U..bbcoN.TGcEkZQIC9sW5A5pnNxAEYp2', 'hr', 'hr1@example.com', '2025-05-09 17:47:43', '2025-05-16 02:39:16'),
(7, 'emp2', '$2y$10$GEVvyHcEWRdHIBumsmWw5ej9r8DMi.qqOuDsbMylN2XNCYCnbuoBy', 'employee', 'emp2@example.com', '2025-05-09 18:07:42', '2025-05-16 03:46:24'),
(9, 'manager', '$2y$10$jp88JV2y4KXuN8oMyUaQvuQHh9r9P86wUGS5LAPZNar9ywzN3jNrq', 'manager', 'manager@mail.com', '2025-05-15 16:57:21', '2025-05-16 02:58:55'),
(38, 'bern', '$2y$10$SOo2SgXy4gkyMHkjU5U.6.0Wa.9VOkj5Uwlv/DsMNHj2hgudJJnhm', 'employee', 'badet.awatin@gmail.com', '2025-05-16 14:44:26', NULL);

-- Insert into employees table
INSERT INTO employees (id, user_id, first_name, last_name, gender, dob, address, phone, department, position, hire_date, profile_image) VALUES
(1, 7, 'Melvin', 'C', 'male', '1992-05-09', 'LIPA', '0998988', 'IT', 'Tech Support', '2025-05-09', NULL),
(2, 4, 'Bernadette', 'A.', 'female', '1990-01-21', 'Lipa', '0999999998', 'Executive', 'Administrative Officer', '2020-03-04', 'profile_2.jpg'),
(3, 5, 'Angelika', 'A', 'female', '1997-11-20', 'rosario', '0987654321', 'Human Resource', 'HR Auditor', '2022-08-08', NULL),
(5, 9, 'Jake', 'L', 'male', '1984-06-01', 'lipa', '09999999', 'IT', 'IT manager', '2012-06-15', NULL),
(21, 38, 'bern', 'bern', 'male', '2025-05-16', 'lipa', '09999', 'IT', 'Intern', '2025-05-16', 'profile_21.jpg');

-- Insert into attendance table //generated sample data of attendance
INSERT INTO attendance (id, employee_id, date, time_in, time_out, status, notes) VALUES
(1, 1, '2025-05-09', '18:09:17', '18:09:46', 'late', NULL),
(2, 2, '2025-05-09', '00:55:06', '00:56:31', 'late', NULL),
(3, 3, '2025-05-09', '01:38:33', '01:38:36', 'late', NULL),
(4, 1, '2025-05-10', '08:22:27', NULL, 'present', NULL),
(5, 3, '2025-05-10', '08:22:48', NULL, 'present', NULL),
(6, 2, '2025-05-10', '08:24:24', NULL, 'present', NULL),
(7, 1, '2025-05-15', '18:04:01', '18:13:09', 'late', NULL),
(10, 1, '2025-05-16', '06:37:59', '06:39:07', 'present', NULL),
(12, 3, '2025-05-16', '07:55:06', '09:55:25', 'present', NULL),
(15, 21, '2025-05-16', '08:19:00', NULL, 'late', NULL),
(16, 1, '2025-03-10', '08:15:00', '17:05:00', 'present', NULL),
(17, 1, '2025-03-11', '08:20:00', '17:10:00', 'present', NULL),
(18, 1, '2025-03-12', '08:25:00', '17:15:00', 'late', 'Traffic delay'),
(19, 1, '2025-03-13', '08:10:00', '17:00:00', 'present', NULL),
(20, 1, '2025-03-14', '08:05:00', '16:55:00', 'present', NULL),
(21, 1, '2025-03-17', '08:30:00', '17:20:00', 'late', 'Car trouble'),
(22, 1, '2025-03-18', '08:15:00', '17:05:00', 'present', NULL),
(23, 1, '2025-03-19', '08:20:00', '17:10:00', 'present', NULL),
(24, 1, '2025-03-20', '08:10:00', '17:00:00', 'present', NULL),
(25, 1, '2025-03-21', '08:05:00', '16:55:00', 'present', NULL),
(26, 1, '2025-03-24', '08:15:00', '17:05:00', 'present', NULL),
(27, 1, '2025-03-25', '08:20:00', '17:10:00', 'present', NULL),
(28, 1, '2025-03-26', '08:25:00', '17:15:00', 'late', 'Overslept'),
(29, 1, '2025-03-27', '08:10:00', '17:00:00', 'present', NULL),
(30, 1, '2025-03-28', '08:05:00', '16:55:00', 'present', NULL),
(31, 1, '2025-03-31', '08:15:00', '17:05:00', 'present', NULL),
(32, 1, '2025-04-01', '08:20:00', '17:10:00', 'present', NULL),
(33, 1, '2025-04-02', '08:25:00', '17:15:00', 'late', 'Public transport delay'),
(34, 1, '2025-04-03', '08:10:00', '17:00:00', 'present', NULL),
(35, 1, '2025-04-04', '08:05:00', '16:55:00', 'present', NULL),
(36, 1, '2025-04-07', '08:15:00', '17:05:00', 'present', NULL),
(37, 1, '2025-04-08', '08:20:00', '17:10:00', 'present', NULL),
(38, 1, '2025-04-09', '08:25:00', '17:15:00', 'late', 'Family emergency'),
(39, 1, '2025-04-10', '08:10:00', '17:00:00', 'present', NULL),
(40, 1, '2025-04-11', '08:05:00', '16:55:00', 'present', NULL),
(41, 1, '2025-04-14', '08:15:00', '17:05:00', 'present', NULL),
(42, 1, '2025-04-15', '08:20:00', '17:10:00', 'present', NULL),
(43, 1, '2025-04-16', '08:25:00', '17:15:00', 'late', 'Doctor appointment'),
(44, 1, '2025-04-17', '08:10:00', '17:00:00', 'present', NULL),
(45, 1, '2025-04-18', '08:05:00', '16:55:00', 'present', NULL),
(46, 1, '2025-04-21', '08:15:00', '17:05:00', 'present', NULL),
(47, 1, '2025-04-22', '08:20:00', '17:10:00', 'present', NULL),
(48, 1, '2025-04-23', '08:25:00', '17:15:00', 'late', 'Traffic accident'),
(49, 1, '2025-04-24', '08:10:00', '17:00:00', 'present', NULL),
(50, 1, '2025-04-25', '08:05:00', '16:55:00', 'present', NULL),
(51, 1, '2025-04-28', '08:15:00', '17:05:00', 'present', NULL),
(52, 1, '2025-04-29', '08:20:00', '17:10:00', 'present', NULL),
(53, 21, '2025-05-17', '15:44:06', NULL, 'late', NULL);

-- Insert into attendance_corrections table
INSERT INTO attendance_corrections (id, attendance_id, employee_id, correction_type, corrected_time, reason, status, reviewed_by, reviewed_at, created_at) VALUES
(1, 15, 21, 'time_in', '08:19:00', 'had no wifi', 'approved', 9, '2025-05-16 17:20:13', '2025-05-16 17:19:47'),
(2, 53, 21, 'time_in', '08:00:00', '', 'pending', NULL, NULL, '2025-05-17 15:44:48');

-- Insert into evaluations table
INSERT INTO evaluations (id, employee_id, evaluator_id, scheduled_date, period, evaluation_type, quality_of_work, productivity, attendance, teamwork, total_score, comments, status, created_at) VALUES
(1, 21, 38, '2025-05-17', 'quarterly', 'self', 5, 2, 4, 2, 3.30, 'too quiet', 'submitted', '2025-05-16 21:54:18'),
(2, 21, 9, '2025-05-17', 'quarterly', 'supervisor', 3, 3, 4, 4, 3.40, '', 'submitted', '2025-05-16 21:54:18'),
(3, 21, 38, '2025-05-19', 'quarterly', 'self', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-05-17 07:29:38'),
(4, 21, 9, '2025-05-19', 'quarterly', 'supervisor', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-05-17 07:29:38');

-- Insert into leave_requests table
INSERT INTO leave_requests (id, employee_id, start_date, end_date, type, reason, status, created_at, processed_by, processed_at) VALUES
(1, 3, '2025-05-21', '2025-05-23', 'vacation', 'vacay', 'approved', '2025-05-10 08:23:50', NULL, NULL),
(2, 1, '2025-05-17', '2025-05-18', 'personal', 'family visit', 'approved', '2025-05-15 18:49:54', NULL, NULL),
(3, 3, '2025-05-26', '2025-05-28', 'personal', 'travel', 'pending', '2025-05-16 17:39:04', NULL, NULL);