<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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

        return response()->json([
            'success' => true,
            'message' => __('Languages fetched successfully.'),
            'data' => $languages,
        ], 200);
    }
}
