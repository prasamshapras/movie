<?php
/**
 * Shared functions for Ticketly
 */

/**
 * Validate user registration data
 */
function validateRegistration($data) {
    $errors = [];
    
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $gender = trim($data['gender'] ?? '');
    $age = intval($data['age'] ?? 0);
    $pass = $data['password'] ?? '';
    $confpass = $data['Confirmpassword'] ?? '';

    $nameRegex  = "/^[a-zA-Z\s]{3,50}$/";
    $emailRegex = "/^[\w\.-]+@[\w\.-]+\.\w{2,}$/";
    $phoneRegex = "/^[0-9]{10}$/";
    $passRegex  = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/";

    if (!$name || !$email || !$phone || !$gender || !$age || !$pass || !$confpass) {
        $errors[] = 'Please fill all fields';
    } elseif (!preg_match($nameRegex, $name)) {
        $errors[] = 'Name must be 3–50 letters only';
    } elseif (!preg_match($emailRegex, $email)) {
        $errors[] = 'Invalid email format';
    } elseif (!preg_match($phoneRegex, $phone)) {
        $errors[] = 'Phone must be exactly 10 digits';
    } elseif ($age < 5 || $age > 100) {
        $errors[] = 'Age must be between 5 and 100';
    } elseif (!in_array($gender, ['Male', 'Female', 'Other'])) {
        $errors[] = 'Invalid gender selected';
    } elseif (!preg_match($passRegex, $pass)) {
        $errors[] = 'Password must be at least 8 characters with uppercase, lowercase, digit, and special character';
    } elseif ($pass !== $confpass) {
        $errors[] = 'Passwords do not match';
    }
    
    return $errors;
}

/**
 * Get the full URL for a movie poster
 */
function getMoviePoster($path) {
    if (!$path || trim($path) === '') {
        return 'https://via.placeholder.com/600x900?text=No+Poster';
    }
    
    $path = trim($path);
    
    // Standardize all slashes to forward slashes
    $path = str_replace('\\', '/', $path);
    
    // If it's already a full URL, return it
    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }
    
    // Remove any redundant relative path junk like ../ or leading /
    $path = preg_replace('/^(\.\.\/|\/)+/', '', $path);
    
    // Ensure we don't have 'Ticketly/' in the path if BASE_URL already includes it
    // This handles cases where people might manually type the subfolder
    $projectName = basename(dirname(__DIR__));
    $path = preg_replace('/^' . preg_quote($projectName, '/') . '\//i', '', $path);
    
    return BASE_URL . '/' . $path;
}

/**
 * Clean string for output
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
