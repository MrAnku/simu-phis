<?php

// app/Helpers/helpers.php

use Illuminate\Support\Facades\Route;

if (!function_exists('isActiveRoute')) {
    function isActiveRoute($route, $output = 'active')
    {
        if (Route::currentRouteNamed($route)) {
            return $output;
        }

        return '';
    }
}

if (!function_exists('generateRandom')) {
    function generateRandom($length = 6)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }
}

if (!function_exists('generateRandomDate')) {
    function generateRandomDate($betweenDays, $startTime, $endTime)
    {

        // Extract start and end dates from $betweenDays
        list($startDate, $endDate) = explode(" to ", $betweenDays);

        // Create DateTime objects for the start and end dates
        $startDateTime = new DateTime($startDate . ' ' . $startTime);
        $endDateTime = new DateTime($endDate . ' ' . $endTime);

        // Generate a random timestamp between the start and end dates
        $randomTimestamp = rand($startDateTime->getTimestamp(), $endDateTime->getTimestamp());

        // Create a DateTime object from the random timestamp
        $randomDate = (new DateTime())->setTimestamp($randomTimestamp);

        // Ensure the random time is between the specified startTime and endTime
        $randomDateOnly = $randomDate->format('Y-m-d'); // Get the date part only
        $randomTimeOnly = $randomDate->format('H:i'); // Get the time part only

        if ($randomTimeOnly < $startTime) {
            $randomTimeOnly = $startTime;
        } elseif ($randomTimeOnly > $endTime) {
            $randomTimeOnly = $endTime;
        }

        // Combine the random date with the adjusted time
        $finalRandomDateTime = new DateTime("$randomDateOnly $randomTimeOnly");

        // Format the resulting date and time
        $formattedDate = $finalRandomDateTime->format('m/d/Y g:i A');

        return $formattedDate;
    }
}
