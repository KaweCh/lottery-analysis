<?php
/**
 * settings.php
 * 
 * User settings page for the Thai Lottery Analysis system
 */

// Set page title and active menu
$pageTitle = 'การตั้งค่า';
$activePage = 'settings';

// Include functions and models
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/config.php';

// Check user authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header('Location: login.php');
    exit;
}

// Handle form submissions
$successMessage = '';
$errorMessage = '';

// Profile Update
if (isset($_POST['update_profile'])) {
    $username = cleanInput($_POST['username'], 'string');
    $email = cleanInput($_POST['email'], 'email');
    
    if (empty($username) || empty($email)) {
        $errorMessage = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } else {
        // Update user profile logic
        try {
            $sql = "UPDATE users SET username = ?, email = ? WHERE user_id = ?";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("ssi", $username, $email, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                // Update session username
                $_SESSION['username'] = $username;
                $successMessage = 'อัปเดตข้อมูลส่วนตัวสำเร็จ';
            } else {
                $errorMessage = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล';
            }
        } catch (Exception $e) {
            $errorMessage = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
    }
}

// Password Change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $errorMessage = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif ($new_password !== $confirm_password) {
        $errorMessage = 'รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน';
    } elseif (strlen($new_password) < 8) {
        $errorMessage = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร';
    } else {
        // Verify current password
        $sql = "SELECT password FROM users WHERE user_id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user && password_verify($current_password, $user['password'])) {
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $sql = "UPDATE users SET password = ? WHERE user_id = ?";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $successMessage = 'เปลี่ยนรหัสผ่านสำเร็จ';
            } else {
                $errorMessage = 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน';
            }
        } else {
            $errorMessage = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
        }
    }
}

// Notification Settings
if (isset($_POST['update_notifications'])) {
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
    
    try {
        $sql = "UPDATE user_preferences SET email_notifications = ?, sms_notifications = ? WHERE user_id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("iii", $email_notifications, $sms_notifications, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $successMessage = 'อัปเดตการแจ้งเตือนสำเร็จ';
        } else {
            $errorMessage = 'เกิดข้อผิดพลาดในการอัปเดตการแจ้งเตือน';
        }
    } catch (Exception $e) {
        $errorMessage = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }
}

// Fetch current user data
$sql = "SELECT u.username, u.email, up.email_notifications, up.sms_notifications 
        FROM users u
        LEFT JOIN user_preferences up ON u.user_id = up.user_id
        WHERE u.user_id = ?";
$stmt = $connection->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

// Include header
include __DIR__ . '/../templates/header.php';
?>

<div class="row">
    <div class="col-lg-8 offset-lg-2">
        <!-- Error/Success Messages -->
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $errorMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $successMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Profile Settings Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-user-edit me-2"></i> ข้อมูลส่วนตัว
                </h6>
            </div>
            <div class="card-body">
                <form method="post" action="settings.php">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label">ชื่อผู้ใช้</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($userData['username']); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="email" class="form-label">อีเมล</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> บันทึกข้อมูลส่วนตัว
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Password Change Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-lock me-2"></i> เปลี่ยนรหัสผ่าน
                </h6>
            </div>
            <div class="card-body">
                <form method="post" action="settings.php">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="current_password" class="form-label">รหัสผ่านปัจจุบัน</label>
                            <input type="password" class="form-control" id="current_password" 
                                   name="current_password" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                            <input type="password" class="form-control" id="new_password" 
                                   name="new_password" required minlength="8">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password" required minlength="8">
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key me-1"></i> เปลี่ยนรหัสผ่าน
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Notification Settings Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-bell me-2"></i> การแจ้งเตือน
                </h6>
            </div>
            <div class="card-body">
                <form method="post" action="settings.php">
                    <input type="hidden" name="update_notifications" value="1">
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="email_notifications" 
                                       name="email_notifications" 
                                       <?php echo $userData['email_notifications'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="email_notifications">
                                    รับการแจ้งเตือนทางอีเมล
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="sms_notifications" 
                                       name="sms_notifications" 
                                       <?php echo $userData['sms_notifications'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="sms_notifications">
                                    รับการแจ้งเตือนทาง SMS
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> บันทึกการแจ้งเตือน
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/../templates/footer.php';
?>