
<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/OtpController.php';


// --------------------------------------------------
// IF ALREADY LOGGED IN
// --------------------------------------------------

if (isLoggedIn()) {

    if (($_SESSION['user']['role'] ?? '') === 'admin') {
        header('Location: /index.php');
        exit;
    }

    header('Location: /index.php');
    exit;
}


// --------------------------------------------------
// VARIABLES
// --------------------------------------------------

$error = '';


// --------------------------------------------------
// HANDLE LOGIN FORM
// --------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    unset($_SESSION['pending_login_user']);

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';


    // --------------------------------------------------
    // VALIDATION
    // --------------------------------------------------

    if ($email === '' || $password === '') {

        $error = 'Please enter your email and password.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    } else {

        // --------------------------------------------------
        // AUTHENTICATE USER
        // --------------------------------------------------

        $controller = new AuthController();

        $result = $controller->authenticateCredentials(
            $email,
            $password
        );


        // --------------------------------------------------
        // LOGIN SUCCESS
        // --------------------------------------------------

        if ($result['success']) {

            $pendingUser = $result['user'];

            $otpResult = (new OtpController())->sendLoginOtp(
                (int) $pendingUser['id'],
                $pendingUser['email'],
                $pendingUser['name']
            );

            if ($otpResult['success']) {

                $_SESSION['pending_login_user'] = $pendingUser;

                header('Location: /views/auth/verify-otp.php');
                exit;
            }

            $error = $otpResult['message'];
        }


        // --------------------------------------------------
        // LOGIN FAILED
        // --------------------------------------------------

        $error = $result['message'] ?? 'Invalid email or password.';
    }
}


// --------------------------------------------------
// LOAD LOGIN VIEW
// --------------------------------------------------

require __DIR__ . '/views/auth/login.php';
