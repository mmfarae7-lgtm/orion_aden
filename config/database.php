<?php
define('DB_HOST', 'sql110.infinityfree.com');
define('DB_USER', 'if0_42354202');
define('DB_PASS', 'Orionaden123');
define('DB_NAME', 'if0_42354202_orion_school');

function getConnection() {
    static $conn = null;
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $conn->set_charset('utf8mb4');
        } catch (Exception $e) {
            die('فشل الاتصال بقاعدة البيانات: ' . $e->getMessage());
        }
    }
    return $conn;
}

function query($sql, $params = []) {
    $conn = getConnection();
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die('خطأ في الاستعلام: ' . $conn->error);
        }
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) $types .= 'i';
            elseif (is_double($param)) $types .= 'd';
            else $types .= 's';
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result();
    }
    return $conn->query($sql);
}

function getRow($sql, $params = []) {
    $result = query($sql, $params);
    return $result ? $result->fetch_assoc() : null;
}

function getRows($sql, $params = []) {
    $result = query($sql, $params);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function insert($sql, $params = []) {
    query($sql, $params);
    return getConnection()->insert_id;
}

function update($sql, $params = []) {
    query($sql, $params);
    return getConnection()->affected_rows;
}

function delete($sql, $params = []) {
    query($sql, $params);
    return getConnection()->affected_rows;
}
