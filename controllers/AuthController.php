<?php

require_once __DIR__ . '/../models/User.php';

class AuthController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function login(
        string $email,
        string $password
    ): array {

        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid email or password.'
            ];
        }

        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Invalid email or password.'
            ];
        }

        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        return [
            'success' => true
        ];
    }

    public function register(
        string $name,
        string $email,
        string $phone,
        string $address,
        string $password
    ): array {

        if ($this->userModel->findByEmail($email)) {

            return [
                'success' => false,
                'message' => 'An account with this email already exists.'
            ];
        }

        $id = $this->userModel->create(
            $name,
            $email,
            $phone,
            $address,
            $password
        );

        $user = $this->userModel->findById($id);

        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        return [
            'success' => true
        ];
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {

            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    }
}