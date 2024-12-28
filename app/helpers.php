<?php

// app/Helpers/helpers.php

use App\Models\Log;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
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

if (!function_exists('translateArrayValues')) {
    function translateArrayValues($array, $targetLang)
    {
        $translationArr = [];
        foreach ($array as $obj) {

            foreach ($obj as $key => $value) {

                array_push($translationArr, $value);
            }
        }

        $translatedArr = changeTrainingLang($translationArr, $targetLang);

        //  var_dump($translatedArr);

        $qobj = [];
        $qarr = [];

        $x = 0;
        foreach ($array as $obj) {

            foreach ($obj as $key => $value) {
                if ($key == 'qtype') {
                    $qobj[$key] = $value;
                } elseif ($key == 'correctOption') {
                    $qobj[$key] = $value;
                } elseif ($key == 'videoUrl') {

                    if (isYouTubeLink($value)) {
                        $qobj[$key] = $value;
                    } else {
                        $qobj[$key] = changeVideoLanguage($value, $targetLang);
                    }
                } else {
                    $qobj[$key] = $translatedArr['translatedText'][$x];
                }
                $x++;
            }
            array_push($qarr, $qobj);
            $qobj = [];
        }

        return $qarr;
    }
}

if (!function_exists('changeTrainingLang')) {
    function changeTrainingLang($content, $targetLang)
    {
        // API endpoint
        $apiEndpoint = "http://65.21.191.199/translate";

        // Request body
        $requestBody = [
            "q" => $content,
            "source" => "en",
            "target" => $targetLang,
        ];

        // Initialize cURL session
        $curl = curl_init();

        // Set cURL options
        curl_setopt($curl, CURLOPT_URL, $apiEndpoint);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($requestBody));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

        // Execute cURL request
        $response = curl_exec($curl);

        // Check for errors
        if (curl_errno($curl)) {
            // echo 'cURL error: ' . curl_error($curl);
            // exit;
        }

        // Close cURL session
        curl_close($curl);

        // Decode the JSON response
        $responseData = json_decode($response, true);

        // echo $responseData;

        return $responseData;
    }
}

if (!function_exists('isYouTubeLink')) {
    function isYouTubeLink($url)
    {
        $pattern = '/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/(watch\?v=|embed\/|v\/|.+\?v=)?([a-zA-Z0-9_-]{11})$/';
        return preg_match($pattern, $url);
    }
}

if (!function_exists('changeVideoLanguage')) {
    function changeVideoLanguage($url, $lang)
    {
        // Convert the language code to uppercase
        $lang = strtoupper($lang);

        // Find the position of the last dot in the URL (to locate the file extension)
        $pos = strrpos($url, '.');

        // If no dot is found, return the original URL
        if ($pos === false) {
            return $url;
        }

        // Insert the language code before the file extension
        $newUrl = substr($url, 0, $pos) . '-' . $lang . substr($url, $pos);

        return $newUrl;
    }
}

if (!function_exists('log_action')) {
    function log_action($details, $role = 'company', $role_id = null){

        $role_id = $role_id ?? Auth::user()->company_id; 

    Log::create([
        'role' => $role,
        'msg' => $details,
        'role_id' => $role_id,
        'ip_address' => request()->ip(),
        'user_agent' => request()->userAgent(),
    ]);
}
}

if (!function_exists('checkWhitelabeled')) {

    function checkWhitelabeled($company_id){
        $company = Company::with('partner')->where('company_id', $company_id)->first();

        $partner_id = $company->partner->partner_id;
        $company_email = $company->email;

        $isWhitelabled = DB::table('white_labelled_partner')
            ->where('partner_id', $partner_id)
            ->where('approved_by_admin', 1)
            ->first();

        if ($isWhitelabled) {
            return [
                'company_email' => $company_email,
                'learn_domain' => $isWhitelabled->learn_domain,
                'company_name' => $isWhitelabled->company_name,
                'logo' => env('APP_URL') . '/storage/uploads/whitelabeled/' . $isWhitelabled->dark_logo
            ];
        }

        return [
            'company_email' => env('MAIL_FROM_ADDRESS'),
            'learn_domain' => 'learn.simuphish.com',
            'company_name' => 'simUphish',
            'logo' => env('APP_URL') . '/assets/images/simu-logo-dark.png'
        ];
}
}
