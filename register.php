<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/AuthController.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $countryCode = trim($_POST['country_code'] ?? '');
    $phoneNumber = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    /*
     * NAME VALIDATION
     * Allows letters, spaces, hyphens and apostrophes.
     */
    if (!preg_match("/^[\p{L}][\p{L}\s'-]*$/u", $name)) {

        $error = 'Name can contain letters, spaces, hyphens and apostrophes only.';

    /*
     * EMAIL
     */
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    /*
     * COUNTRY CODE
     */
    } elseif ($countryCode === '') {

        $error = 'Please select your country code.';

    /*
     * PHONE
     */
    } elseif (!preg_match('/^[0-9]{7,15}$/', $phoneNumber)) {

        $error = 'Phone number must contain 7 to 15 digits.';

    /*
     * PASSWORD LENGTH
     */
    } elseif (strlen($password) < 8) {

        $error = 'Password must contain at least 8 characters.';

    /*
     * UPPERCASE
     */
    } elseif (!preg_match('/[A-Z]/', $password)) {

        $error = 'Password must contain at least one uppercase letter.';

    /*
     * LOWERCASE
     */
    } elseif (!preg_match('/[a-z]/', $password)) {

        $error = 'Password must contain at least one lowercase letter.';

    /*
     * NUMBER
     */
    } elseif (!preg_match('/[0-9]/', $password)) {

        $error = 'Password must contain at least one number.';

    /*
     * SPECIAL CHARACTER
     */
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {

        $error = 'Password must contain at least one special character.';

    /*
     * CONFIRM PASSWORD
     */
    } elseif ($password !== $confirmPassword) {

        $error = 'Passwords do not match.';

    } else {

        $phone = $countryCode . ' ' . $phoneNumber;

        $controller = new AuthController();

        $result = $controller->register(
            $name,
            $email,
            $phone,
            $address,
            $password
        );

        if ($result['success']) {

            header('Location: index.php');
            exit;
        }

        $error = $result['message'];
    }
}

require __DIR__ . '/views/auth/register.php';