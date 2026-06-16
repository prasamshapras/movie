<?php
require 'includes/config.php';

$err = '';
$success = '';
$remember_email = '';

if (isset($_GET['registered'])) {
    $success = 'Account created successfully! You can now login.';
}

if (isset($_COOKIE['remember_email'])) {
    $remember_email = $_COOKIE['remember_email'];
}

if (isset($_GET['redirect']) && trim($_GET['redirect']) !== '') {
    $_SESSION['after_login_redirect'] = $_GET['redirect'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // Check if the user is an admin first
    $q = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
    $q->execute([$email]);
    $u = $q->fetch();

    if (!$u) {
        // If not an admin, check the customers table
        $q = $pdo->prepare("SELECT * FROM customers WHERE email = ?");
        $q->execute([$email]);
        $u = $q->fetch();
        $is_admin = false;
    } else {
        $is_admin = true;
    }

    if ($u && password_verify($pass, $u['password'])) {
        $_SESSION['customer_id'] = $is_admin ? $u['admin_id'] : $u['customer_id'];
        $_SESSION['username'] = $u['name'];
        $_SESSION['role'] = $u['role'] ?? ($is_admin ? 'admin' : 'normal');


        if ($remember) {
            setcookie('remember_email', $email, time() + (30 * 24 * 60 * 60), '/');
        } else {
            setcookie('remember_email', '', time() - 3600, '/');
        }

        if ($_SESSION['role'] === 'admin') {
            header('Location: admin/dashboard.php');
            exit;
        }

        // If a redirect URL is saved in session (e.g. from movie/seat selection)
        if (isset($_SESSION['redirect_after_login'])) {
            $redir = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
            unset($_SESSION['after_login_redirect']); // Clear generic redirect if it exists
            header('Location: ' . $redir);
            exit;
        }

        $redir = $_SESSION['after_login_redirect'] ?? 'dashboard.php';
        unset($_SESSION['after_login_redirect']);

        header('Location: ' . $redir);
        exit;
    } else {
        $err = 'Invalid credentials';
    }
}

$page_title = 'Welcome Back';
include 'includes/auth_header.php';
?>

<form method="post">
    <div class="form-row">
        <input 
            class="input-field"
            name="email" 
            type="email" 
            placeholder="Email" 
            value="<?= htmlspecialchars($remember_email) ?>" 
            required
        >
    </div>

    <div class="form-row password-wrapper">
        <input 
            class="input-field"
            name="password" 
            id="password" 
            type="password" 
            placeholder="Password" 
            required
        >
        <span class="eye-icon" data-target="password">
            <i class="far fa-eye"></i>
        </span>
    </div>

    <div class="remember-forgot">
        <label style="display: flex; align-items: center; gap: 0.5rem; color: #555; cursor: pointer;">
            <input 
                type="checkbox" 
                name="remember" 
                style="width: 16px; height: 16px; accent-color: #2575fc; cursor: pointer;"
                <?= $remember_email ? 'checked' : '' ?>
            >
            Remember me
        </label>

        <a href="forgot-password.php" style="color: #2575fc; text-decoration: none;">Forgot Password?</a>
    </div>

    <button class="btn" type="submit">Login</button>
</form>

<div class="links">
    Don't have an account?
    <a href="register.php">Sign up</a>
</div>

<?php include 'includes/auth_footer.php'; ?>