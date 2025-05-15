<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

requireRole(['admin', 'hr', 'manager']);

// Handle employee creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $username = sanitize($_POST['username']);
    $password = password_hash(sanitize($_POST['password']), PASSWORD_DEFAULT);
    $role = sanitize($_POST['role']);
    $email = sanitize($_POST['email']);
    
    $firstName = sanitize($_POST['first_name']);
    $lastName = sanitize($_POST['last_name']);
    $gender = sanitize($_POST['gender']);
    $dob = sanitize($_POST['dob']);
    $address = sanitize($_POST['address']);
    $phone = sanitize($_POST['phone']);
    $department = sanitize($_POST['department']);
    $position = sanitize($_POST['position']);
    $hireDate = sanitize($_POST['hire_date']);
    
    try {
        $pdo->beginTransaction();
        
        // Create user
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $password, $role, $email]);
        $userId = $pdo->lastInsertId();
        
        // Create employee
        $stmt = $pdo->prepare("INSERT INTO employees 
            (user_id, first_name, last_name, gender, dob, address, phone, 
            department, position, hire_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId, $firstName, $lastName, $gender, $dob, $address, $phone,
            $department, $position, $hireDate
        ]);
        
        $pdo->commit();
        $success = "Employee added successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error adding employee: " . $e->getMessage();
    }
}

// Get all employees
$employees = getAllEmployees();
?>
<?php include '../../includes/header.php'; ?>
<div class="employee-mgt-container">
    <h2>Employee Management</h2>
    
    <?php if (isset($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- adding employee modal -->
    <button id="openAddEmployeeModal" type="button">Add New Employee</button>

    <div id="addEmployeeModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; overflow:auto; background:rgba(0,0,0,0.4);">
        <div class="modal-content" style="background:#fff; margin:5% auto; padding:20px; border-radius:5px; width:90%; max-width:600px; position:relative;">
            <span id="closeAddEmployeeModal" style="position:absolute; top:10px; right:20px; font-size:28px; font-weight:bold; cursor:pointer;">&times;</span>
            <h3>Add New Employee</h3>
            <form method="POST" action="employees.php">
                <div>
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div>
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div>
                    <label for="role">Role:</label>
                    <select id="role" name="role" required>
                        <option value="employee">Employee</option>
                        <option value="hr">HR</option>
                        <option value="manager">Manager</option>
                        <?php if (hasRole('admin')): ?>
                            <option value="admin">Admin</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div>
                    <label for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div>
                    <label for="last_name">Last Name:</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
                <div>
                    <label for="gender">Gender:</label>
                    <select id="gender" name="gender" required>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label for="dob">Date of Birth:</label>
                    <input type="date" id="dob" name="dob" required>
                </div>
                <div>
                    <label for="address">Address:</label>
                    <textarea id="address" name="address" required></textarea>
                </div>
                <div>
                    <label for="phone">Phone:</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                <div>
                    <label for="department">Department:</label>
                    <input type="text" id="department" name="department" required>
                </div>
                <div>
                    <label for="position">Position:</label>
                    <input type="text" id="position" name="position" required>
                </div>
                <div>
                    <label for="hire_date">Hire Date:</label>
                    <input type="date" id="hire_date" name="hire_date" required>
                </div>
                <button type="submit" name="add_employee">Add Employee</button>
            </form>
        </div>
    </div>

    <script>
    // Modal open/close logic
    document.getElementById('openAddEmployeeModal').onclick = function() {
        document.getElementById('addEmployeeModal').style.display = 'block';
    };
    document.getElementById('closeAddEmployeeModal').onclick = function() {
        document.getElementById('addEmployeeModal').style.display = 'none';
    };
    window.onclick = function(event) {
        var modal = document.getElementById('addEmployeeModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    };
    </script>
    <hr>
    <div class="employee-list">
        <h2>Employee List</h2>
        <table>
            <thead>
            <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Name</th>
            <th>Department</th>
            <th>Position</th>
            <th>Email</th>
            <th>Role</th>
            <th>Hire Date</th>
            <?php if (hasRole(['admin', 'hr'])): ?>
            <th>Edit</th>
            <?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($employees as $employee): ?>
            <tr>
            <td><?php echo $employee['id']; ?></td>
            <td>
                <?php
                // Fetch username from users table using user_id
                $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$employee['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                echo htmlspecialchars($user['username'] ?? '');
                ?>
            </td>
            <td><?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?></td>
            <td><?php echo $employee['department']; ?></td>
            <td><?php echo $employee['position']; ?></td>
            <td><?php echo $employee['email']; ?></td>
            <td><?php echo ucfirst($employee['role']); ?></td>
            <td><?php echo $employee['hire_date']; ?></td>
            <?php if (hasRole(['admin', 'hr'])): ?>
                <td>
                <button 
                class="editEmployeeBtn"
                data-employee='<?php
                    // Add username to the data-employee JSON
                    $employeeWithUsername = $employee;
                    $employeeWithUsername['username'] = $user['username'] ?? '';
                    echo json_encode($employeeWithUsername);
                ?>'
                title="Edit Employee" 
                style="background:none;border:none;cursor:pointer;font-size:18px;">
                📝
                </button>
                </td>
            <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Edit Employee Modal -->
        <div id="editEmployeeModal" class="modal" style="display:none; position:fixed; z-index:1001; left:0; top:0; width:100%; height:100%; overflow:auto; background:rgba(0,0,0,0.4);">
            <div class="modal-content" style="background:#fff; margin:5% auto; padding:20px; border-radius:5px; width:90%; max-width:600px; position:relative;">
            <span id="closeEditEmployeeModal" style="position:absolute; top:10px; right:20px; font-size:28px; font-weight:bold; cursor:pointer;">&times;</span>
            <h3>Edit Employee</h3>
            <?php if (isset($edit_success)): ?>
                <div class="success"><?php echo $edit_success; ?></div>
            <?php endif; ?>
            <?php if (isset($edit_error)): ?>
                <div class="error"><?php echo $edit_error; ?></div>
            <?php endif; ?>
            <form method="POST" action="employees.php" id="editEmployeeForm">
                <input type="hidden" name="edit_employee" value="1">
                <input type="hidden" name="employee_id" id="edit_employee_id">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div>
                <label for="edit_username">Username:</label>
                <input type="text" id="edit_username" name="username" required>
                </div>
                <div>
                <label for="edit_email">Email:</label>
                <input type="email" id="edit_email" name="email" required>
                </div>
                <div>
                <label for="edit_role">Role:</label>
                <select id="edit_role" name="role" required>
                    <option value="employee">Employee</option>
                    <option value="hr">HR</option>
                    <option value="manager">Manager</option>
                    <?php if (hasRole('admin')): ?>
                    <option value="admin">Admin</option>
                    <?php endif; ?>
                </select>
                </div>
                <div>
                <label for="edit_first_name">First Name:</label>
                <input type="text" id="edit_first_name" name="first_name" required>
                </div>
                <div>
                <label for="edit_last_name">Last Name:</label>
                <input type="text" id="edit_last_name" name="last_name" required>
                </div>
                <div>
                <label for="edit_gender">Gender:</label>
                <select id="edit_gender" name="gender" required>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
                </div>
                <div>
                <label for="edit_dob">Date of Birth:</label>
                <input type="date" id="edit_dob" name="dob" required>
                </div>
                <div>
                <label for="edit_address">Address:</label>
                <textarea id="edit_address" name="address" required></textarea>
                </div>
                <div>
                <label for="edit_phone">Phone:</label>
                <input type="tel" id="edit_phone" name="phone" required>
                </div>
                <div>
                <label for="edit_department">Department:</label>
                <input type="text" id="edit_department" name="department" required>
                </div>
                <div>
                <label for="edit_position">Position:</label>
                <input type="text" id="edit_position" name="position" required>
                </div>
                <div>
                <label for="edit_hire_date">Hire Date:</label>
                <input type="date" id="edit_hire_date" name="hire_date" required>
                </div>
                <button type="submit">Save Changes</button>
            </form>
            </div>
        </div>

        <script>
        // Edit modal logic
        document.querySelectorAll('.editEmployeeBtn').forEach(function(btn) {
            btn.onclick = function() {
            var data = JSON.parse(this.getAttribute('data-employee'));
            document.getElementById('edit_employee_id').value = data.id;
            document.getElementById('edit_user_id').value = data.user_id;
            document.getElementById('edit_username').value = data.username;
            document.getElementById('edit_email').value = data.email;
            document.getElementById('edit_role').value = data.role;
            document.getElementById('edit_first_name').value = data.first_name;
            document.getElementById('edit_last_name').value = data.last_name;
            document.getElementById('edit_gender').value = data.gender;
            document.getElementById('edit_dob').value = data.dob;
            document.getElementById('edit_address').value = data.address;
            document.getElementById('edit_phone').value = data.phone;
            document.getElementById('edit_department').value = data.department;
            document.getElementById('edit_position').value = data.position;
            document.getElementById('edit_hire_date').value = data.hire_date;
            document.getElementById('editEmployeeModal').style.display = 'block';
            };
        });
        document.getElementById('closeEditEmployeeModal').onclick = function() {
            document.getElementById('editEmployeeModal').style.display = 'none';
        };
        window.addEventListener('click', function(event) {
            var modal = document.getElementById('editEmployeeModal');
            if (event.target == modal) {
            modal.style.display = 'none';
            }
        });
        </script>
    </div>
<?php
// Handle employee edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_employee'])) {
    $employeeId = intval($_POST['employee_id']);
    $userId = intval($_POST['user_id']);
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $role = sanitize($_POST['role']);
    $firstName = sanitize($_POST['first_name']);
    $lastName = sanitize($_POST['last_name']);
    $gender = sanitize($_POST['gender']);
    $dob = sanitize($_POST['dob']);
    $address = sanitize($_POST['address']);
    $phone = sanitize($_POST['phone']);
    $department = sanitize($_POST['department']);
    $position = sanitize($_POST['position']);
    $hireDate = sanitize($_POST['hire_date']);

    try {
        $pdo->beginTransaction();

        // Update users table
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
        $stmt->execute([$username, $email, $role, $userId]);

        // Update employees table
        $stmt = $pdo->prepare("UPDATE employees SET 
            first_name = ?, last_name = ?, gender = ?, dob = ?, address = ?, phone = ?, 
            department = ?, position = ?, hire_date = ? 
            WHERE id = ?");
        $stmt->execute([
            $firstName, $lastName, $gender, $dob, $address, $phone,
            $department, $position, $hireDate, $employeeId
        ]);

        $pdo->commit();
        // Set indicator for success
        $edit_success = "Employee updated successfully!";
        // Refresh employee list after update
        $employees = getAllEmployees();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $edit_error = "Error updating employee: " . $e->getMessage();
    }
}
?>
</div>
    
<?php include '../../includes/footer.php'; ?>