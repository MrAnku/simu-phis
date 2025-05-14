<?php

// app/Helpers/helpers.php

use App\Models\Log;
use App\Models\Company;
use App\Models\SiemLog;
use App\Models\SiemProvider;
use App\Models\WhiteLabelledCompany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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

if (!function_exists('translateQuizUsingAi')) {
    function translateQuizUsingAi($quiz, $targetLang)
    {
        $apiKey = env('OPENAI_API_KEY');
        $apiEndpoint = "https://api.openai.com/v1/chat/completions";
        // $file = public_path($tempBodyFile);
        // $fileContent = file_get_contents($tempBodyFile);
        // return response($fileContent, 200)->header('Content-Type', 'text/html');

        $quizJson = json_encode($quiz, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $prompt = "Translate this quiz into '{$targetLang}' language. 
                Translate **only** the values of the objects, **not** the keys.
                Keep the values of 'qtype', 'correctOption', and 'videoUrl' **unchanged**.
                Do **not** include explanations, comments, or anything else—return **only** a valid JSON.

                Return a **valid JSON object** without any additional text or formatting.

                Here is the quiz JSON:\n\n{$quizJson}";

        $requestBody = [
            "model" => "gpt-4o-mini",
            "messages" => [["role" => "user", "content" => $prompt]],
            "temperature" => 0.7
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $apiKey,
        ])->post($apiEndpoint, $requestBody);

        if ($response->failed()) {
            // return 'Failed to fetch translation' . json_encode($response->body());
             return $quiz;
        }
        $responseData = $response->json();
        $translatedJsonQuiz = $responseData['choices'][0]['message']['content'] ?? null;

        return trim($translatedJsonQuiz);
    }
}
if (!function_exists('changeTranslatedQuizVideoUrl')) {
    function changeTranslatedQuizVideoUrl($quizArray, $targetLang){
        $newArray = [];
        foreach ($quizArray as $quizObject) {
            foreach ($quizObject as $key => $value) {
                if ($key == 'videoUrl') {
                    if (isYouTubeLink($value)) {
                        $quizObject[$key] = $value;
                    } else {
                        $quizObject[$key] = changeVideoLanguage($value, $targetLang);
                    }
                }
            }
            array_push($newArray, $quizObject);
        }
        return $newArray;
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
    function log_action($details, $role = 'company', $role_id = null)
    {

        $role_id = $role_id ?? Auth::user()->company_id;

        Log::create([
            'role' => $role,
            'msg' => $details,
            'role_id' => $role_id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        if(Auth::guard('company')->check()){
            $siemExists = SiemProvider::where('company_id', Auth::user()->company_id)->where('status', 1)->first();
            if ($siemExists) {
                SiemLog::create([
                    'log_msg' => $details,
                    'company_id' => Auth::user()->company_id,
                ]);
            }
        }
    }
}

if (!function_exists('checkWhitelabeled')) {

    function checkWhitelabeled($company_id)
    {
        $company = Company::with('partner')->where('company_id', $company_id)->first();

        $partner_id = $company->partner->partner_id;
        $company_email = $company->email;


        
        $isWhitelabled = WhiteLabelledCompany::where('partner_id', $partner_id)
            ->where('approved_by_partner', 1)
            ->first();

        if ($isWhitelabled) {
            return [
                'company_email' => $company_email,
                'learn_domain' => $isWhitelabled->learn_domain,
                'company_name' => $isWhitelabled->company_name,
                'logo' => $isWhitelabled->dark_logo
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

if (!function_exists('langName')) {

    function langName($langCode)
    {
        $languages = [
            "sq" => "Albanian",
            "ar" => "Arabic",
            "az" => "Azerbaijani",
            "bn" => "Bengali",
            "bg" => "Bulgarian",
            "ca" => "Catalan",
            "zh" => "Chinese",
            "zt" => "Chinese (traditional)",
            "cs" => "Czech",
            "da" => "Danish",
            "nl" => "Dutch",
            "en" => "English",
            "eo" => "Esperanto",
            "et" => "Estonian",
            "fi" => "Finnish",
            "fr" => "French",
            "de" => "German",
            "el" => "Greek",
            "he" => "Hebrew",
            "hi" => "Hindi",
            "hu" => "Hungarian",
            "id" => "Indonesian",
            "ga" => "Irish",
            "it" => "Italian",
            "ja" => "Japanese",
            "ko" => "Korean",
            "lv" => "Latvian",
            "lt" => "Lithuanian",
            "ms" => "Malay",
            "nb" => "Norwegian",
            "fa" => "Persian",
            "pl" => "Polish",
            "pt" => "Portuguese",
            "ro" => "Romanian",
            "ru" => "Russian",
            "sk" => "Slovak",
            "sl" => "Slovenian",
            "es" => "Spanish",
            "sv" => "Swedish",
            "tl" => "Tagalog",
            "th" => "Thai",
            "tr" => "Turkish",
            "uk" => "Ukrainian",
            "ur" => "Urdu"
        ];

        return $languages[$langCode];
    }
}
