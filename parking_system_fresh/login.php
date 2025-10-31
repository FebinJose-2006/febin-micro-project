<?php 
require_once __DIR__ . '/config/init.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    redirect(isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? '/admin/index.php' : '/my_bookings.php');
}

$error = '';
$email = ''; // Preserve email on failed login attempt

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Email and password are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        try {
          $stmt = $pdo->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && md5($password) === $user['password']) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['role'] = $user['role'];
    redirect($user['role'] === 'admin' ? '/admin/index.php' : '/my_bookings.php');
} else {
    $error = "Invalid email or password.";
}


            // 2️⃣ Try login as normal user (bcrypt/password_hash)
            $stmt = $pdo->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                set_flash_message('success', 'Welcome back, ' . e($user['name']) . '!');
                redirect($user['role'] === 'admin' ? '/admin/index.php' : '/my_bookings.php');
                exit;
            }

            $error = "Invalid email or password.";

        } catch (PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            $error = "An error occurred. Please try again later.";
        }
    }
}
?>
