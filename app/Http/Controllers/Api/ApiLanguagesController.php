<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\CompanySettings;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ApiLanguagesController extends Controller
{
    public function index()
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
        $email = Auth::user()->email;
        $defaultPhishingLanguage = CompanySettings::where('email', $email)->first()->default_phishing_email_lang;

        $defaultTrainingLanguage = CompanySettings::where('email', $email)->first()->default_training_lang;

        $defaultNotificationLanguage = CompanySettings::where('email', $email)->first()->default_notifications_lang;

        return response()->json([
            'success' => true,
            'message' => __('Languages fetched successfully.'),
            'data' => [
                "languages" => $languages,
                "default_phishing_language" => $defaultPhishingLanguage,
                "default_training_language" => $defaultTrainingLanguage,
                "default_notification_language" => $defaultNotificationLanguage
            ],
        ], 200);
    }
}
