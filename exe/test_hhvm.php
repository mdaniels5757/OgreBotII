<?php 

require_once __DIR__ . "/../base/bootstrap.php";
global $validator;

$validator->assert(cal_days_in_month(CAL_GREGORIAN, 2, 1900) === 28);
$validator->assert(cal_days_in_month(CAL_GREGORIAN, 2, 2100) === 28);
$validator->assert(cal_days_in_month(CAL_GREGORIAN, 2, 2000) === 29);
$validator->assert(cal_days_in_month(CAL_GREGORIAN, 2, 2004) === 29);
$validator->assert(cal_days_in_month(CAL_GREGORIAN, 2, 2005) === 28);
$validator->assert(cal_days_in_month(CAL_GREGORIAN, 1, 2005) === 31);
$validator->assert(cal_days_in_month(CAL_GREGORIAN, 3, 2005) === 31);
$validator->assert(cal_days_in_month(CAL_GREGORIAN, 5, 2005) === 31);
$validator->assert(cal_days_in_month(CAL_GREGORIAN, 7, 2005) === 31);
$validator->assert(cal_days_in_month(CAL_GREGORIAN, 8, 2005) === 31);
$validator->assert(cal_days_in_month(CAL_GREGORIAN, 10, 2005) === 31);
$validator->assert(cal_days_in_month(CAL_GREGORIAN, 12, 2005) === 31);
$validator->assert(cal_days_in_month(CAL_GREGORIAN, 4, 2005) === 30);
$validator->assert(cal_days_in_month(CAL_GREGORIAN, 6, 2005) === 30);
$validator->assert(cal_days_in_month(CAL_GREGORIAN, 9, 2005) === 30);
$validator->assert(cal_days_in_month(CAL_GREGORIAN, 11, 2005) === 30);