<?php
// session_start();
require_once 'config/database.php'; 

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function isManager() {
    return isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'manager');
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

function requireManager() {
    requireLogin();
    if (!isManager()) {
        header('Location: index.php');
        exit;
    }
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = 'Please enter username and password';
        header('Location: login.php');
        exit;
    }

    try {
        $database = new Database();
        $db = $database->getConnection();

        $query = 'SELECT id, username, password, email, full_name, role FROM users WHERE username = :username LIMIT 1';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['full_name'] = $row['full_name'];
                $_SESSION['user_role'] = $row['role'];
                
                header('Location: index.php');
                exit;
            } else {
                $_SESSION['error'] = 'Invalid password';
            }
        } else {
            $_SESSION['error'] = 'User not found';
        }
    } catch (PDOException $exception) {
        $_SESSION['error'] = 'Database error: ' . $exception->getMessage();
    }

    header('Location: login.php');
    exit;
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}
?>