<?php
require 'includes/config.php';
require 'includes/functions.php';
require 'includes/mail_service.php';

$err = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = validateRegistration($_POST);
    
    if (!empty($errors)) {
        $err = $errors[0];
    } else {
        $email = trim($_POST['email']);
        $name = trim($_POST['name']);
        
        $q = $pdo->prepare("SELECT customer_id FROM customers WHERE email = ?");
        $q->execute([$email]);

        if ($q->fetch()) {
            $err = 'Email already registered';
        } else {
            $hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $role = "normal";

            $ins = $pdo->prepare("
                INSERT INTO customers 
                (name, email, phone, gender, age, password, role) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            if ($ins->execute([
                $name, 
                $email, 
                trim($_POST['phone']), 
                trim($_POST['gender']), 
                intval($_POST['age']), 
                $hash, 
                $role
            ])) {
                // Send welcome email
                sendWelcomeEmail($email, $name);
                
                header('Location: login.php?registered=1');
                exit;
            } else {
                $err = 'Something went wrong. Please try again.';
            }
        }
    }
}

$page_title = 'Create Account';
include 'includes/auth_header.php';
?>

<form method="post">
    <div class="form-row">
        <input class="input-field" name="name" placeholder="Your name" required>
    </div>

    <div class="form-row">
        <input class="input-field" name="email" type="email" placeholder="Email" required>
    </div>

    <div class="form-row">
        <input class="input-field" name="phone" placeholder="Phone 10 digits" required>
    </div>

    <div class="form-row">
        <select name="gender" required>
            <option value="">Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
        </select>
    </div>

    <div class="form-row">
        <input class="input-field" name="age" type="number" min="5" max="100" placeholder="Age" required>
    </div>

    <div class="form-row password-wrapper">
        <input class="input-field" name="password" id="password" type="password" placeholder="Password" required>
        <span class="eye-icon" data-target="password">Show</span>
    </div>

    <div class="form-row password-wrapper">
        <input class="input-field" name="Confirmpassword" id="ConfirmPassword" type="password" placeholder="Confirm Password" required>
        <span class="eye-icon" data-target="ConfirmPassword">Show</span>
    </div>

    <button class="btn">Register</button>
</form>

<div class="links">
    Already have an account? <a href="login.php">Login</a>
</div>

<?php include 'includes/auth_footer.php'; ?>