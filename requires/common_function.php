<?php

// SELECT data
function selectData($table, $mysqli, $where = '', $select = '*', $order = '')
{
    $sql = "SELECT $select FROM `$table`";
    if ($where) $sql .= " WHERE $where";
    if ($order) $sql .= " ORDER BY $order";
    return $mysqli->query($sql);
}

// INSERT data
function insertData($table, $mysqli, $data)
{
    $columns = array_keys($data);
    $values = array_map(function ($val) use ($mysqli) {
        return "'" . $mysqli->real_escape_string($val) . "'";
    }, array_values($data));

    $columnList = implode(", ", array_map(fn($col) => "`$col`", $columns));
    $valueList = implode(", ", $values);

    $sql = "INSERT INTO `$table` ($columnList) VALUES ($valueList)";
    return $mysqli->query($sql);
}

// UPDATE data
function updateData($table, $mysqli, $data, $where)
{
    $updates = [];
    foreach ($data as $key => $value) {
        $safeVal = $mysqli->real_escape_string($value);
        $updates[] = "`$key` = '$safeVal'";
    }

    $wheres = [];
    foreach ($where as $key => $value) {
        $safeVal = $mysqli->real_escape_string($value);
        $wheres[] = "`$key` = '$safeVal'";
    }

    $sql = "UPDATE `$table` SET " . implode(", ", $updates) . " WHERE " . implode(" AND ", $wheres);
    return $mysqli->query($sql);
}

// DELETE data
function deleteData($table, $mysqli, $where)
{
    $wheres = [];
    foreach ($where as $key => $value) {
        $safeVal = $mysqli->real_escape_string($value);
        $wheres[] = "`$key` = '$safeVal'";
    }

    $sql = "DELETE FROM `$table` WHERE " . implode(" AND ", $wheres);
    return $mysqli->query($sql);
}

// COUNT rows
function countData($table, $mysqli, $where = '')
{
    $sql = "SELECT COUNT(*) as total FROM `$table`";
    if ($where) $sql .= " WHERE $where";
    $result = $mysqli->query($sql);
    return $result ? $result->fetch_assoc()['total'] : 0;
}

// FETCH single row
function fetchSingle($queryResult)
{
    return $queryResult ? $queryResult->fetch_assoc() : null;
}

// FETCH all rows
function fetchAll($queryResult)
{
    $data = [];
    if ($queryResult) {
        while ($row = $queryResult->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}
