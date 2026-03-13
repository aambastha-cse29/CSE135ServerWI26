<?php
// admin_action.php
// Handles POST actions for user management: add, edit, delete.
// Always redirects back to /admin after processing.

require_once 'auth_check.php';
require_once 'auth_helpers.php';
require_once 'db.php';

// Only superadmin can perform these actions
if (!canManageUsers()) {
    header("Location: /403");
    exit;
}

$action = $_POST['action'] ?? null;
$pdo    = getDB();

// --------------------
// ADD USER
// --------------------
if ($action === 'add') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';
    $sections = $_POST['sections'] ?? [];

    $allowedRoles = ['superadmin', 'analyst', 'viewer'];
    $allowedSecs  = ['performance', 'activity', 'traffic'];

    if (empty($username) || empty($password) || !in_array($role, $allowedRoles, true)) {
        header("Location: /admin?error=Invalid+input");
        exit;
    }

    // Sanitize sections — only allow known values, only for analysts
    $sections = array_values(array_filter($sections, fn($s) => in_array($s, $allowedSecs, true)));
    $sectionsJson = ($role === 'analyst' && !empty($sections))
        ? json_encode($sections)
        : null;

    $hash = password_hash($password, PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO users (username, password, role, sections) VALUES (:username, :password, :role, :sections)"
        );
        $stmt->execute([
            ':username' => $username,
            ':password' => $hash,
            ':role'     => $role,
            ':sections' => $sectionsJson,
        ]);
        header("Location: /admin?success=User+added+successfully");
        exit;
    } catch (PDOException $e) {
        // Duplicate username hits unique key constraint
        header("Location: /admin?error=Username+already+exists");
        exit;
    }
}

// --------------------
// EDIT USER
// --------------------
elseif ($action === 'edit') {
    $userId   = (int)($_POST['user_id'] ?? 0);
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';
    $sections = $_POST['sections'] ?? [];

    $allowedRoles = ['superadmin', 'analyst', 'viewer'];
    $allowedSecs  = ['performance', 'activity', 'traffic'];

    if ($userId <= 0 || !in_array($role, $allowedRoles, true)) {
        header("Location: /admin?error=Invalid+input");
        exit;
    }

    $sections = array_values(array_filter($sections, fn($s) => in_array($s, $allowedSecs, true)));
    $sectionsJson = ($role === 'analyst' && !empty($sections))
        ? json_encode($sections)
        : null;

    try {
        if (!empty($password)) {
            // Update role, sections, and password
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare(
                "UPDATE users SET role = :role, sections = :sections, password = :password WHERE id = :id"
            );
            $stmt->execute([
                ':role'     => $role,
                ':sections' => $sectionsJson,
                ':password' => $hash,
                ':id'       => $userId,
            ]);
        } else {
            // Update role and sections only
            $stmt = $pdo->prepare(
                "UPDATE users SET role = :role, sections = :sections WHERE id = :id"
            );
            $stmt->execute([
                ':role'     => $role,
                ':sections' => $sectionsJson,
                ':id'       => $userId,
            ]);
        }
        header("Location: /admin?success=User+updated+successfully");
        exit;
    } catch (PDOException $e) {
        header("Location: /admin?error=Update+failed");
        exit;
    }
}

// --------------------
// DELETE USER
// --------------------
elseif ($action === 'delete') {
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId <= 0) {
        header("Location: /admin?error=Invalid+user");
        exit;
    }

    // Prevent superadmin from deleting themselves
    if ($userId === (int)$_SESSION['user_id']) {
        header("Location: /admin?error=Cannot+delete+your+own+account");
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        header("Location: /admin?success=User+deleted+successfully");
        exit;
    } catch (PDOException $e) {
        header("Location: /admin?error=Delete+failed");
        exit;
    }
}

else {
    header("Location: /admin");
    exit;
}