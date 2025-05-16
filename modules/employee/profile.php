<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

requireLogin();
$employeeId = getEmployeeId();

// profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitize($_POST['first_name']);
    $lastName = sanitize($_POST['last_name']);
    $gender = sanitize($_POST['gender']);
    $dob = sanitize($_POST['dob']);
    $address = sanitize($_POST['address']);
    $phone = sanitize($_POST['phone']);
    
    try {
        $stmt = $pdo->prepare("UPDATE employees SET 
            first_name = ?, last_name = ?, gender = ?, dob = ?, address = ?, phone = ?
            WHERE id = ?");
        $stmt->execute([$firstName, $lastName, $gender, $dob, $address, $phone, $employeeId]);
        
        $success = "Profile updated successfully!";
        logAction($pdo, 'profile_update', 'Updated profile information');
    } catch (PDOException $e) {
        $error = "Error updating profile: " . $e->getMessage();
        logAction($pdo, 'profile_update_failed', $e->getMessage());
    }
}

  // Handle image upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['profile_pic']['tmp_name'];
        $mime = mime_content_type($tmpName);
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($mime, $allowed)) {
            // Always save as jpg for consistency
            $dest = $imgDir . "profile_$employeeId.jpg";
            if ($mime === 'image/png') {
                $image = imagecreatefrompng($tmpName);
                imagejpeg($image, $dest, 90);
                imagedestroy($image);
            } elseif ($mime === 'image/gif') {
                $image = imagecreatefromgif($tmpName);
                imagejpeg($image, $dest, 90);
                imagedestroy($image);
            } else {
                move_uploaded_file($tmpName, $dest);
            }
            $imgExists = true;
            $success = ($success ?? '') . " Profile picture updated!";
        } else {
            $error = ($error ?? '') . " Invalid image type. Only JPG, PNG, GIF allowed.";
        }
    }

// get employee detailss
$employee = getEmployeeDetails($employeeId);
?>
<?php include '../../includes/header.php'; ?>
<div class="profile-container">
    <h2>My Profile</h2>
    
    <?php if (isset($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="profile.php">
        
        <div>
            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name" value="<?php echo $employee['first_name']; ?>" required>
        </div>
        <div>
            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" value="<?php echo $employee['last_name']; ?>" required>
        </div>
        <div>
            <label for="gender">Gender:</label>
            <select id="gender" name="gender" required>
                <option value="male" <?php echo $employee['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                <option value="female" <?php echo $employee['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                <option value="other" <?php echo $employee['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>
        <div>
            <label for="dob">Date of Birth:</label>
            <input type="date" id="dob" name="dob" value="<?php echo $employee['dob']; ?>" required>
        </div>
        <div>
            <label for="address">Address:</label>
            <textarea id="address" name="address" required><?php echo $employee['address']; ?></textarea>
        </div>
        <div>
            <label for="phone">Phone:</label>
            <input type="tel" id="phone" name="phone" value="<?php echo $employee['phone']; ?>" required>
        </div>
        <div>
            <label>Department: <?php echo $employee['department']; ?> </label>
        </div>
        <div>
            <label>Position: <?php echo $employee['position']; ?></label>
        </div>
        <div>
            <label>Hire Date: <?php echo $employee['hire_date']; ?> </label>
        </div>
        <button type="submit">Update Profile</button>
    </form>
</div>
    
<?php include '../../includes/footer.php'; ?>