<?php
session_start();
require "../requires/common.php";
require "../requires/title.php";
require "../requires/connect.php";


if (isset($_GET['download'])) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=users_export_" . date("Y-m-d") . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");
}

$role_id = isset($_GET['role_id']) ? intval($_GET['role_id']) : 0;

if ($role_id > 0) {
    $where = "WHERE role_id = $role_id";
} else {
    $where = "";
}

echo "<table border='1'>";
echo "<tr><th>Name</th><th>Email</th><th>Phone</th><th>Gender</th><th>Role</th><th>Status</th><th>Created At</th></tr>";

$query = $mysqli->query("SELECT * FROM users $where");


function getRoleName($role_id) {
    $roles = [
        1 => 'Admin',
        2 => 'Teacher',
        3 => 'Student',
        4 => 'External',
    ];
    return $roles[(int)$role_id] ?? 'Unknown';
}

while ($row = $query->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
    echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
    echo "<td>" . ucfirst(htmlspecialchars($row['gender'])) . "</td>";
    echo "<td>" . getRoleName($row['role_id']) . "</td>";
    echo "<td>" . ($row['status'] == 1 ? 'Active' : 'Inactive') . "</td>";
    echo "<td>" . date("Y-m-d", strtotime($row['created_at'])) . "</td>";
    echo "</tr>";
}

echo "</table>";
exit;
