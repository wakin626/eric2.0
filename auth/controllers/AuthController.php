<?php
namespace App\Controllers;

use App\Models\UserModel;

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new UserModel();
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                $_SESSION['error'] = 'Please enter username and password';
                $this->render('login', []);
                return;
            }

            $user = $this->userModel->verifyLogin($username, $password);

            if ($user) {
                if ($user['status'] == 0) {
                    $_SESSION['error'] = 'Account is inactive';
                    $this->render('login', []);
                    return;
                }

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['department'] = $user['department'];

                $redirectMap = [
                    'admin' => 'admin',
                    'warehouse' => 'warehouse',
                    'production' => 'production',
                    'finance' => 'finance'
                ];
                $redirect = $redirectMap[$user['department']] ?? 'admin';
                header("Location: ?controller={$redirect}");
                exit;
            } else {
                $_SESSION['error'] = 'Invalid username or password';
                $this->render('login', []);
                return;
            }
        }

        if (isset($_SESSION['user_id'])) {
            $redirectMap = [
                'admin' => 'admin',
                'warehouse' => 'warehouse',
                'production' => 'production',
                'finance' => 'finance'
            ];
            $redirect = $redirectMap[$_SESSION['department']] ?? 'admin';
            header("Location: ?controller={$redirect}");
            exit;
        }

        $this->render('login', []);
    }

    public function signup() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $full_name = trim($_POST['full_name'] ?? '');
            $department = $_POST['department'] ?? '';

            $errors = [];

            if (empty($username) || empty($email) || empty($password) || empty($full_name) || empty($department)) {
                $errors[] = 'All fields are required';
            }

            if (strlen($username) < 3) {
                $errors[] = 'Username must be at least 3 characters';
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email address';
            }

            if (strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters';
            }

            if ($password !== $confirm_password) {
                $errors[] = 'Passwords do not match';
            }

            if ($this->userModel->getByUsername($username)) {
                $errors[] = 'Username already taken';
            }

            if ($this->userModel->getByEmail($email)) {
                $errors[] = 'Email already registered';
            }

            if (!empty($errors)) {
                $data['errors'] = $errors;
                $data['old'] = $_POST;
                $this->render('signup', $data);
                return;
            }

            $this->userModel->create([
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'full_name' => $full_name,
                'department' => $department
            ]);

            $_SESSION['success'] = 'Account created successfully. Please login.';
            header('Location: ?controller=auth&action=login');
            exit;
        }

        $this->render('signup', []);
    }

    public function logout() {
        session_destroy();
        header('Location: ?controller=auth&action=login');
        exit;
    }

    private function render($view, $data = []) {
        extract($data);
        $departments = ['admin', 'warehouse', 'production', 'finance'];
        include __DIR__ . "/../views/{$view}.php";
    }
}