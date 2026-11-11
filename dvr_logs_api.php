<?php
include('config.php');

// Accept GET requests (with or without ?fetch_dvr_logs=1)
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'Code' => 405,
        'message' => 'Method not allowed. Only GET requests are accepted.'
    ]);
    exit();
}

// ------------------------------------------------------------------------------
// Dedicated Major Types Endpoint: ?fetch_major_types=1 or ?action=major_types
// ------------------------------------------------------------------------------
if (isset($_GET['fetch_major_types']) || (isset($_GET['action']) && $_GET['action'] === 'major_types')) {
    $types_query = "SELECT major_type, COUNT(*) as count 
                    FROM dvr_fetched_logs 
                    WHERE major_type IS NOT NULL AND major_type != '' 
                    GROUP BY major_type 
                    ORDER BY count DESC";
    $types_res = mysqli_query($con, $types_query);
    $major_types = [];
    if ($types_res) {
        while ($r = mysqli_fetch_assoc($types_res)) {
            $major_types[] = [
                'major_type' => $r['major_type'],
                'count' => (int)$r['count']
            ];
        }
    }
    http_response_code(200);
    echo json_encode([
        'Code' => 200,
        'message' => 'Major types fetched successfully.',
        'data' => $major_types
    ]);
    exit();
}

// ------------------------------------------------------------------------------
// Dedicated DVR Names Endpoint: ?fetch_dvr_names=1 or ?action=dvr_names
// ------------------------------------------------------------------------------
if (isset($_GET['fetch_dvr_names']) || (isset($_GET['action']) && $_GET['action'] === 'dvr_names')) {
    $dvr_query = "SELECT dvrname, COUNT(*) as count 
                  FROM dvr_fetched_logs 
                  WHERE dvrname IS NOT NULL AND dvrname != '' 
                  GROUP BY dvrname 
                  ORDER BY count DESC";
    $dvr_res = mysqli_query($con, $dvr_query);
    $dvr_names = [];
    if ($dvr_res) {
        while ($r = mysqli_fetch_assoc($dvr_res)) {
            $dvr_names[] = [
                'dvrname' => $r['dvrname'],
                'count' => (int)$r['count']
            ];
        }
    }
    http_response_code(200);
    echo json_encode([
        'Code' => 200,
        'message' => 'DVR names fetched successfully.',
        'data' => $dvr_names
    ]);
    exit();
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(500, (int)($_GET['limit'] ?? 15)));
$offset = ($page - 1) * $limit;

// Filtering
$where_clauses = [];
$params = [];
$types = '';

// Filter by Major Type (Dynamic dropdown)
if (!empty($_GET['major_type']) && strtoupper(trim($_GET['major_type'])) !== 'ALL') {
    $where_clauses[] = "major_type = ?";
    $params[] = trim($_GET['major_type']);
    $types .= 's';
}

// Filter by DVR Name (Dynamic dropdown)
if (!empty($_GET['dvrname']) && strtoupper(trim($_GET['dvrname'])) !== 'ALL') {
    $where_clauses[] = "dvrname = ?";
    $params[] = trim($_GET['dvrname']);
    $types .= 's';
}

// Filter by ATM ID
if (!empty($_GET['atmid'])) {
    $where_clauses[] = "atmid LIKE ?";
    $params[] = '%' . trim($_GET['atmid']) . '%';
    $types .= 's';
}

// Filter by IP Address
if (!empty($_GET['ipaddress'])) {
    $where_clauses[] = "ipaddress LIKE ?";
    $params[] = '%' . trim($_GET['ipaddress']) . '%';
    $types .= 's';
}

// Filter by Date Range
if (!empty($_GET['from_date'])) {
    $where_clauses[] = "log_time >= ?";
    $params[] = trim($_GET['from_date']) . ' 00:00:00';
    $types .= 's';
}
if (!empty($_GET['to_date'])) {
    $where_clauses[] = "log_time <= ?";
    $params[] = trim($_GET['to_date']) . ' 23:59:59';
    $types .= 's';
}

// General Search across multiple columns
if (!empty($_GET['search'])) {
    $searchTerm = '%' . trim($_GET['search']) . '%';
    $where_clauses[] = "(ipaddress LIKE ? OR atmid LIKE ? OR dvrname LIKE ? OR description LIKE ? OR minor_type LIKE ? OR major_type LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ssssss';
}

$where_sql = !empty($where_clauses) ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

// 1. Count query
$count_query = "SELECT COUNT(*) AS total_count FROM dvr_fetched_logs" . $where_sql;
$count_stmt = mysqli_prepare($con, $count_query);

if (!$count_stmt) {
    http_response_code(500);
    echo json_encode([
        'Code' => 500,
        'message' => 'Failed to prepare count query.',
        'details' => mysqli_error($con)
    ]);
    exit();
}

if (!empty($params)) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}

if (!mysqli_stmt_execute($count_stmt)) {
    http_response_code(500);
    echo json_encode([
        'Code' => 500,
        'message' => 'Failed to execute count query.',
        'details' => mysqli_stmt_error($count_stmt)
    ]);
    exit();
}

$count_result = mysqli_stmt_get_result($count_stmt);
$count_row = mysqli_fetch_assoc($count_result);
$total_count = (int)($count_row['total_count'] ?? 0);
mysqli_stmt_close($count_stmt);

// 2. Data query
$data_query = "SELECT * FROM dvr_fetched_logs" . $where_sql . " ORDER BY id DESC LIMIT ? OFFSET ?";
$data_stmt = mysqli_prepare($con, $data_query);

if (!$data_stmt) {
    http_response_code(500);
    echo json_encode([
        'Code' => 500,
        'message' => 'Failed to prepare data query.',
        'details' => mysqli_error($con)
    ]);
    exit();
}

$data_params = $params;
$data_types = $types . 'ii';
$data_params[] = $limit;
$data_params[] = $offset;

mysqli_stmt_bind_param($data_stmt, $data_types, ...$data_params);

if (!mysqli_stmt_execute($data_stmt)) {
    http_response_code(500);
    echo json_encode([
        'Code' => 500,
        'message' => 'Failed to fetch DVR logs.',
        'details' => mysqli_stmt_error($data_stmt)
    ]);
    exit();
}

$data_result = mysqli_stmt_get_result($data_stmt);
$data = [];
while ($row = mysqli_fetch_assoc($data_result)) {
    $data[] = $row;
}
mysqli_stmt_close($data_stmt);

http_response_code(200);
echo json_encode([
    'Code' => 200,
    'message' => 'DVR Logs fetched successfully.',
    'total_count' => $total_count,
    'current_page' => $page,
    'per_page' => $limit,
    'total_pages' => $limit > 0 ? (int)ceil($total_count / $limit) : 0,
    'data' => $data
]);
?>