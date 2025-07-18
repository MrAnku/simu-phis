<?php

// app/Helpers/helpers.php

use App\Models\Log;
use App\Models\Badge;
use App\Models\Company;
use App\Models\SiemLog;
use App\Mail\CampaignMail;
use Illuminate\Support\Str;
use App\Models\SiemProvider;
use App\Models\CompanySettings;
use App\Models\WhiteLabelledCompany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

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


function translateQuizInChunks($quiz, $targetLang)
{
    $quizArray = json_decode($quiz, true);
    if (!$quizArray) return $quiz;

    // Translate in smaller chunks if it's an array of questions
    if (isset($quizArray['questions']) && is_array($quizArray['questions'])) {
        $chunkSize = 5; // Process 5 questions at a time
        $questions = $quizArray['questions'];
        $translatedQuestions = [];

        for ($i = 0; $i < count($questions); $i += $chunkSize) {
            $chunk = array_slice($questions, $i, $chunkSize);
            $chunkJson = json_encode(['questions' => $chunk], JSON_UNESCAPED_UNICODE);
            
            $translatedChunk = translateQuizUsingAi($chunkJson, $targetLang);
            $decodedChunk = json_decode($translatedChunk, true);
            
            if (isset($decodedChunk['questions'])) {
                $translatedQuestions = array_merge($translatedQuestions, $decodedChunk['questions']);
            } else {
                // If chunk translation failed, use original
                $translatedQuestions = array_merge($translatedQuestions, $chunk);
            }
        }

        $quizArray['questions'] = $translatedQuestions;
        return json_encode($quizArray, JSON_UNESCAPED_UNICODE);
    }

    return $quiz;
}






if (!function_exists('changeTranslatedQuizVideoUrl')) {
    function changeTranslatedQuizVideoUrl($quizArray, $targetLang)
    {
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
if (!function_exists('getClientIp')) {
    function getClientIp()
    {
        $forwarded = request()->header('X-Forwarded-For');
        if ($forwarded) {
            // May contain multiple IPs: client, proxy1, proxy2...
            $ips = explode(',', $forwarded);
            return trim($ips[0]); // First is the actual client IP
        }

        return request()->ip(); // fallback
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
            'ip_address' => getClientIp(),
            'user_agent' => request()->userAgent(),
        ]);

        if (Auth::guard('company')->check()) {
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
                'logo' => env('CLOUDFRONT_URL') . $isWhitelabled->dark_logo,
                'company_id' => $company_id,
            ];
        }

        return [
            'company_email' => env('MAIL_FROM_ADDRESS'),
            'learn_domain' => env('SIMUPHISH_LEARNING_URL'),
            'company_name' => env('APP_NAME'),
            'logo' => env('APP_URL') . '/assets/images/simu-logo-dark.png',
            'company_id' => $company_id,
        ];
    }
}

if (!function_exists('langName')) {

    function langName($langCode)
    {
        $languages = [
            'ar' => 'Arabic',
            'am' => 'Amharic',
            'cs' => 'Czech',
            'da' => 'Danish',
            'nl' => 'Dutch',
            'en' => 'English',
            'et' => 'Estonian',
            'fi' => 'Finnish',
            'fr' => 'French',
            'de' => 'German',
            'el' => 'Greek',
            'hi' => 'Hindi',
            'hu' => 'Hungarian',
            'ga' => 'Irish',
            'it' => 'Italian',
            'lv' => 'Latvian',
            'lt' => 'Lithuanian',
            'pl' => 'Polish',
            'pt' => 'Portuguese',
            'ro' => 'Romanian',
            'sk' => 'Slovak',
            'sl' => 'Slovenian',
            'es' => 'Spanish',
            'sv' => 'Swedish',
            'ur' => 'Urdu',
        ];

        return $languages[$langCode];
    }
}

if (!function_exists('checkWhiteLabelDomain')) {

    function checkWhiteLabelDomain()
    {
        if (Schema::hasTable('white_labelled_companies')) {
            $host = request()->getHost();
            $companyBranding = WhiteLabelledCompany::where('learn_domain', $host)
                ->where('approved_by_partner', 1)
                ->where('service_status', 1)
                ->first();

            if ($companyBranding) {
                $domain = $companyBranding->learn_domain;

                //store company branding in session
                session([
                    'companyLogoDark' => env('CLOUDFRONT_URL') . $companyBranding->dark_logo,
                    'companyLogoLight' => env('CLOUDFRONT_URL') . $companyBranding->light_logo,
                    'companyFavicon' => env('CLOUDFRONT_URL') . $companyBranding->favicon,
                    'companyName' => $companyBranding->company_name,
                    'companyDomain' => "https://" . $companyBranding->domain . "/",
                    'companyLearnDomain' => "https://" . $companyBranding->learn_domain . "/"
                ]);
            } else {
                $domain = env('SIMUPHISH_LEARNING_URL');
                //store default company branding in session
                session([
                    'companyLogoDark' => env('CLOUDFRONT_URL') . '/assets/images/simu-logo-dark.png',
                    'companyLogoLight' => env('CLOUDFRONT_URL') . '/assets/images/simu-logo.png',
                    'companyFavicon' => env('CLOUDFRONT_URL') . '/assets/images/simu-icon.png',
                    'companyName' => env('APP_NAME'),
                    'companyDomain' => env('SIMUPHISH_URL'),
                    'companyLearnDomain' => env('SIMUPHISH_LEARNING_URL')
                ]);
            }

            return $domain;
        }
    }
}

if (!function_exists('sendMailUsingDmi')) {
    function sendMailUsingDmi($accessToken, $mailData)
    {
        // Validate required mailData fields
        if (!isset($mailData['email'], $mailData['email_subject'], $mailData['mailBody'], $mailData['from_email'])) {
            return ['success' => false, 'message' => 'Missing required email parameters.Missing required email fields'];
        }

        // Build email payload for sendMail endpoint
        $email = [
            "message" => [
                "subject" => $mailData['email_subject'],
                "body" => [
                    "contentType" => "HTML",
                    "content" => $mailData['mailBody']
                ],
                "from" => [
                    "emailAddress" => [
                        "address" => $mailData['from_email']
                    ]
                ],
                "toRecipients" => [
                    [
                        "emailAddress" => [
                            "address" => $mailData['email']
                        ]
                    ]
                ]
            ],
            "saveToSentItems" => true // Save a copy in the sender's Sent Items folder
        ];

        // Send the email using the sendMail endpoint
        $sendResponse = Http::withToken($accessToken)
            ->withHeaders([
                'Content-Type' => 'application/json'
            ])
            ->post('https://graph.microsoft.com/v1.0/me/sendMail', $email);

        if ($sendResponse->successful()) {
            return ['success' => true, 'message' => 'Email sent successfully.'];
        }

        return [
            'success' => false,
            'message' => 'Failed to send email: ' . $sendResponse->body()
        ];
    }
}


if (!function_exists('checkIfOutlookDomain')) {
    function checkIfOutlookDomain($email)
    {

        //extract the domain from the email address
        $domain = substr(strrchr($email, "@"), 1);
        // Validate input domain
        if (empty($domain) || !is_string($domain)) {
            return false;
        }

        // Trim and sanitize domain
        $domain = trim($domain);
        $domain = filter_var($domain, FILTER_SANITIZE_STRING);

        // Attempt to get MX records
        $mxRecords = @dns_get_record($domain, DNS_MX);

        // Check if DNS query failed or returned no records
        if ($mxRecords === false || empty($mxRecords)) {
            return false;
        }

        // Check each MX record for Microsoft 365 pattern
        foreach ($mxRecords as $record) {
            if (isset($record['target']) && str_contains(strtolower($record['target']), 'mail.protection.outlook.com')) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('setCompanyTimezone')) {
    function setCompanyTimezone($companyId)
    {
        //get timezone from company settings
        $companySettings = CompanySettings::where('company_id', $companyId)->first();

        if ($companySettings) {
            date_default_timezone_set($companySettings->time_zone);
            config(['app.timezone' => $companySettings->time_zone]);
        }
    }
}
if (!function_exists('getWebsiteUrl')) {
    function getWebsiteUrl($website, $campaign, $campaignType = null)
    {
        // Generate random parts
        $randomString1 = Str::random(6);
        $randomString2 = Str::random(10);
        $slugName = Str::slug($website->name);

        // Construct the base URL
        $baseUrl = "https://{$randomString1}." . env('PHISHING_WEBSITE_DOMAIN') . "/{$randomString2}";

        // Define query parameters
        $params = [
            'v' => 'r',
            'c' => Str::random(10),
            'p' => $website->id,
            'l' => $slugName,
            'token' => $campaign->id,
            'usrid' => $campaign->user_id
        ];

        if ($campaignType !== null) {
            $params[$campaignType] = Str::random(3);
        }

        // Build query string and final URL
        $queryString = http_build_query($params);
        $websiteFilePath = $baseUrl . '?' . $queryString;

        return $websiteFilePath;
    }
}

if (!function_exists('changeEmailLang')) {
    function changeEmailLang($emailBody, $email_lang)
    {
        $tempFile = tmpfile();
        fwrite($tempFile, $emailBody);
        $meta = stream_get_meta_data($tempFile);
        $tempFilePath = $meta['uri'];

        $response = Http::withoutVerifying()
            ->timeout(60)
            ->attach('file', file_get_contents($tempFilePath), 'email.html')
            ->post('https://translate.sparrow.host/translate_file', [
                'source' => 'en',
                'target' => $email_lang,
            ]);

        fclose($tempFile);

        if ($response->failed()) {
            echo 'Failed to fetch translation: ' . $response->body();
            return $emailBody;
        }

        $responseData = $response->json();
        $translatedUrl = $responseData['translatedFileUrl'] ?? null;

        if (!$translatedUrl) {
            echo 'No translated URL found in response.';
            return $emailBody;
        }

        $translatedUrl = str_replace('http://localhost:5000', 'https://translate.sparrow.host', $translatedUrl);

        $translatedContent = file_get_contents($translatedUrl);


        return $translatedContent;
    }
}

if (!function_exists('checkNotificationLanguage')) {
    function checkNotificationLanguage($companyId)
    {
        $company = CompanySettings::where('company_id', $companyId)->first();
        if ($company && $company->default_notifications_lang) {
            return $company->default_notifications_lang;
        }

        // Default to English if no language is set
        return 'en';
    }
}

if (!function_exists('translateHtmlToAmharic')) {
    function translateHtmlToAmharic(string $htmlContent): ?string
    {
        $apiKey = env('OPENAI_API_KEY');
        $endpoint = 'https://api.openai.com/v1/chat/completions';

        // Step 1: Split HTML into chunks (e.g., by <div> or <p>)
        $chunks = preg_split('/(?=<div|<p|<section|<article|<table|<ul|<ol|<h[1-6])/i', $htmlContent, -1, PREG_SPLIT_NO_EMPTY);

        $translatedChunks = [];

        foreach ($chunks as $index => $chunk) {
            $messages = [
                [
                    "role" => "system",
                    "content" => "You are a professional translator. Translate only the visible text in the HTML into Amharic. Do not alter the structure, tags, attributes, or inline styles."
                ],
                [
                    "role" => "user",
                    "content" => "Translate this HTML into Amharic, keeping the HTML unchanged:\n\n$chunk"
                ]
            ];

            try {
                $response = Http::timeout(60)
                    ->retry(3, 5000)
                    ->withHeaders([
                        'Authorization' => "Bearer {$apiKey}",
                        'Content-Type'  => 'application/json',
                    ])->post($endpoint, [
                        'model' => 'gpt-4o',
                        'messages' => $messages,
                        'temperature' => 0.2,
                        'max_tokens' => 2048,
                    ]);

                if ($response->successful()) {
                    $translatedChunk = $response->json()['choices'][0]['message']['content'] ?? '';
                    $translatedChunks[] = $translatedChunk;
                } else {
                    \Log::error("Chunk $index failed", ['status' => $response->status(), 'body' => $response->body()]);
                    $translatedChunks[] = $chunk; // fallback to original
                }

                // Sleep to avoid hitting rate limits
                sleep(1);
            } catch (\Exception $e) {
                \Log::error("Chunk $index exception", ['error' => $e->getMessage()]);
                $translatedChunks[] = $chunk; // fallback to original
            }
        }

        // Step 3: Combine all translated chunks
        return implode('', $translatedChunks);
    }
}

if (!function_exists('sendPhishingMail')) {
    function sendPhishingMail($mailData)
    {

        // Set mail configuration dynamically
        config([
            'mail.mailers.smtp.host' => $mailData['sendMailHost'],
            'mail.mailers.smtp.username' => $mailData['sendMailUserName'],
            'mail.mailers.smtp.password' => $mailData['sendMailPassword'],
        ]);


        try {
            Mail::to($mailData['email'])->send(new CampaignMail($mailData));
            return true;
        } catch (\Exception $e) {
            echo 'Error sending email: ' . $e->getMessage() . "\n";
            return false;
        }
    }
}

if (!function_exists('getMatchingBadge')) {
    function getMatchingBadge($criteria_type, $criteria_value)
    {
        $badges = Badge::where('criteria_type', $criteria_type)->get();

        foreach ($badges as $badge) {
            if (compare($criteria_value, $badge->criteria_operator, $badge->criteria_value)) {
                return $badge->id; // return first matched badge
            }
        }

        return null; // no match
    }
}

if (!function_exists('compare')) {
    function compare($actual, $operator, $expected)
    {
        return match ($operator) {
            '>=' => $actual >= $expected,
            '>'  => $actual > $expected,
            '='  => $actual == $expected,
            '<=' => $actual <= $expected,
            '<'  => $actual < $expected,
            default => false,
        };
    }
}
