<?php
// ==============================================================================
// DVR Dashboard Dropdown APIs (GET Method)
// File: Dashboard_dropdown_api.php
// ==============================================================================

include('config.php');

// Helper to escape string safely
function escape_param($con, $param) {
    return mysqli_real_escape_string($con, trim($param));
}

// ==============================================================================
// 1. Customer Dropdown API
// URL: api/Dashboard_dropdown_api.php?fetch_customer=1
// Alt: api/Dashboard_dropdown_api.php?fetch_customers=1 OR action=customer
// ==============================================================================
if (
    ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['fetch_customer']) || isset($_GET['fetch_customers']))) ||
    (isset($_REQUEST['action']) && in_array(strtolower($_REQUEST['action']), ['customer', 'customers', 'fetch_customer', 'fetch_customers']))
) {
    $where_condition = "WHERE customer IS NOT NULL AND customer != ''";

    // Optional filters
    if (!empty($_GET['bank'])) {
        $bank = escape_param($con, $_GET['bank']);
        $where_condition .= " AND bank = '$bank'";
    }
    if (isset($_GET['live']) && $_GET['live'] !== '') {
        $live = escape_param($con, $_GET['live']);
        $where_condition .= " AND live = '$live'";
    }

    // 1. Get total distinct count
    $count_query = "
        SELECT COUNT(DISTINCT customer) AS total_count
        FROM all_dvr_live
        $where_condition
    ";
    $count_result = mysqli_query($con, $count_query);

    if ($count_result === false) {
        error_log('Customer dropdown count query failed: ' . mysqli_error($con));
        http_response_code(500);
        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to fetch customer dropdown count.'
        ]);
        exit();
    }

    $count_row = mysqli_fetch_assoc($count_result);
    $total_count = $count_row ? (int)$count_row['total_count'] : 0;
    mysqli_free_result($count_result);

    // 2. Fetch distinct customers
    $data_query = "
        SELECT DISTINCT customer
        FROM all_dvr_live
        $where_condition
        ORDER BY customer ASC
    ";
    $data_result = mysqli_query($con, $data_query);

    if ($data_result === false) {
        error_log('Customer dropdown data query failed: ' . mysqli_error($con));
        http_response_code(500);
        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to fetch customer dropdown data.'
        ]);
        exit();
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($data_result)) {
        $data[] = $row;
    }

    if (mysqli_errno($con)) {
        error_log('Customer dropdown fetch error: ' . mysqli_error($con));
        mysqli_free_result($data_result);
        http_response_code(500);
        echo json_encode([
            'Code'  => 500,
            'error' => 'An error occurred while reading customer dropdown data.'
        ]);
        exit();
    }

    mysqli_free_result($data_result);

    http_response_code(200);
    echo json_encode([
        'Code'        => 200,
        'message'     => 'Customer dropdown data fetched successfully.',
        'total_count' => $total_count,
        'data'        => $data
    ]);
    exit();
}


// ==============================================================================
// 2. Bank Dropdown API
// URL: api/Dashboard_dropdown_api.php?fetch_bank=1
// Alt: api/Dashboard_dropdown_api.php?fetch_banks=1 OR action=bank
// ==============================================================================
if (
    ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['fetch_bank']) || isset($_GET['fetch_banks']))) ||
    (isset($_REQUEST['action']) && in_array(strtolower($_REQUEST['action']), ['bank', 'banks', 'fetch_bank', 'fetch_banks']))
) {
    $where_condition = "WHERE bank IS NOT NULL AND bank != ''";

    // Optional customer filter
    if (!empty($_GET['customer'])) {
        $customer = escape_param($con, $_GET['customer']);
        $where_condition .= " AND customer = '$customer'";
    }
    if (isset($_GET['live']) && $_GET['live'] !== '') {
        $live = escape_param($con, $_GET['live']);
        $where_condition .= " AND live = '$live'";
    }

    // 1. Get total distinct count
    $count_query = "
        SELECT COUNT(DISTINCT bank) AS total_count
        FROM all_dvr_live
        $where_condition
    ";
    $count_result = mysqli_query($con, $count_query);

    if ($count_result === false) {
        error_log('Bank dropdown count query failed: ' . mysqli_error($con));
        http_response_code(500);
        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to fetch bank dropdown count.'
        ]);
        exit();
    }

    $count_row = mysqli_fetch_assoc($count_result);
    $total_count = $count_row ? (int)$count_row['total_count'] : 0;
    mysqli_free_result($count_result);

    // 2. Fetch distinct banks
    $data_query = "
        SELECT DISTINCT bank
        FROM all_dvr_live
        $where_condition
        ORDER BY bank ASC
    ";
    $data_result = mysqli_query($con, $data_query);

    if ($data_result === false) {
        error_log('Bank dropdown data query failed: ' . mysqli_error($con));
        http_response_code(500);
        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to fetch bank dropdown data.'
        ]);
        exit();
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($data_result)) {
        $data[] = $row;
    }

    if (mysqli_errno($con)) {
        error_log('Bank dropdown fetch error: ' . mysqli_error($con));
        mysqli_free_result($data_result);
        http_response_code(500);
        echo json_encode([
            'Code'  => 500,
            'error' => 'An error occurred while reading bank dropdown data.'
        ]);
        exit();
    }

    mysqli_free_result($data_result);

    http_response_code(200);
    echo json_encode([
        'Code'        => 200,
        'message'     => 'Bank dropdown data fetched successfully.',
        'total_count' => $total_count,
        'data'        => $data
    ]);
    exit();
}


// ==============================================================================
// 3. DVR Name Dropdown API
// URL: api/Dashboard_dropdown_api.php?fetch_dvrname=1
// Alt: api/Dashboard_dropdown_api.php?fetch_dvr_name=1 OR action=dvrname
// ==============================================================================
if (
    ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['fetch_dvrname']) || isset($_GET['fetch_dvr_name']))) ||
    (isset($_REQUEST['action']) && in_array(strtolower($_REQUEST['action']), ['dvrname', 'dvr_name', 'fetch_dvrname', 'fetch_dvr_name']))
) {
    $where_condition = "WHERE dvrname IS NOT NULL AND dvrname != ''";

    // Optional filters
    if (!empty($_GET['customer'])) {
        $customer = escape_param($con, $_GET['customer']);
        $where_condition .= " AND customer = '$customer'";
    }
    if (!empty($_GET['bank'])) {
        $bank = escape_param($con, $_GET['bank']);
        $where_condition .= " AND bank = '$bank'";
    }
    if (isset($_GET['live']) && $_GET['live'] !== '') {
        $live = escape_param($con, $_GET['live']);
        $where_condition .= " AND live = '$live'";
    }

    // 1. Get total distinct count
    $count_query = "
        SELECT COUNT(DISTINCT dvrname) AS total_count
        FROM all_dvr_live
        $where_condition
    ";
    $count_result = mysqli_query($con, $count_query);

    if ($count_result === false) {
        error_log('DVR Name dropdown count query failed: ' . mysqli_error($con));
        http_response_code(500);
        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to fetch DVR name dropdown count.'
        ]);
        exit();
    }

    $count_row = mysqli_fetch_assoc($count_result);
    $total_count = $count_row ? (int)$count_row['total_count'] : 0;
    mysqli_free_result($count_result);

    // 2. Fetch distinct DVR names
    $data_query = "
        SELECT DISTINCT dvrname
        FROM all_dvr_live
        $where_condition
        ORDER BY dvrname ASC
    ";
    $data_result = mysqli_query($con, $data_query);

    if ($data_result === false) {
        error_log('DVR Name dropdown data query failed: ' . mysqli_error($con));
        http_response_code(500);
        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to fetch DVR name dropdown data.'
        ]);
        exit();
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($data_result)) {
        $data[] = $row;
    }

    if (mysqli_errno($con)) {
        error_log('DVR Name dropdown fetch error: ' . mysqli_error($con));
        mysqli_free_result($data_result);
        http_response_code(500);
        echo json_encode([
            'Code'  => 500,
            'error' => 'An error occurred while reading DVR name dropdown data.'
        ]);
        exit();
    }

    mysqli_free_result($data_result);

    http_response_code(200);
    echo json_encode([
        'Code'        => 200,
        'message'     => 'DVR name dropdown data fetched successfully.',
        'total_count' => $total_count,
        'data'        => $data
    ]);
    exit();
}


// ==============================================================================
// 4. ATM ID Dropdown API
// URL: api/Dashboard_dropdown_api.php?fetch_atmid=1
// Alt: api/Dashboard_dropdown_api.php?fetch_atm_id=1 OR action=atmid / action=atm_id
// Note: In table `all_dvr_live`, the column name is `atmid`. We also alias it as `atm_id`.
// ==============================================================================
if (
    ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['fetch_atmid']) || isset($_GET['fetch_atm_id']))) ||
    (isset($_REQUEST['action']) && in_array(strtolower($_REQUEST['action']), ['atmid', 'atm_id', 'fetch_atmid', 'fetch_atm_id']))
) {
    $where_condition = "WHERE atmid IS NOT NULL AND atmid != ''";

    // Optional customer, bank, dvrname, or search filter
    if (!empty($_GET['customer'])) {
        $customer = escape_param($con, $_GET['customer']);
        $where_condition .= " AND customer = '$customer'";
    }
    if (!empty($_GET['bank'])) {
        $bank = escape_param($con, $_GET['bank']);
        $where_condition .= " AND bank = '$bank'";
    }
    if (!empty($_GET['dvrname'])) {
        $dvrname = escape_param($con, $_GET['dvrname']);
        $where_condition .= " AND dvrname = '$dvrname'";
    }
    if (!empty($_GET['search'])) {
        $search = escape_param($con, $_GET['search']);
        $where_condition .= " AND atmid LIKE '%$search%'";
    }
    if (isset($_GET['live']) && $_GET['live'] !== '') {
        $live = escape_param($con, $_GET['live']);
        $where_condition .= " AND live = '$live'";
    }

    // 1. Get total distinct count
    $count_query = "
        SELECT COUNT(DISTINCT atmid) AS total_count
        FROM all_dvr_live
        $where_condition
    ";
    $count_result = mysqli_query($con, $count_query);

    if ($count_result === false) {
        error_log('ATM ID dropdown count query failed: ' . mysqli_error($con));
        http_response_code(500);
        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to fetch ATM ID dropdown count.'
        ]);
        exit();
    }

    $count_row = mysqli_fetch_assoc($count_result);
    $total_count = $count_row ? (int)$count_row['total_count'] : 0;
    mysqli_free_result($count_result);

    // 2. Fetch distinct ATM IDs (returns both atmid and atm_id for compatibility)
    $data_query = "
        SELECT DISTINCT atmid, atmid AS atm_id
        FROM all_dvr_live
        $where_condition
        ORDER BY atmid ASC
    ";
    $data_result = mysqli_query($con, $data_query);

    if ($data_result === false) {
        error_log('ATM ID dropdown data query failed: ' . mysqli_error($con));
        http_response_code(500);
        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to fetch ATM ID dropdown data.'
        ]);
        exit();
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($data_result)) {
        $data[] = $row;
    }

    if (mysqli_errno($con)) {
        error_log('ATM ID dropdown fetch error: ' . mysqli_error($con));
        mysqli_free_result($data_result);
        http_response_code(500);
        echo json_encode([
            'Code'  => 500,
            'error' => 'An error occurred while reading ATM ID dropdown data.'
        ]);
        exit();
    }

    mysqli_free_result($data_result);

    http_response_code(200);
    echo json_encode([
        'Code'        => 200,
        'message'     => 'ATM ID dropdown data fetched successfully.',
        'total_count' => $total_count,
        'data'        => $data
    ]);
    exit();
}


// ==============================================================================
// 5. All Dropdowns in Single Request
// URL: api/Dashboard_dropdown_api.php?fetch_all_dropdowns=1
// Alt: action=all_dropdowns OR action=all
// ==============================================================================
if (
    ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_all_dropdowns'])) ||
    (isset($_REQUEST['action']) && in_array(strtolower($_REQUEST['action']), ['all_dropdowns', 'fetch_all_dropdowns', 'all']))
) {
    // 1. Customers
    $cust_res = mysqli_query($con, "SELECT DISTINCT customer FROM all_dvr_live WHERE customer IS NOT NULL AND customer != '' ORDER BY customer ASC");
    $customers = [];
    while ($r = mysqli_fetch_assoc($cust_res)) {
        $customers[] = $r['customer'];
    }
    mysqli_free_result($cust_res);

    // 2. Banks
    $bank_res = mysqli_query($con, "SELECT DISTINCT bank FROM all_dvr_live WHERE bank IS NOT NULL AND bank != '' ORDER BY bank ASC");
    $banks = [];
    while ($r = mysqli_fetch_assoc($bank_res)) {
        $banks[] = $r['bank'];
    }
    mysqli_free_result($bank_res);

    // 3. DVR Names
    $dvr_res = mysqli_query($con, "SELECT DISTINCT dvrname FROM all_dvr_live WHERE dvrname IS NOT NULL AND dvrname != '' ORDER BY dvrname ASC");
    $dvrnames = [];
    while ($r = mysqli_fetch_assoc($dvr_res)) {
        $dvrnames[] = $r['dvrname'];
    }
    mysqli_free_result($dvr_res);

    // 4. ATM IDs
    $atm_res = mysqli_query($con, "SELECT DISTINCT atmid FROM all_dvr_live WHERE atmid IS NOT NULL AND atmid != '' ORDER BY atmid ASC");
    $atmids = [];
    while ($r = mysqli_fetch_assoc($atm_res)) {
        $atmids[] = $r['atmid'];
    }
    mysqli_free_result($atm_res);

    http_response_code(200);
    echo json_encode([
        'Code'    => 200,
        'message' => 'All dropdown lists fetched successfully.',
        'data'    => [
            'customers' => $customers,
            'banks'     => $banks,
            'dvrnames'  => $dvrnames,
            'atmids'    => $atmids
        ]
    ]);
    exit();
}


// ==============================================================================
// Default: API Help & Available Endpoints Listing
// ==============================================================================
http_response_code(200);
echo json_encode([
    'Code'      => 200,
    'message'   => 'SAR DVR Dashboard Dropdown API Engine (GET Method)',
    'endpoints' => [
        'fetch_customer'      => 'api/Dashboard_dropdown_api.php?fetch_customer=1',
        'fetch_bank'          => 'api/Dashboard_dropdown_api.php?fetch_bank=1',
        'fetch_dvrname'       => 'api/Dashboard_dropdown_api.php?fetch_dvrname=1',
        'fetch_atmid'         => 'api/Dashboard_dropdown_api.php?fetch_atmid=1',
        'fetch_all_dropdowns' => 'api/Dashboard_dropdown_api.php?fetch_all_dropdowns=1'
    ],
    'optional_filters' => [
        'customer' => 'Filter by customer name (e.g. ?fetch_bank=1&customer=Hitachi)',
        'bank'     => 'Filter by bank name',
        'live'     => 'Filter by live status (e.g. ?fetch_customer=1&live=Y)',
        'search'   => 'Search text for ATM IDs (e.g. ?fetch_atmid=1&search=IOB)'
    ]
]);
exit();