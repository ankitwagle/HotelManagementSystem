
<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/AuthController.php';


// --------------------------------------------------
// IF ALREADY LOGGED IN
// --------------------------------------------------

if (isLoggedIn()) {

    if (($_SESSION['user']['role'] ?? '') === 'admin') {
        header('Location: /views/admin/index.php');
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

        $result = $controller->login(
            $email,
            $password
        );


        // --------------------------------------------------
        // LOGIN SUCCESS
        // --------------------------------------------------

        if ($result['success']) {

            if (($_SESSION['user']['role'] ?? '') === 'admin') {

                header('Location: /views/admin/index.php');
                exit;
            }

            header('Location: /index.php');
            exit;
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
