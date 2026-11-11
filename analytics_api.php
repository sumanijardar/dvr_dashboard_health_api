<?php
// ==============================================================================
// Analytics API — All chart data in one call
// URL: api/analytics_api.php?fetch_all=1
// ==============================================================================

include('config.php');

// ==============================================================================
// 1. Full Analytics Summary (all charts in one response)
// URL: api/analytics_api.php?fetch_all=1
// ==============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_all'])) {

    $response = [];

    // ── A. Fleet Overview ──────────────────────────────────────────────────
    $fleet_query = "
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN last_communication >= CURDATE() AND login_status = 0 THEN 1 ELSE 0 END) AS online,
            SUM(CASE WHEN last_communication < CURDATE() OR last_communication IS NULL OR login_status != 0 THEN 1 ELSE 0 END) AS offline,
            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS ping_ok,
            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS ping_failed,
            SUM(CASE WHEN login_status = 1 THEN 1 ELSE 0 END) AS login_failed,
            SUM(CASE WHEN network_status = 'working' THEN 1 ELSE 0 END) AS network_working,
            SUM(CASE WHEN network_status = 'not working' THEN 1 ELSE 0 END) AS network_not_working
        FROM all_dvr_live
        WHERE live = 'Y'
    ";

    $fleet_result = mysqli_query($con, $fleet_query);

    if ($fleet_result === false) {
        error_log('Analytics fleet query failed: ' . mysqli_error($con));
        http_response_code(500);
        echo json_encode(['Code' => 500, 'error' => 'Failed to fetch fleet overview.']);
        exit();
    }

    $response['fleet_overview'] = mysqli_fetch_assoc($fleet_result);
    mysqli_free_result($fleet_result);

    // ── B. DVR Brand Distribution ──────────────────────────────────────────
    $brand_query = "
        SELECT dvrname AS name, COUNT(*) AS value
        FROM all_dvr_live
        WHERE live = 'Y'
        GROUP BY dvrname
        ORDER BY value DESC
    ";

    $brand_result = mysqli_query($con, $brand_query);

    if ($brand_result === false) {
        error_log('Analytics brand query failed: ' . mysqli_error($con));
        http_response_code(500);
        echo json_encode(['Code' => 500, 'error' => 'Failed to fetch brand distribution.']);
        exit();
    }

    $response['dvr_brands'] = [];
    while ($row = mysqli_fetch_assoc($brand_result)) {
        $row['value'] = (int) $row['value'];
        $response['dvr_brands'][] = $row;
    }
    mysqli_free_result($brand_result);

    // ── C. Customer Distribution ───────────────────────────────────────────
    $customer_query = "
        SELECT customer AS name, COUNT(*) AS value
        FROM all_dvr_live
        WHERE live = 'Y' AND customer IS NOT NULL AND customer != ''
        GROUP BY customer
        ORDER BY value DESC
        LIMIT 8
    ";

    $customer_result = mysqli_query($con, $customer_query);

    if ($customer_result === false) {
        error_log('Analytics customer query failed: ' . mysqli_error($con));
        http_response_code(500);
        echo json_encode(['Code' => 500, 'error' => 'Failed to fetch customer distribution.']);
        exit();
    }

    $response['customers'] = [];
    while ($row = mysqli_fetch_assoc($customer_result)) {
        $row['value'] = (int) $row['value'];
        $response['customers'][] = $row;
    }
    mysqli_free_result($customer_result);

    // ── D. HDD Status Breakdown ────────────────────────────────────────────
    $hdd_query = "
        SELECT
            CASE
                WHEN LOWER(TRIM(hdd)) IN ('working', 'ok', 'normal', 'good') THEN 'Working'
                WHEN LOWER(TRIM(hdd)) IN ('not working', 'not ok', 'error', 'abnormal', 'no', 'no disk', 'notexist', 'not exist', 'unformatted', 'no disk/idle') THEN 'Not Working'
                WHEN hdd IS NULL OR TRIM(hdd) = '' OR LOWER(TRIM(hdd)) = 'n/a' THEN 'N/A'
                ELSE 'Other'
            END AS name,
            COUNT(*) AS value
        FROM all_dvr_live
        WHERE live = 'Y'
        GROUP BY name
        ORDER BY value DESC
    ";

    $hdd_result = mysqli_query($con, $hdd_query);

    if ($hdd_result === false) {
        error_log('Analytics HDD query failed: ' . mysqli_error($con));
        http_response_code(500);
        echo json_encode(['Code' => 500, 'error' => 'Failed to fetch HDD status.']);
        exit();
    }

    $response['hdd_status'] = [];
    while ($row = mysqli_fetch_assoc($hdd_result)) {
        $row['value'] = (int) $row['value'];
        $response['hdd_status'][] = $row;
    }
    mysqli_free_result($hdd_result);

    // ── E. Network Status ─────────────────────────────────────────────────
    $network_query = "
        SELECT
            CASE
                WHEN network_status = 'working' THEN 'Working'
                WHEN network_status = 'not working' THEN 'Not Working'
                ELSE 'Unknown'
            END AS name,
            COUNT(*) AS value
        FROM all_dvr_live
        WHERE live = 'Y'
        GROUP BY name
        ORDER BY value DESC
    ";

    $network_result = mysqli_query($con, $network_query);

    if ($network_result === false) {
        error_log('Analytics network query failed: ' . mysqli_error($con));
        http_response_code(500);
        echo json_encode(['Code' => 500, 'error' => 'Failed to fetch network status.']);
        exit();
    }

    $response['network_status'] = [];
    while ($row = mysqli_fetch_assoc($network_result)) {
        $row['value'] = (int) $row['value'];
        $response['network_status'][] = $row;
    }
    mysqli_free_result($network_result);

    // ── F. DVR Logs — Major Type Distribution ─────────────────────────────
    $logs_query = "
        SELECT major_type AS name, COUNT(*) AS value
        FROM dvr_fetched_logs
        WHERE major_type IS NOT NULL AND major_type != ''
        GROUP BY major_type
        ORDER BY value DESC
        LIMIT 10
    ";

    $logs_result = mysqli_query($con, $logs_query);

    if ($logs_result === false) {
        error_log('Analytics logs query failed: ' . mysqli_error($con));
        http_response_code(500);
        echo json_encode(['Code' => 500, 'error' => 'Failed to fetch log types.']);
        exit();
    }

    $response['log_types'] = [];
    while ($row = mysqli_fetch_assoc($logs_result)) {
        $row['value'] = (int) $row['value'];
        $response['log_types'][] = $row;
    }
    mysqli_free_result($logs_result);

    // ── G. DVR Brand in Logs ──────────────────────────────────────────────
    $log_brand_query = "
        SELECT dvrname AS name, COUNT(*) AS value
        FROM dvr_fetched_logs
        WHERE dvrname IS NOT NULL AND dvrname != ''
        GROUP BY dvrname
        ORDER BY value DESC
        LIMIT 8
    ";

    $log_brand_result = mysqli_query($con, $log_brand_query);

    if ($log_brand_result === false) {
        error_log('Analytics log brand query failed: ' . mysqli_error($con));
        http_response_code(500);
        echo json_encode(['Code' => 500, 'error' => 'Failed to fetch log brand distribution.']);
        exit();
    }

    $response['log_brands'] = [];
    while ($row = mysqli_fetch_assoc($log_brand_result)) {
        $row['value'] = (int) $row['value'];
        $response['log_brands'][] = $row;
    }
    mysqli_free_result($log_brand_result);

    // ── H. Footage Availability Status ───────────────────────────────────
    $footage_query = "
        SELECT status AS name, COUNT(*) AS value
        FROM footage_availability_report
        GROUP BY status
        ORDER BY value DESC
    ";

    $footage_result = mysqli_query($con, $footage_query);

    if ($footage_result === false) {
        error_log('Analytics footage query failed: ' . mysqli_error($con));
        http_response_code(500);
        echo json_encode(['Code' => 500, 'error' => 'Failed to fetch footage status.']);
        exit();
    }

    $response['footage_status'] = [];
    while ($row = mysqli_fetch_assoc($footage_result)) {
        $row['value'] = (int) $row['value'];
        $response['footage_status'][] = $row;
    }
    mysqli_free_result($footage_result);

    // ── I. Login Status per DVR Brand ─────────────────────────────────────
    $login_brand_query = "
        SELECT
            dvrname AS brand,
            SUM(CASE WHEN login_status = 0 THEN 1 ELSE 0 END) AS login_ok,
            SUM(CASE WHEN login_status = 1 THEN 1 ELSE 0 END) AS login_failed,
            COUNT(*) AS total
        FROM all_dvr_live
        WHERE live = 'Y' AND dvrname IS NOT NULL AND dvrname != ''
        GROUP BY dvrname
        ORDER BY total DESC
    ";

    $login_brand_result = mysqli_query($con, $login_brand_query);

    if ($login_brand_result === false) {
        error_log('Analytics login brand query failed: ' . mysqli_error($con));
        http_response_code(500);
        echo json_encode(['Code' => 500, 'error' => 'Failed to fetch login status per brand.']);
        exit();
    }

    $response['login_by_brand'] = [];
    while ($row = mysqli_fetch_assoc($login_brand_result)) {
        $response['login_by_brand'][] = $row;
    }
    mysqli_free_result($login_brand_result);

    // ── Success Response ──────────────────────────────────────────────────
    http_response_code(200);

    echo json_encode([
        'Code'    => 200,
        'message' => 'Analytics data fetched successfully.',
        'data'    => $response
    ]);

    exit();
}


// ==============================================================================
// Default: 400 Invalid Request
// ==============================================================================

http_response_code(400);

echo json_encode([
    'Code'    => 400,
    'message' => 'Invalid API request. Use: ?fetch_all=1'
]);

