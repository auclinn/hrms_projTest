CREATE DATABASE hrms_db;

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

---------------------------------------------------
---------------------------------------------------
-- !! do not run these yetttttt, make hashed passwords muna


-- base users for testing
-- admin pw: admin123
-- hr pw: hr123
-- emp pw: emp2

--for the hashed passwords, punta muna sa gen_hash.php then copy paste ↓


insert into users (username, password, role, email) 
values ('admin', 'paste dito ung password from gen_hash.php', 'admin', 'admin@example.com'),
        ('hr1', 'paste dito ung password from gen_hash.php', 'hr', 'hr1@example.com'),
        ('emp2', 'paste dito ung password from gen_hash.php', 'employee', 'emp2@example.com');
        ('manager', 'paste dito ung password from gen_hash.php', 'manager', 'manager@mail.com');

insert into employees(user_id, first_name, last_name, gender, dob, address, phone, department, position, hire_date)
values (1, 'Ad', 'Min', 'female', '1990-01-21', 'padre garcia', '0999999998', 'executive', 'administrative officer', '2020-03-04'),
       (2, 'H', 'R', 'female', '1997-11-20', 'rosario', '0987654321', 'human resource', 'hr manager', '2022-08-08'),
       (3, 'Emp', 'Loyee', 'male', '1992-05-09', 'lipa', '09999999999', 'IT', 'Tech Support', '2025-05-09');
       (4, 'Mana', 'Ger', 'male', '1984-06-01', 'lipa', '09999999999', 'IT', 'IT manager', '2025-05-09');