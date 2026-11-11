<?php
// ==============================================================================
// Complete DVR Dashboard APIs (GET Method)
// ==============================================================================

include('config.php');

// ==============================================================================
// 1. All Sites
// URL: api/dashboard_api.php?fetch_all_sites=1
// ==============================================================================
if (($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_all_sites']))) {

    // Fetch total live sites count
    $count_query = "
        SELECT COUNT(atmid) AS total_count
        FROM `all_dvr_live`
        WHERE live = 'Y'
    ";

    $count_query_result = mysqli_query($con, $count_query);

    // Check count query error
    if (!$count_query_result) {
        http_response_code(500);

        echo json_encode([
            'Code' => 500,
            'error' => 'Failed to fetch total sites count.',
            'details' => mysqli_error($con)
        ]);
        exit();
    }

    // Get actual count value
    $count_row = mysqli_fetch_assoc($count_query_result);

    if ($count_row === null || $count_row === false) {
        http_response_code(500);

        echo json_encode([
            'Code' => 500,
            'error' => 'Failed to retrieve total sites count.'
        ]);
        exit();
    }

    $total_count = (int) $count_row['total_count'];

    // Fetch all live sites
    $data_query = "
        SELECT *
        FROM `all_dvr_live`
        WHERE live = 'Y'
    ";

    $data_result = mysqli_query($con, $data_query);

    // Check data query error
    if (!$data_result) {
        http_response_code(500);

        echo json_encode([
            'Code' => 500,
            'error' => 'Failed to fetch live sites.',
            'details' => mysqli_error($con)
        ]);
        exit();
    }

    // Fetch records
    $data = [];

    while ($row = mysqli_fetch_assoc($data_result)) {

        // DVR port mapping (case-insensitive)
            $dvr_name = strtolower(trim($row['dvrname'] ?? ''));

            if (in_array($dvr_name, ['cpplus', 'cpplus_orange', 'cpplus_indigo'], true)) {
                $row['refresh_url'] = '8005/refresh/cpplus_dahuva';

            } elseif ($dvr_name === 'hikvision') {
                $row['refresh_url'] = '8006/refresh/hikvision';

            } elseif ($dvr_name === 'hikvision_nvr') {
                $row['refresh_url'] = '8004/refresh/hikvision_nvr';

            } elseif (in_array($dvr_name, ['dahuva', 'dahua', 'dhaua', 'dhauva'], true)) {
                $row['refresh_url'] = '8005/refresh/cpplus_dahuva';

            } elseif (in_array($dvr_name, ['unv', 'uniview'], true)) {
                $row['refresh_url'] = '8003/refresh/unv';
            }

        $data[] = $row;
    }

    // Check for fetch errors
    if (mysqli_errno($con)) {
        http_response_code(500);

        echo json_encode([
            'Code' => 500,
            'error' => 'An error occurred while fetching site records.',
            'details' => mysqli_error($con)
        ]);
        exit();
    }

    // Success response
    http_response_code(200);

    echo json_encode([
        'Code' => 200,
        'message' => 'Live sites fetched successfully.',
        'total_count' => $total_count,
        'data' => $data
    ]);

    mysqli_free_result($count_query_result);
    mysqli_free_result($data_result);

    exit();
}

// ==============================================================================
// 2. Online Sites
// URL: api/dashboard_api.php?fetch_online_sites=1
// ==============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_online_sites'])) {

    // Common condition for online sites
    $where_condition = "
        WHERE live = 'Y'
          AND last_communication >= CURDATE()
          AND login_status = 0
    ";

    // 1. Get total online sites count
    $count_query = "
        SELECT COUNT(atmid) AS total_count
        FROM all_dvr_live
        $where_condition
    ";

    $count_result = mysqli_query($con, $count_query);

    // Check count query error
    if ($count_result === false) {
        error_log('Online sites count query failed: ' . mysqli_error($con));

        http_response_code(500);

        echo json_encode([
            'Code' => 500,
            'error' => 'Failed to fetch online sites count.'
        ]);
        exit();
    }

    // Fetch actual count
    $count_row = mysqli_fetch_assoc($count_result);

    if ($count_row === false) {
        error_log('Failed to fetch online sites count result: ' . mysqli_error($con));

        mysqli_free_result($count_result);

        http_response_code(500);

        echo json_encode([
            'Code' => 500,
            'error' => 'Failed to retrieve online sites count.'
        ]);
        exit();
    }

    $total_count = (int) $count_row['total_count'];

    // 2. Fetch online sites data
    $data_query = "
        SELECT *
        FROM all_dvr_live
        $where_condition
    ";

    $data_result = mysqli_query($con, $data_query);

    // Check data query error
    if ($data_result === false) {
        error_log('Online sites data query failed: ' . mysqli_error($con));

        mysqli_free_result($count_result);

        http_response_code(500);

        echo json_encode([
            'Code' => 500,
            'error' => 'Failed to fetch online sites data.'
        ]);
        exit();
    }

    // 3. Fetch records
    $data = [];

    while ($row = mysqli_fetch_assoc($data_result)) {

        
       // DVR port mapping (case-insensitive)
        $dvr_name = strtolower(trim($row['dvrname'] ?? ''));

        if (in_array($dvr_name, ['cpplus', 'cpplus_orange', 'cpplus_indigo'], true)) {
            $row['refresh_url'] = '8005/refresh/cpplus_dahuva';

        } elseif ($dvr_name === 'hikvision') {
            $row['refresh_url'] = '8006/refresh/hikvision';

        } elseif ($dvr_name === 'hikvision_nvr') {
            $row['refresh_url'] = '8004/refresh/hikvision_nvr';

        } elseif (in_array($dvr_name, ['dahuva', 'dahua', 'dhaua', 'dhauva'], true)) {
            $row['refresh_url'] = '8005/refresh/cpplus_dahuva';

        } elseif (in_array($dvr_name, ['unv', 'uniview'], true)) {
            $row['refresh_url'] = '8003/refresh/unv';
        }

        $data[] = $row;
    }

    // Check for fetch errors
    if (mysqli_errno($con)) {
        error_log('Online sites fetch failed: ' . mysqli_error($con));

        mysqli_free_result($count_result);
        mysqli_free_result($data_result);

        http_response_code(500);

        echo json_encode([
            'Code' => 500,
            'error' => 'An error occurred while reading online sites.'
        ]);
        exit();
    }

    // 4. Success response
    mysqli_free_result($count_result);
    mysqli_free_result($data_result);

    http_response_code(200);

    echo json_encode([
        'Code' => 200,
        'message' => 'Online sites fetched successfully.',
        'total_count' => $total_count,
        'data' => $data
    ]);

    exit();
}


// ==============================================================================
// 3. Offline Sites
// URL: api/dashboard_api.php?fetch_offline_sites=1
// ==============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_offline_sites'])) {

    // Common condition for offline sites
    $where_condition = "
    WHERE live = 'Y'
      AND (
            last_communication < CURDATE()
            OR last_communication IS NULL
            OR login_status != 0
            OR login_status IS NULL
      )
";


    // 2. Get total offline sites count
    $count_query = "
        SELECT COUNT(atmid) AS total_count
        FROM all_dvr_live
        $where_condition
    ";

    $count_result = mysqli_query($con, $count_query);

    // Check count query error
    if ($count_result === false) {
        error_log('Offline sites count query failed: ' . mysqli_error($con));

        http_response_code(500);

        echo json_encode([
            'Code' => 500,
            'error' => 'Failed to fetch offline sites count.'
        ]);
        exit();
    }

    // Fetch actual count
    $count_row = mysqli_fetch_assoc($count_result);

    if ($count_row === false) {
        error_log('Offline sites count fetch failed: ' . mysqli_error($con));

        mysqli_free_result($count_result);

        http_response_code(500);

        echo json_encode([
            'Code' => 500,
            'error' => 'Failed to retrieve offline sites count.'
        ]);
        exit();
    }

    $total_count = (int) $count_row['total_count'];

    // 3. Fetch offline sites data
    $data_query = "
        SELECT *
        FROM all_dvr_live
        $where_condition
    ";

    $data_result = mysqli_query($con, $data_query);

    // Check data query error
    if ($data_result === false) {
        error_log('Offline sites data query failed: ' . mysqli_error($con));

        mysqli_free_result($count_result);

        http_response_code(500);

        echo json_encode([
            'Code' => 500,
            'error' => 'Failed to fetch offline sites data.'
        ]);
        exit();
    }

    // 4. Fetch records
    $data = [];

    while ($row = mysqli_fetch_assoc($data_result)) {

       // DVR port mapping (case-insensitive)
        $dvr_name = strtolower(trim($row['dvrname'] ?? ''));

        if (in_array($dvr_name, ['cpplus', 'cpplus_orange', 'cpplus_indigo'], true)) {
            $row['refresh_url'] = '8005/refresh/cpplus_dahuva';

        } elseif ($dvr_name === 'hikvision') {
            $row['refresh_url'] = '8006/refresh/hikvision';

        } elseif ($dvr_name === 'hikvision_nvr') {
            $row['refresh_url'] = '8004/refresh/hikvision_nvr';

        } elseif (in_array($dvr_name, ['dahuva', 'dahua', 'dhaua', 'dhauva'], true)) {
            $row['refresh_url'] = '8005/refresh/cpplus_dahuva';

        } elseif (in_array($dvr_name, ['unv', 'uniview'], true)) {
            $row['refresh_url'] = '8003/refresh/unv';
        }

        $data[] = $row;
    }

    // Check fetch errors
    if (mysqli_errno($con)) {
        error_log('Offline sites fetch error: ' . mysqli_error($con));

        mysqli_free_result($count_result);
        mysqli_free_result($data_result);

        http_response_code(500);

        echo json_encode([
            'Code' => 500,
            'error' => 'An error occurred while reading offline sites.'
        ]);
        exit();
    }

    // 5. Free query results
    mysqli_free_result($count_result);
    mysqli_free_result($data_result);

    // 6. Success response
    http_response_code(200);

    echo json_encode([
        'Code' => 200,
        'message' => 'Offline sites fetched successfully.',
        'total_count' => $total_count,
        'data' => $data
    ]);

    exit();
}



// ==============================================================================
// 4. DVR Login Failed
// URL: api/dashboard_api.php?fetch_login_failed=1
// ==============================================================================

if ((    $_SERVER['REQUEST_METHOD'] === 'GET'   && isset($_GET['fetch_login_failed']))) {

    
    // Common condition for login-failed DVRs
    $where_condition = "
        WHERE live = 'Y'
          AND login_status = 1
          AND (
                last_communication < CURDATE()
                OR last_communication IS NULL
          )
          AND status = 1
    ";

    // 2. Get total login-failed DVR count
    $count_query = "
        SELECT COUNT(atmid) AS total_count
        FROM all_dvr_live
        $where_condition
    ";

    $count_result = mysqli_query($con, $count_query);

    // Check count query error
    if ($count_result === false) {
        error_log(
            'Login failed count query error: ' . mysqli_error($con)
        );

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to fetch login-failed DVR count.'
        ]);
        exit();
    }

    // Fetch total count
    $count_row = mysqli_fetch_assoc($count_result);

    if ($count_row === false) {
        error_log(
            'Login failed count fetch error: ' . mysqli_error($con)
        );

        mysqli_free_result($count_result);

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to retrieve login-failed DVR count.'
        ]);
        exit();
    }

    $total_count = (int) $count_row['total_count'];

    // 3. Fetch login-failed DVR records
    $data_query = "
        SELECT *
        FROM all_dvr_live
        $where_condition
    ";

    $data_result = mysqli_query($con, $data_query);

    // Check data query error
    if ($data_result === false) {
        error_log(
            'Login failed data query error: ' . mysqli_error($con)
        );

        mysqli_free_result($count_result);

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to fetch login-failed DVR data.'
        ]);
        exit();
    }

    // 4. Fetch records
    $data = [];

    while ($row = mysqli_fetch_assoc($data_result)) {
        // DVR port mapping (case-insensitive)
        $dvr_name = strtolower(trim($row['dvrname'] ?? ''));

        if (in_array($dvr_name, ['cpplus', 'cpplus_orange', 'cpplus_indigo'], true)) {
            $row['refresh_url'] = '8005/refresh/cpplus_dahuva';

        } elseif ($dvr_name === 'hikvision') {
            $row['refresh_url'] = '8006/refresh/hikvision';

        } elseif ($dvr_name === 'hikvision_nvr') {
            $row['refresh_url'] = '8004/refresh/hikvision_nvr';

        } elseif (in_array($dvr_name, ['dahuva', 'dahua', 'dhaua', 'dhauva'], true)) {
            $row['refresh_url'] = '8005/refresh/cpplus_dahuva';

        } elseif (in_array($dvr_name, ['unv', 'uniview'], true)) {
            $row['refresh_url'] = '8003/refresh/unv';
        }
        $data[] = $row;
    }

    // Check data fetching errors
    if (mysqli_errno($con)) {
        error_log(
            'Login failed data fetch error: ' . mysqli_error($con)
        );

        mysqli_free_result($count_result);
        mysqli_free_result($data_result);

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'An error occurred while reading login-failed DVR data.'
        ]);
        exit();
    }

    // 5. Release query results
    mysqli_free_result($count_result);
    mysqli_free_result($data_result);

    // 6. Return successful response
    http_response_code(200);

    echo json_encode([
        'Code'        => 200,
        'message'     => 'Login-failed DVRs fetched successfully.',
        'total_count' => $total_count,
        'data'        => $data
    ]);

    exit();
}


// ==============================================================================
// 5. HDD Not Working
// URL: api/dashboard_api.php?fetch_hdd_not_working=1
// ==============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_hdd_not_working'])) 
 {


    // Common condition for HDD not working
    $where_condition = "
        WHERE live = 'Y'
          AND (
                last_communication >= CURDATE()
                OR hsk_status = 'working'
          )
          AND hdd IN (
                'abnormal',
                'not working',
                'Error',
                'No',
                'No Disk',
                'No disk/idle',
                'Not Exist',
                'not ok',
                'notexist',
                'unformatted'
          )
    ";

    // 2. Get total HDD not working count
    $count_query = "
        SELECT COUNT(atmid) AS total_count
        FROM all_dvr_live
        $where_condition
    ";

    $count_result = mysqli_query($con, $count_query);

    // Check count query error
    if ($count_result === false) {
        error_log(
            'HDD not working count query failed: ' . mysqli_error($con)
        );

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to fetch HDD not working count.'
        ]);
        exit();
    }

    // Fetch total count
    $count_row = mysqli_fetch_assoc($count_result);

    if ($count_row === false) {
        error_log(
            'HDD count fetch failed: ' . mysqli_error($con)
        );

        mysqli_free_result($count_result);

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to retrieve HDD not working count.'
        ]);
        exit();
    }

    $total_count = (int) $count_row['total_count'];

    // 3. Fetch HDD not working records
    $data_query = "
        SELECT *
        FROM all_dvr_live
        $where_condition
    ";

    $data_result = mysqli_query($con, $data_query);

    // Check data query error
    if ($data_result === false) {
        error_log(
            'HDD not working data query failed: ' . mysqli_error($con)
        );

        mysqli_free_result($count_result);

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to fetch HDD not working data.'
        ]);
        exit();
    }

    // 4. Fetch records
    $data = [];

    while ($row = mysqli_fetch_assoc($data_result)) {
        // DVR port mapping (case-insensitive)
            $dvr_name = strtolower(trim($row['dvrname'] ?? ''));

            if (in_array($dvr_name, ['cpplus', 'cpplus_orange', 'cpplus_indigo'], true)) {
                $row['refresh_url'] = '8005/refresh/cpplus_dahuva';

            } elseif ($dvr_name === 'hikvision') {
                $row['refresh_url'] = '8006/refresh/hikvision';

            } elseif ($dvr_name === 'hikvision_nvr') {
                $row['refresh_url'] = '8004/refresh/hikvision_nvr';

            } elseif (in_array($dvr_name, ['dahuva', 'dahua', 'dhaua', 'dhauva'], true)) {
                $row['refresh_url'] = '8005/refresh/cpplus_dahuva';

            } elseif (in_array($dvr_name, ['unv', 'uniview'], true)) {
                $row['refresh_url'] = '8003/refresh/unv';
            }
        $data[] = $row;
    }

    // Check for fetch errors
    if (mysqli_errno($con)) {
        error_log(
            'HDD not working data fetch failed: ' . mysqli_error($con)
        );

        mysqli_free_result($count_result);
        mysqli_free_result($data_result);

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'An error occurred while reading HDD data.'
        ]);
        exit();
    }

    // 5. Free query results
    mysqli_free_result($count_result);
    mysqli_free_result($data_result);

    // 6. Success response
    http_response_code(200);

    echo json_encode([
        'Code'        => 200,
        'message'     => 'HDD not working data fetched successfully.',
        'total_count' => $total_count,
        'data'        => $data
    ]);

    exit();
}




// ==============================================================================
// 6. PING OK and not 
// URL: api/dashboard_api.php?fetch_ping_ok=1
// ==============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_ping_ok'])) 
{

    // Common condition for ping OK sites
    $where_condition = "
        WHERE status = 1
          AND live = 'Y'
    ";

    // 2. Get total ping OK sites count
    $count_query = "
        SELECT COUNT(atmid) AS total_count
        FROM all_dvr_live
        $where_condition
    ";

    $count_result = mysqli_query($con, $count_query);

    // Check count query error
    if ($count_result === false) {
        error_log(
            'Ping OK count query failed: ' . mysqli_error($con)
        );

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to fetch ping OK sites count.'
        ]);
        exit();
    }

    // Fetch actual count
    $count_row = mysqli_fetch_assoc($count_result);

    if ($count_row === false) {
        error_log(
            'Ping OK count fetch failed: ' . mysqli_error($con)
        );

        mysqli_free_result($count_result);

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to retrieve ping OK sites count.'
        ]);
        exit();
    }

    $total_count = (int) $count_row['total_count'];

    // 3. Fetch ping OK sites
    $data_query = "
        SELECT *
        FROM all_dvr_live
        $where_condition
    ";

    $data_result = mysqli_query($con, $data_query);

    // Check data query error
    if ($data_result === false) {
        error_log(
            'Ping OK data query failed: ' . mysqli_error($con)
        );

        mysqli_free_result($count_result);

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to fetch ping OK sites data.'
        ]);
        exit();
    }

    // 4. Fetch records
    $data = [];

    while ($row = mysqli_fetch_assoc($data_result)) {

         
        // DVR port mapping (case-insensitive)
        $dvr_name = strtolower(trim($row['dvrname'] ?? ''));

        if (in_array($dvr_name, ['cpplus', 'cpplus_orange', 'cpplus_indigo'], true)) {
            $row['refresh_url'] = '8005/refresh/cpplus_dahuva';

        } elseif ($dvr_name === 'hikvision') {
            $row['refresh_url'] = '8006/refresh/hikvision';

        } elseif ($dvr_name === 'hikvision_nvr') {
            $row['refresh_url'] = '8004/refresh/hikvision_nvr';

        } elseif (in_array($dvr_name, ['dahuva', 'dahua', 'dhaua', 'dhauva'], true)) {
            $row['refresh_url'] = '8005/refresh/cpplus_dahuva';

        } elseif (in_array($dvr_name, ['unv', 'uniview'], true)) {
            $row['refresh_url'] = '8003/refresh/unv';
        }
        $data[] = $row;
    }

    // Check for fetch errors
    if (mysqli_errno($con)) {
        error_log(
            'Ping OK data fetch failed: ' . mysqli_error($con)
        );

        mysqli_free_result($count_result);
        mysqli_free_result($data_result);

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'An error occurred while reading ping OK sites data.'
        ]);
        exit();
    }

    // 5. Free query results
    mysqli_free_result($count_result);
    mysqli_free_result($data_result);

    // 6. Success response
    http_response_code(200);

    echo json_encode([
        'Code'        => 200,
        'message'     => 'Ping OK sites fetched successfully.',
        'total_count' => $total_count,
        'data'        => $data
    ]);

    exit();
}


// ==============================================================================
// 7. PING Failed  
// URL: api/dashboard_api.php?fetch_ping_failed=1
// ==============================================================================

if ( $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_ping_failed'])) {

    
    // Common condition for ping failed sites
    $where_condition = "
        WHERE status = 0
          AND live = 'Y'
    ";

    // 2. Get total ping failed sites count
    $count_query = "
        SELECT COUNT(atmid) AS total_count
        FROM all_dvr_live
        $where_condition
    ";

    $count_result = mysqli_query($con, $count_query);

    // Check count query error
    if ($count_result === false) {
        error_log(
            'Ping failed count query error: ' . mysqli_error($con)
        );

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to fetch ping failed sites count.'
        ]);
        exit();
    }

    // Fetch actual count
    $count_row = mysqli_fetch_assoc($count_result);

    if ($count_row === false) {
        error_log(
            'Ping failed count fetch error: ' . mysqli_error($con)
        );

        mysqli_free_result($count_result);

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to retrieve ping failed sites count.'
        ]);
        exit();
    }

    $total_count = (int) $count_row['total_count'];

    // 3. Fetch ping failed sites data
    $data_query = "
        SELECT *
        FROM all_dvr_live
        $where_condition
    ";

    $data_result = mysqli_query($con, $data_query);

    // Check data query error
    if ($data_result === false) {
        error_log(
            'Ping failed data query error: ' . mysqli_error($con)
        );

        mysqli_free_result($count_result);

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to fetch ping failed sites data.'
        ]);
        exit();
    }

    // 4. Fetch records
    $data = [];

    while ($row = mysqli_fetch_assoc($data_result)) {
        $data[] = $row;
    }

    // Check for fetch errors
    if (mysqli_errno($con)) {
        error_log(
            'Ping failed data fetch error: ' . mysqli_error($con)
        );

        mysqli_free_result($count_result);
        mysqli_free_result($data_result);

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'An error occurred while reading ping failed sites data.'
        ]);
        exit();
    }

    // 5. Free query results
    mysqli_free_result($count_result);
    mysqli_free_result($data_result);

    // 6. Success response
    http_response_code(200);

    echo json_encode([
        'Code'        => 200,
        'message'     => 'Ping failed sites fetched successfully.',
        'total_count' => $total_count,
        'data'        => $data
    ]);

    exit();
}





// ==============================================================================
// 8. DVR Time Difference — Online Sites
// URL: api/dashboard_api.php?fetch_time_diff_online=1
// ==============================================================================

if (($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_time_diff_online']))) {


    // Common condition for online DVRs with time difference > 180 seconds
    $where_condition = "
        WHERE live = 'Y'
          AND (
                last_communication >= CURDATE()
                OR hsk_status = 'working'
          )
          AND TIME_TO_SEC(REPLACE(time_diff, '+', '')) > 180
    ";

    // 2. Get total count
    $count_query = "
        SELECT COUNT(atmid) AS total_count
        FROM all_dvr_live
        $where_condition
    ";

    $count_result = mysqli_query($con, $count_query);

    // Check count query error
    if ($count_result === false) {
        error_log(
            'Time difference count query failed: ' . mysqli_error($con)
        );

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to fetch time difference count.'
        ]);
        exit();
    }

    // Fetch actual count
    $count_row = mysqli_fetch_assoc($count_result);

    if ($count_row === false) {
        error_log(
            'Time difference count fetch failed: ' . mysqli_error($con)
        );

        mysqli_free_result($count_result);

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to retrieve time difference count.'
        ]);
        exit();
    }

    $total_count = (int) $count_row['total_count'];

    // 3. Fetch matching DVR records
    $data_query = "
        SELECT *
        FROM all_dvr_live
        $where_condition
    ";

    $data_result = mysqli_query($con, $data_query);

    // Check data query error
    if ($data_result === false) {
        error_log(
            'Time difference data query failed: ' . mysqli_error($con)
        );

        mysqli_free_result($count_result);

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'Failed to fetch time difference data.'
        ]);
        exit();
    }

    // 4. Fetch records
    $data = [];

    while ($row = mysqli_fetch_assoc($data_result)) {
        $data[] = $row;
    }

    // Check for fetch errors
    if (mysqli_errno($con)) {
        error_log(
            'Time difference data fetch failed: ' . mysqli_error($con)
        );

        mysqli_free_result($count_result);
        mysqli_free_result($data_result);

        http_response_code(500);

        echo json_encode([
            'Code'  => 500,
            'error' => 'An error occurred while reading time difference data.'
        ]);
        exit();
    }

    // 5. Free query results
    mysqli_free_result($count_result);
    mysqli_free_result($data_result);

    // 6. Success response
    http_response_code(200);

    echo json_encode([
        'Code'        => 200,
        'message'     => 'Online DVR time difference data fetched successfully.',
        'total_count' => $total_count,
        'data'        => $data
    ]);

    exit();
}


// ==============================================================================
// 9. DVR Time Difference — Offline Sites
// URL: api/dashboard_api.php?fetch_time_diff_offline=1
// ==============================================================================
if (($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_time_diff_offline'])) || (isset($_REQUEST['action']) && $_REQUEST['action'] === 'time_diff_offline')) {

    $query = "SELECT *
              FROM all_dvr_live
              WHERE live = 'Y'
                AND (
                    (
                        last_communication < CURDATE()
                        OR last_communication IS NULL
                    )
                    AND hsk_status <> 'working'
                )
                AND TIME_TO_SEC(REPLACE(time_diff, '+', '')) > 180";
    $result = mysqli_query($con, $query);
    $data = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    echo json_encode([
        'Code' => 200,
        'data' => $data,
    ]);
    exit();
}





// ==============================================================================
// 10. Online Network Count
// URL: api/dashboard_api.php?fetch_online_network_count=1
// ==============================================================================
if (($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_online_network_count'])) || (isset($_REQUEST['action']) && $_REQUEST['action'] === 'online_network_count')) {

    $query = "SELECT
                (
                    SELECT COUNT(*)
                    FROM all_dvr_live
                    WHERE live = 'Y'
                      AND (
                          last_communication >= CURDATE()
                          OR hsk_status = 'working'
                      )
                )
                +
                (
                    SELECT COUNT(*)
                    FROM all_dvr_live
                    WHERE live = 'Y'
                      AND network_status = 'working'
                      AND (
                          last_communication < CURDATE()
                          OR last_communication IS NULL
                      )
                      AND hsk_status <> 'working'
                ) AS total_rows";
    $result = mysqli_query($con, $query);
    $data = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    echo json_encode([
        'Code' => 200,
        'data' => $data,
    ]);
    exit();
}

// ==============================================================================
// 11. Offline — Network Not Working
// URL: api/dashboard_api.php?fetch_offline_network_not_working=1
// ==============================================================================
if (($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_offline_network_not_working'])) || (isset($_REQUEST['action']) && $_REQUEST['action'] === 'offline_network_not_working')) {

    $query = "SELECT *
              FROM all_dvr_live
              WHERE live = 'Y'
                AND network_status = 'not working'
                AND (
                    last_communication < CURDATE()
                    OR last_communication IS NULL
                )
                AND (
                    hsk_status <> 'working'
                    OR hsk_status IS NULL
                )";
    $result = mysqli_query($con, $query);
    $data = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    echo json_encode([
        'Code' => 200,
        'data' => $data,
    ]);
    exit();
}


// ==============================================================================
// 12. Duplicate Sites — ATM ID ya IP Address
// URL: api/dashboard_api.php?fetch_duplicate_sites=1
// ==============================================================================
if (($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_duplicate_sites'])) || (isset($_REQUEST['action']) && $_REQUEST['action'] === 'duplicate_sites')) {

    $query = "SELECT *
              FROM all_dvr_live
              WHERE live = 'Y'
                AND (
                    atmid IN (
                        SELECT atmid
                        FROM all_dvr_live
                        WHERE live = 'Y'
                        GROUP BY atmid
                        HAVING COUNT(*) > 1
                    )
                    OR IPAddress IN (
                        SELECT IPAddress
                        FROM all_dvr_live
                        WHERE live = 'Y'
                        GROUP BY IPAddress
                        HAVING COUNT(*) > 1
                    )
                )
              ORDER BY IPAddress";
    $result = mysqli_query($con, $query);
    $data = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    echo json_encode([
        'Code' => 200,
        'data' => $data,
    ]);
    exit();
}

// ==============================================================================
// 13. Camera Online / Offline Count by Channel
// URL: api/dashboard_api.php?fetch_camera_count_by_channel=1
// ==============================================================================
if (($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_camera_count_by_channel'])) || (isset($_REQUEST['action']) && $_REQUEST['action'] === 'camera_count_by_channel')) {

    $query = "SELECT
                s.channel,
                COUNT(*) AS total,
                SUM(
                    CASE
                        WHEN s.is_working = '1' THEN 1
                        ELSE 0
                    END
                ) AS working,
                SUM(
                    CASE
                        WHEN s.is_working = '0'
                          OR s.is_working IS NULL THEN 1
                        ELSE 0
                    END
                ) AS not_working
              FROM all_dvr_live a
              JOIN site_dvr_data s ON a.SN = s.SN
              WHERE a.live = 'Y'
              GROUP BY s.channel
              ORDER BY s.channel";
    $result = mysqli_query($con, $query);
    $data = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    echo json_encode([
        'Code' => 200,
        'data' => $data,
    ]);
    exit();
}

// ==============================================================================
// 14. Network Status Count
// URL: api/dashboard_api.php?fetch_network_status_count=1
// ==============================================================================
if (($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_network_status_count'])) || (isset($_REQUEST['action']) && $_REQUEST['action'] === 'network_status_count')) {

    $query = "SELECT
                COUNT(*) AS total,
                SUM(
                    CASE
                        WHEN network_status = 'working' THEN 1
                        ELSE 0
                    END
                ) AS working,
                SUM(
                    CASE
                        WHEN network_status = 'not_working' THEN 1
                        ELSE 0
                    END
                ) AS not_working
              FROM all_dvr_live";
    $result = mysqli_query($con, $query);
    $data = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    echo json_encode([
        'Code' => 200,
        'data' => $data,
    ]);
    exit();
}

// ==============================================================================
// Default: API Help & Available Endpoints Listing
// ==============================================================================
echo json_encode([
    'Code' => 200,
    'message' => 'SAR DVR Dashboard API Engine (GET Method)',
    'endpoints' => [
        'fetch_all_sites' => 'api/dashboard_api.php?fetch_all_sites=1',
        'fetch_online_sites' => 'api/dashboard_api.php?fetch_online_sites=1',
        'fetch_offline_sites' => 'api/dashboard_api.php?fetch_offline_sites=1',
        'fetch_login_failed' => 'api/dashboard_api.php?fetch_login_failed=1',
        'fetch_hdd_not_working' => 'api/dashboard_api.php?fetch_hdd_not_working=1',
        'fetch_ping_ok' => 'api/dashboard_api.php?fetch_ping_ok=1',
        'fetch_ping_failed' => 'api/dashboard_api.php?fetch_ping_failed=1',
        'fetch_time_diff_online' => 'api/dashboard_api.php?fetch_time_diff_online=1',


        'fetch_online_network_count' => 'api/dashboard_api.php?fetch_online_network_count=1',
        'fetch_offline_network_not_working' => 'api/dashboard_api.php?fetch_offline_network_not_working=1',
        'fetch_time_diff_offline' => 'api/dashboard_api.php?fetch_time_diff_offline=1',
        'fetch_duplicate_sites' => 'api/dashboard_api.php?fetch_duplicate_sites=1',
        'fetch_camera_count_by_channel' => 'api/dashboard_api.php?fetch_camera_count_by_channel=1',
        'fetch_network_status_count' => 'api/dashboard_api.php?fetch_network_status_count=1',
    ]
]);
exit();
