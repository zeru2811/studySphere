<?php 

function checkRoleAuth($role, $mysqli, $cp_base_url)
{
    $authenticated = false;

    // Session check
    if (isset($_SESSION['id'], $_SESSION['username'], $_SESSION['email'])) {
        $userId = intval($_SESSION['id']);
        $sql = "SELECT COUNT(*) AS total FROM users u 
                INNER JOIN role r ON u.role_id = r.id 
                WHERE u.id = $userId AND r.name = '$role'";
        $res = $mysqli->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            if ($row['total'] >= 1) $authenticated = true;
        }
    }

    // Cookie check if session fails
    if (!$authenticated && isset($_COOKIE['id'], $_COOKIE['username'], $_COOKIE['email'])) {
        $userId = intval($_COOKIE['id']);
        $sql = "SELECT COUNT(*) AS total FROM users u 
                INNER JOIN role r ON u.role_id = r.id 
                WHERE u.id = $userId AND r.name = '$role'";
        $res = $mysqli->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            if ($row['total'] >= 1) $authenticated = true;
        }
    }

    // Redirect if not authorized
    if (!$authenticated) {
        header("Location: {$cp_base_url}logout.php");
        exit();
    }
}
