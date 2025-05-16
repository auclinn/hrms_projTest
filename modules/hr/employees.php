<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/send_email.php';

requireRole(['admin', 'hr', 'manager']);

// Handle employee creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $username   = sanitize($_POST['username']);
    $password   = password_hash(sanitize($_POST['password']), PASSWORD_DEFAULT);
    $role       = sanitize($_POST['role']);
    $email      = sanitize($_POST['email']);
    $firstName  = sanitize($_POST['first_name']);
    $lastName   = sanitize($_POST['last_name']);
    $gender     = sanitize($_POST['gender']);
    $dob        = sanitize($_POST['dob']);
    $address    = sanitize($_POST['address']);
    $phone      = sanitize($_POST['phone']);
    $department = sanitize($_POST['department']);
    $position   = sanitize($_POST['position']);
    $hireDate   = sanitize($_POST['hire_date']);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $password, $role, $email]);
        $userId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO employees 
            (user_id, first_name, last_name, gender, dob, address, phone, department, position, hire_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId, $firstName, $lastName, $gender, $dob, $address, $phone,
            $department, $position, $hireDate
        ]);

        $pdo->commit();

        $rawPassword = sanitize($_POST['password']);
        if (sendAccountEmail($email, $username, $rawPassword)) {
            $_SESSION['success'] = "Employee added successfully. Email sent.";
        } else {
            $_SESSION['warning'] = "Employee added but email failed to send.";
        }

        $success = "Employee added successfully!";
        logAction($pdo, 'add_employee', 'Added employee: ' . $firstName . ' ' . $lastName);
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error adding employee: " . $e->getMessage();
        logAction($pdo, 'add_employee_error', 'Failed to add a new employee. '. $e->getMessage());
    }
}

// Handle employee edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_employee'])) {
    $employeeId = intval($_POST['employee_id']);
    $userId     = intval($_POST['user_id']);
    $username   = sanitize($_POST['username']);
    $email      = sanitize($_POST['email']);
    $role       = sanitize($_POST['role']);
    $firstName  = sanitize($_POST['first_name']);
    $lastName   = sanitize($_POST['last_name']);
    $gender     = sanitize($_POST['gender']);
    $dob        = sanitize($_POST['dob']);
    $address    = sanitize($_POST['address']);
    $phone      = sanitize($_POST['phone']);
    $department = sanitize($_POST['department']);
    $position   = sanitize($_POST['position']);
    $hireDate   = sanitize($_POST['hire_date']);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
        $stmt->execute([$username, $email, $role, $userId]);

        $stmt = $pdo->prepare("UPDATE employees SET 
            first_name = ?, last_name = ?, gender = ?, dob = ?, address = ?, phone = ?, 
            department = ?, position = ?, hire_date = ? 
            WHERE id = ?");
        $stmt->execute([
            $firstName, $lastName, $gender, $dob, $address, $phone,
            $department, $position, $hireDate, $employeeId
        ]);

        $pdo->commit();
        $edit_success = "Employee updated successfully!";
        logAction($pdo, 'edit_employee', 'Updated employee: ' . $firstName . ' ' . $lastName . ' (ID: ' . $employeeId . ')');
    } catch (PDOException $e) {
        $pdo->rollBack();
        $edit_error = "Error updating employee: " . $e->getMessage();
        logAction($pdo, 'edit_employee_error', 'Failed to update employee (ID: ' . $employeeId . '). ' . $e->getMessage());
    }
}

// Handle employee delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_employee'])) {
    $employeeId = intval($_POST['employee_id']);
    $userId     = intval($_POST['user_id']);
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->execute([$employeeId]);
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $pdo->commit();
        $delete_success = "Employee deleted successfully!";
        logAction($pdo, 'delete_employee', 'Deleted employee ID: ' . $employeeId);
    } catch (PDOException $e) {
        $pdo->rollBack();
        $delete_error = "Error deleting employee: " . $e->getMessage();
        logAction($pdo, 'delete_employee_error', 'Failed to delete employee (ID: ' . $employeeId . '). ' . $e->getMessage());
    }
}

// Get all employees
$employees = getAllEmployees();
?>
<?php include '../../includes/header.php'; ?>
<div class="employee-mgt-container">
    <div class="add-emp-container">
        <h2>Employee Management</h2>
        <button id="openAddEmployeeModal" type="button">Add New Employee</button>
        <div id="addEmployeeModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; overflow:auto; background:rgba(0,0,0,0.4);">
            <div class="modal-content" style="background:#fff; margin:5% auto; padding:20px; border-radius:5px; width:90%; max-width:600px; position:relative;">
                <span id="closeAddEmployeeModal" style="position:absolute; top:10px; right:20px; font-size:28px; font-weight:bold; cursor:pointer;">&times;</span>
                <h3>Add New Employee</h3>
                <form method="POST" action="employees.php">
                    <div><label for="username">Username:</label><input type="text" id="username" name="username" required></div>
                    <div><label for="password">Password:</label><input type="password" id="password" name="password" required></div>
                    <div>
                        <label for="role">Role:</label>
                        <select id="role" name="role" required>
                            <option value="employee">Employee</option>
                            <option value="hr">HR</option>
                            <option value="manager">Manager</option>
                            <?php if (hasRole('admin')): ?><option value="admin">Admin</option><?php endif; ?>
                        </select>
                    </div>
                    <div><label for="email">Email:</label><input type="email" id="email" name="email" required></div>
                    <div><label for="first_name">First Name:</label><input type="text" id="first_name" name="first_name" required></div>
                    <div><label for="last_name">Last Name:</label><input type="text" id="last_name" name="last_name" required></div>
                    <div>
                        <label for="gender">Gender:</label>
                        <select id="gender" name="gender" required>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div><label for="dob">Date of Birth:</label><input type="date" id="dob" name="dob" required></div>
                    <div><label for="address">Address:</label><textarea id="address" name="address" required></textarea></div>
                    <div><label for="phone">Phone:</label><input type="tel" id="phone" name="phone" required></div>
                    <div><label for="department">Department:</label><input type="text" id="department" name="department" required></div>
                    <div><label for="position">Position:</label><input type="text" id="position" name="position" required></div>
                    <div><label for="hire_date">Hire Date:</label><input type="date" id="hire_date" name="hire_date" required></div>
                    <button type="submit" name="add_employee">Add Employee</button>
                </form>
            </div>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="success"><?php echo $success; ?><span class="close-message" onclick="this.parentElement.style.display='none';">&times;</span></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?><span class="close-message" onclick="this.parentElement.style.display='none';">&times;</span></div>
    <?php endif; ?>
    <?php if (isset($edit_success)): ?>
        <div class="success"><?php echo $edit_success; ?><span class="close-message" onclick="this.parentElement.style.display='none';">&times;</span></div>
    <?php endif; ?>
    <?php if (isset($edit_error)): ?>
        <div class="error"><?php echo $edit_error; ?><span class="close-message" onclick="this.parentElement.style.display='none';">&times;</span></div>
    <?php endif; ?>
    <?php if (isset($delete_success)): ?>
        <div class="success"><?php echo $delete_success; ?><span class="close-message" onclick="this.parentElement.style.display='none';">&times;</span></div>
    <?php endif; ?>
    <?php if (isset($delete_error)): ?>
        <div class="error"><?php echo $delete_error; ?><span class="close-message" onclick="this.parentElement.style.display='none';">&times;</span></div>
    <?php endif; ?>

    <script>
    document.getElementById('openAddEmployeeModal').onclick = function() {
        document.getElementById('addEmployeeModal').style.display = 'block';
    };
    document.getElementById('closeAddEmployeeModal').onclick = function() {
        document.getElementById('addEmployeeModal').style.display = 'none';
    };
    window.onclick = function(event) {
        var modal = document.getElementById('addEmployeeModal');
        if (event.target == modal) modal.style.display = 'none';
    };
    </script>
    <hr>
    <?php
    // Pagination logic
    $perPage = 10;
    $totalEmployees = count($employees);
    $totalPages = ceil($totalEmployees / $perPage);
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
    if ($page < 1) $page = 1;
    if ($page > $totalPages) $page = $totalPages;
    $start = ($page - 1) * $perPage;
    $employeesPage = array_slice($employees, $start, $perPage);
    ?>


    
    <?php
    // Search and filter logic
    $filteredEmployees = $employees;

    // Search
    if (!empty($_GET['search'])) {
        $search = strtolower(trim($_GET['search']));
        $filteredEmployees = array_filter($filteredEmployees, function($emp) use ($search, $pdo) {
            // Get username from users table
            $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$emp['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $username = $user['username'] ?? '';
            return (
                strpos(strtolower($emp['first_name'] . ' ' . $emp['last_name']), $search) !== false ||
                strpos(strtolower($username), $search) !== false ||
                strpos(strtolower($emp['email']), $search) !== false ||
                strpos(strtolower($emp['department']), $search) !== false ||
                strpos(strtolower($emp['position']), $search) !== false
            );
        });
    }

    // Date range filter
    if (!empty($_GET['from_date']) || !empty($_GET['to_date'])) {
        $from = !empty($_GET['from_date']) ? $_GET['from_date'] : null;
        $to = !empty($_GET['to_date']) ? $_GET['to_date'] : null;
        $filteredEmployees = array_filter($filteredEmployees, function($emp) use ($from, $to) {
            $hireDate = $emp['hire_date'];
            if ($from && $to) {
                return $hireDate >= $from && $hireDate <= $to;
            } elseif ($from) {
                return $hireDate >= $from;
            } elseif ($to) {
                return $hireDate <= $to;
            }
            return true;
        });
    }

    // Update pagination based on filtered results
    $totalEmployees = count($filteredEmployees);
    $totalPages = ceil($totalEmployees / $perPage);
    if ($page > $totalPages) $page = $totalPages > 0 ? $totalPages : 1;
    $start = ($page - 1) * $perPage;
    $employeesPage = array_slice(array_values($filteredEmployees), $start, $perPage);
    ?>
    <div class="employee-list">
        
            <!-- Search and Filter Form -->
        <div class="search-filter">
            <h3>Employee List</h3>
            <form method="GET" action="employees.php">
            <input type="text" name="search" placeholder="Search by name, username, email, department..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <label for="from_date">Hire Date:</label>
            <input type="date" name="from_date" id="from_date" value="<?php echo isset($_GET['from_date']) ? htmlspecialchars($_GET['from_date']) : ''; ?>" style="padding:6px;">
            <span>to</span>
            <input type="date" name="to_date" id="to_date" value="<?php echo isset($_GET['to_date']) ? htmlspecialchars($_GET['to_date']) : ''; ?>" style="padding:6px;">
            <button type="submit" style="padding:6px 14px;">
                Search
            </button>
            <?php if (!empty($_GET['search']) || !empty($_GET['from_date']) || !empty($_GET['to_date'])): ?>
                <a href="employees.php" style="margin-left:10px; color:#d9534f;">Reset</a>
            <?php endif; ?>
        </form>
        </div>
        <div style="max-height:50vh; overflow-y:auto;">
            <table style="border-collapse:collapse; width:100%;">
                <thead style="position:sticky; top:0; background:#fff; z-index:1;">
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Hire Date</th>
                        <?php if (hasRole(['admin', 'hr'])): ?><th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($employeesPage as $employee): ?>
                    <tr>
                        <td><?php echo $employee['id']; ?></td>
                        <td>
                            <?php
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
        </div>
        <div class="pagination" style="margin:15px 0;">
            <?php if ($totalPages > 1): ?>
                <?php if ($page > 1): ?>
                    <a href="?page=1">&laquo; First</a>
                    <a href="?page=<?php echo $page-1; ?>">&lt; Prev</a>
                <?php endif; ?>
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span style="font-weight:bold;"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page+1; ?>">Next &gt;</a>
                    <a href="?page=<?php echo $totalPages; ?>">Last &raquo;</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div id="editEmployeeModal" class="modal" style="display:none; position:fixed; z-index:1001; left:0; top:0; width:100%; height:100%; overflow:auto; background:rgba(0,0,0,0.4);">
            <div class="modal-content" style="background:#fff; margin:5% auto; padding:20px; border-radius:5px; width:90%; max-width:600px; position:relative;">
                <span id="closeEditEmployeeModal" style="position:absolute; top:10px; right:20px; font-size:28px; font-weight:bold; cursor:pointer;">&times;</span>
                <h3>Edit Employee</h3>
                <form method="POST" action="employees.php" id="editEmployeeForm">
                    <input type="hidden" name="edit_employee" value="1">
                    <input type="hidden" name="employee_id" id="edit_employee_id">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div><label for="edit_username">Username:</label><input type="text" id="edit_username" name="username" required></div>
                    <div><label for="edit_email">Email:</label><input type="email" id="edit_email" name="email" required></div>
                    <div>
                        <label for="edit_role">Role:</label>
                        <select id="edit_role" name="role" required>
                            <option value="employee">Employee</option>
                            <option value="hr">HR</option>
                            <option value="manager">Manager</option>
                            <?php if (hasRole('admin')): ?><option value="admin">Admin</option><?php endif; ?>
                        </select>
                    </div>
                    <div><label for="edit_first_name">First Name:</label><input type="text" id="edit_first_name" name="first_name" required></div>
                    <div><label for="edit_last_name">Last Name:</label><input type="text" id="edit_last_name" name="last_name" required></div>
                    <div>
                        <label for="edit_gender">Gender:</label>
                        <select id="edit_gender" name="gender" required>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div><label for="edit_dob">Date of Birth:</label><input type="date" id="edit_dob" name="dob" required></div>
                    <div><label for="edit_address">Address:</label><textarea id="edit_address" name="address" required></textarea></div>
                    <div><label for="edit_phone">Phone:</label><input type="tel" id="edit_phone" name="phone" required></div>
                    <div><label for="edit_department">Department:</label><input type="text" id="edit_department" name="department" required></div>
                    <div><label for="edit_position">Position:</label><input type="text" id="edit_position" name="position" required></div>
                    <div><label for="edit_hire_date">Hire Date:</label><input type="date" id="edit_hire_date" name="hire_date" required></div>
                    <button type="submit">Save Changes</button>
                </form>
            </div>
        </div>
        <div id="deleteEmployeeModal" class="modal" style="display:none; position:fixed; z-index:1002; left:0; top:0; width:100%; height:100%; overflow:auto; background:rgba(0,0,0,0.4);">
            <div class="modal-content" style="background:#fff; margin:10% auto; padding:20px; border-radius:5px; width:90%; max-width:400px; position:relative;">
                <span id="closeDeleteEmployeeModal" style="position:absolute; top:10px; right:20px; font-size:28px; font-weight:bold; cursor:pointer;">&times;</span>
                <h3>Delete Employee</h3>
                <p>Are you sure you want to delete <strong id="deleteEmployeeName"></strong>? This action cannot be undone.</p>
                <form method="POST" action="employees.php">
                    <input type="hidden" name="delete_employee" value="1">
                    <input type="hidden" name="employee_id" id="delete_employee_id">
                    <input type="hidden" name="user_id" id="delete_user_id">
                    <button type="submit" style="background:#d9534f; color:#fff; border:none; padding:8px 16px; border-radius:3px;">Delete</button>
                    <button type="button" onclick="document.getElementById('deleteEmployeeModal').style.display='none';" style="margin-left:10px;">Cancel</button>
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
            if (event.target == modal) modal.style.display = 'none';
        });

        // Delete modal logic
        document.querySelectorAll('.employee-list tbody tr').forEach(function(row) {
            <?php if (hasRole(['admin', 'hr'])): ?>
            var cells = row.getElementsByTagName('td');
            if (cells.length > 0) {
                var editCell = cells[cells.length - 1];
                var editBtn = editCell.querySelector('.editEmployeeBtn');
                if (editBtn) {
                    var dataEmployee = editBtn.getAttribute('data-employee');
                    var deleteBtn = document.createElement('button');
                    deleteBtn.className = 'deleteEmployeeBtn';
                    deleteBtn.setAttribute('data-employee', dataEmployee);
                    deleteBtn.title = 'Delete Employee';
                    deleteBtn.style = 'background:none;border:none;cursor:pointer;font-size:18px;color:#d9534f;margin-left:8px;';
                    deleteBtn.innerHTML = '🗑️';
                    editCell.appendChild(deleteBtn);
                }
            }
            <?php endif; ?>
        });
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.deleteEmployeeBtn').forEach(function(btn) {
                btn.onclick = function() {
                    var data = JSON.parse(this.getAttribute('data-employee'));
                    document.getElementById('delete_employee_id').value = data.id;
                    document.getElementById('delete_user_id').value = data.user_id;
                    document.getElementById('deleteEmployeeName').textContent = data.first_name + ' ' + data.last_name;
                    document.getElementById('deleteEmployeeModal').style.display = 'block';
                };
            });
            var closeDeleteBtn = document.getElementById('closeDeleteEmployeeModal');
            if (closeDeleteBtn) {
                closeDeleteBtn.onclick = function() {
                    document.getElementById('deleteEmployeeModal').style.display = 'none';
                };
            }
            window.addEventListener('click', function(event) {
                var modal = document.getElementById('deleteEmployeeModal');
                if (event.target == modal) modal.style.display = 'none';
            });
        });
        </script>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
