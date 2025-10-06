<?php
// Set a default timezone (important for accurate time handling)
date_default_timezone_set('Asia/Kathmandu');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/nepali_calendar.php';

// Get current time
$current_timestamp = time();

// Determine the language from the session
$lang = $_SESSION['lang'] ?? 'en';

if ($lang === 'np') {
    // --- NEPALESE DATE (BS) ---
    $cal = new Nepali_Calendar();

    // Get current English Date components
    $year  = (int)date('Y', $current_timestamp);
    $month = (int)date('m', $current_timestamp);
    $day   = (int)date('d', $current_timestamp);

    // Convert to Nepali Date
    $nepDate = $cal->eng_to_nep($year, $month, $day);

    // Nepali Numeral Map
    $np_numbers = ['0'=>'०','1'=>'१','2'=>'२','3'=>'३','4'=>'४','5'=>'५','6'=>'६','7'=>'७','8'=>'८','9'=>'९'];

    $nep_month_name = $nepDate['nmonth'];

    $date_str = strtr(
        $nepDate['year'] . '-' . str_pad($nepDate['month'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($nepDate['date'], 2, '0', STR_PAD_LEFT),
        $np_numbers
    );

    $time_str = date("h:i:s A", $current_timestamp);
    $time_str = strtr($time_str, $np_numbers);

    // --- NEPALI DATE (BS) ---
    echo "मिति: " . htmlspecialchars($date_str) . " | समय: " . htmlspecialchars($time_str);
} else {
    // --- ENGLISH DATE (AD) ---
    $date_format = date("Y M j, h:i:s A", $current_timestamp);
    echo "Date: " . date("Y-M-j") . " | Time: " . date("h:i:s A");
}
?>