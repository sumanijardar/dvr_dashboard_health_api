<?php
// ==============================================================================
// Footage Availability Report APIs (GET Method)
// ==============================================================================

include('config.php');

// ==============================================================================
// 1. Footage Status Summary Counts
// URL: api/footage_api.php?fetch_footage_counts=1
// ==============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_footage_counts'])) {

    // 1. Get overall total count
    $total_query = "
        SELECT COUNT(id) AS total_count
        FROM `footage_availability_report`
    ";

    $total_result = mysqli_query($con, $total_query);

    // Check total count query error
    if ($total_result === false) {
        error_log('Footage total count query failed: ' . mysqli_error($con));

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to fetch footage total count.'
        ]);
        exit();
    }

    $total_row = mysqli_fetch_assoc($total_result);

    if ($total_row === false) {
        error_log('Footage total count fetch failed: ' . mysqli_error($con));

        mysqli_free_result($total_result);

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to retrieve footage total count.'
        ]);
        exit();
    }

    $total_count = (int) $total_row['total_count'];

    mysqli_free_result($total_result);

    // 2. Get per-status counts
    $status_query = "
        SELECT status, COUNT(id) AS cnt
        FROM `footage_availability_report`
        GROUP BY status
        ORDER BY cnt DESC
    ";

    $status_result = mysqli_query($con, $status_query);

    // Check status count query error
    if ($status_result === false) {
        error_log('Footage status counts query failed: ' . mysqli_error($con));

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to fetch footage status counts.'
        ]);
        exit();
    }

    // Build status_counts map
    $status_counts = [];

    while ($row = mysqli_fetch_assoc($status_result)) {
        $status_counts[$row['status']] = (int) $row['cnt'];
    }

    // Check for fetch errors
    if (mysqli_errno($con)) {
        error_log('Footage status counts fetch error: ' . mysqli_error($con));

        mysqli_free_result($status_result);

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'An error occurred while reading footage status counts.'
        ]);
        exit();
    }

    mysqli_free_result($status_result);

    // 3. Success response
    http_response_code(200);

    echo json_encode([
        'Code'    => 200,
        'message' => 'Footage counts fetched successfully.',
        'data'    => [
            'total'         => $total_count,
            'status_counts' => $status_counts
        ]
    ]);

    exit();
}


// ==============================================================================
// 2. Footage Distinct Status Types (for dropdown)
// URL: api/footage_api.php?fetch_status_types=1
// ==============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_status_types'])) {

    // Fetch all distinct status values with counts
    $status_query = "
        SELECT status, COUNT(id) AS cnt
        FROM `footage_availability_report`
        GROUP BY status
        ORDER BY cnt DESC
    ";

    $status_result = mysqli_query($con, $status_query);

    // Check query error
    if ($status_result === false) {
        error_log('Footage status types query failed: ' . mysqli_error($con));

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to fetch footage status types.'
        ]);
        exit();
    }

    // Fetch all rows
    $data = [];

    while ($row = mysqli_fetch_assoc($status_result)) {
        $data[] = [
            'status' => $row['status'],
            'count'  => (int) $row['cnt']
        ];
    }

    // Check for fetch errors
    if (mysqli_errno($con)) {
        error_log('Footage status types fetch error: ' . mysqli_error($con));

        mysqli_free_result($status_result);

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'An error occurred while reading footage status types.'
        ]);
        exit();
    }

    mysqli_free_result($status_result);

    // Success response
    http_response_code(200);

    echo json_encode([
        'Code'        => 200,
        'message'     => 'Footage status types fetched successfully.',
        'total_count' => count($data),
        'data'        => $data
    ]);

    exit();
}


// ==============================================================================
// 3. Footage Records — Paginated List with Filters
// URL: api/footage_api.php?page=1&limit=25&status=PRESENT&ip=...&from_date=...&to_date=...&search=...
// ==============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // ── Input sanitization ──────────────────────────────────────────────────
    $page      = max(1, (int) ($_GET['page']   ?? 1));
    $limit     = max(1, min(200, (int) ($_GET['limit'] ?? 25)));
    $offset    = ($page - 1) * $limit;

    $status    = trim($_GET['status']    ?? '');
    $ip        = trim($_GET['ip']        ?? '');
    $from_date = trim($_GET['from_date'] ?? '');
    $to_date   = trim($_GET['to_date']   ?? '');
    $search    = trim($_GET['search']    ?? '');

    // Whitelist sort columns to prevent SQL injection
    $allowed_sort_cols = ['id', 'ip', 'footage_dt_from', 'footage_dt_to', 'status', 'udate'];
    $sort_col  = in_array($_GET['sort'] ?? '', $allowed_sort_cols, true) ? $_GET['sort'] : 'id';
    $sort_dir  = strtoupper($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

    // ── Build WHERE clause ──────────────────────────────────────────────────
    $where_parts = [];
    $bind_types  = '';
    $bind_vals   = [];

    if ($status !== '') {
        $where_parts[] = 'status = ?';
        $bind_types   .= 's';
        $bind_vals[]   = $status;
    }

    if ($ip !== '') {
        $where_parts[] = 'ip LIKE ?';
        $bind_types   .= 's';
        $bind_vals[]   = '%' . $ip . '%';
    }

    if ($from_date !== '') {
        $where_parts[] = 'footage_dt_from >= ?';
        $bind_types   .= 's';
        $bind_vals[]   = $from_date . ' 00:00:00';
    }

    if ($to_date !== '') {
        $where_parts[] = 'footage_dt_to <= ?';
        $bind_types   .= 's';
        $bind_vals[]   = $to_date . ' 23:59:59';
    }

    if ($search !== '') {
        $where_parts[] = '(ip LIKE ? OR status LIKE ?)';
        $bind_types   .= 'ss';
        $like_val      = '%' . $search . '%';
        $bind_vals[]   = $like_val;
        $bind_vals[]   = $like_val;
    }

    $where_sql = count($where_parts) > 0
        ? 'WHERE ' . implode(' AND ', $where_parts)
        : '';

    // ── 1. Count query ──────────────────────────────────────────────────────
    $count_query = "
        SELECT COUNT(id) AS total_count
        FROM `footage_availability_report`
        $where_sql
    ";

    $count_stmt = mysqli_prepare($con, $count_query);

    // Check prepare error
    if ($count_stmt === false) {
        error_log('Footage count prepare failed: ' . mysqli_error($con));

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to prepare footage count query.'
        ]);
        exit();
    }

    // Bind parameters if any
    if ($bind_types !== '' && count($bind_vals) > 0) {
        mysqli_stmt_bind_param($count_stmt, $bind_types, ...$bind_vals);
    }

    mysqli_stmt_execute($count_stmt);

    $count_result = mysqli_stmt_get_result($count_stmt);

    // Check count result error
    if ($count_result === false) {
        error_log('Footage count get_result failed: ' . mysqli_stmt_error($count_stmt));

        mysqli_stmt_close($count_stmt);

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to fetch footage records count.'
        ]);
        exit();
    }

    $count_row = mysqli_fetch_assoc($count_result);

    if ($count_row === false) {
        error_log('Footage count row fetch failed: ' . mysqli_error($con));

        mysqli_free_result($count_result);
        mysqli_stmt_close($count_stmt);

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to retrieve footage records count.'
        ]);
        exit();
    }

    $total_count = (int) $count_row['total_count'];
    $total_pages = $limit > 0 ? (int) ceil($total_count / $limit) : 1;

    mysqli_free_result($count_result);
    mysqli_stmt_close($count_stmt);

    // ── 2. Data query ───────────────────────────────────────────────────────
    $data_query = "
        SELECT
            id,
            ip,
            footage_dt_from,
            footage_dt_to,
            status,
            udate
        FROM `footage_availability_report`
        $where_sql
        ORDER BY $sort_col $sort_dir
        LIMIT ? OFFSET ?
    ";

    $data_stmt = mysqli_prepare($con, $data_query);

    // Check prepare error
    if ($data_stmt === false) {
        error_log('Footage data prepare failed: ' . mysqli_error($con));

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to prepare footage data query.'
        ]);
        exit();
    }

    // Bind all filter params + LIMIT + OFFSET
    $full_types = $bind_types . 'ii';
    $full_vals  = array_merge($bind_vals, [$limit, $offset]);

    mysqli_stmt_bind_param($data_stmt, $full_types, ...$full_vals);

    mysqli_stmt_execute($data_stmt);

    $data_result = mysqli_stmt_get_result($data_stmt);

    // Check data result error
    if ($data_result === false) {
        error_log('Footage data get_result failed: ' . mysqli_stmt_error($data_stmt));

        mysqli_stmt_close($data_stmt);

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to fetch footage records data.'
        ]);
        exit();
    }

    // 3. Fetch all rows
    $data = [];

    while ($row = mysqli_fetch_assoc($data_result)) {
        $data[] = $row;
    }

    // Check for fetch errors
    if (mysqli_errno($con)) {
        error_log('Footage records fetch error: ' . mysqli_error($con));

        mysqli_free_result($data_result);
        mysqli_stmt_close($data_stmt);

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'An error occurred while reading footage records.'
        ]);
        exit();
    }

    // 4. Free results
    mysqli_free_result($data_result);
    mysqli_stmt_close($data_stmt);

    // 5. Success response
    http_response_code(200);

    echo json_encode([
        'Code'         => 200,
        'message'      => 'Footage records fetched successfully.',
        'total_count'  => $total_count,
        'current_page' => $page,
        'per_page'     => $limit,
        'total_pages'  => $total_pages,
        'data'         => $data
    ]);

    exit();
}


// ==============================================================================
// 400 — Invalid Request (fallback)
// ==============================================================================

http_response_code(400);

echo json_encode([
    'Code'    => 400,
    'message' => 'Invalid API request.'
]);
