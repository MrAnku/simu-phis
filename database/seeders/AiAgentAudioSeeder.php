<?php

namespace Database\Seeders;

use App\Models\DeepFakeAudio;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AiAgentAudioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $audios = [
            [
                "id" => "2EiwWnXFnvU5JabPnv8n",
                "name" => "Clyde",
                "gender" => "male",
                "language" => "en",
                "use_case" => "characters_animation",
                "company_id" => "default"
            ],
            [
                "id" => "CwhRBWXzGAHq8TQ4Fs17",
                "name" => "Roger",
                "gender" => "male",
                "language" => "en",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "EXAVITQu4vr4xnSDxMaL",
                "name" => "Sarah",
                "gender" => "female",
                "language" => "en",
                "use_case" => "entertainment_tv",
                "company_id" => "default"
            ],
            [
                "id" => "FGY2WhTYpPnrIDTdsKH5",
                "name" => "Laura",
                "gender" => "female",
                "language" => "en",
                "use_case" => "social_media",
                "company_id" => "default"
            ],
            [
                "id" => "IKne3meq5aSn9XLyUdCD",
                "name" => "Charlie",
                "gender" => "male",
                "language" => "en",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "JBFqnCBsd6RMkjVDRZzb",
                "name" => "George",
                "gender" => "male",
                "language" => "en",
                "use_case" => "narrative_story",
                "company_id" => "default"
            ],
            [
                "id" => "N2lVS1w4EtoT3dr4eOWO",
                "name" => "Callum",
                "gender" => "male",
                "language" => "en",
                "use_case" => "characters",
                "company_id" => "default"
            ],
            [
                "id" => "SAz9YHcvj6GT2YYXdXww",
                "name" => "River",
                "gender" => "neutral",
                "language" => "en",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "SOYHLrjzK2X1ezoPC6cr",
                "name" => "Harry",
                "gender" => "male",
                "language" => "en",
                "use_case" => "characters_animation",
                "company_id" => "default"
            ],
            [
                "id" => "TX3LPaxmHKxFdv7VOQHJ",
                "name" => "Liam",
                "gender" => "male",
                "language" => "en",
                "use_case" => "social_media",
                "company_id" => "default"
            ],
            [
                "id" => "Xb7hH8MSUJpSbSDYk0k2",
                "name" => "Alice",
                "gender" => "female",
                "language" => "en",
                "use_case" => "advertisement",
                "company_id" => "default"
            ],
            [
                "id" => "XrExE9yKIg1WjnnlVkGX",
                "name" => "Matilda",
                "gender" => "female",
                "language" => "en",
                "use_case" => "informative_educational",
                "company_id" => "default"
            ],
            [
                "id" => "bIHbv24MWmeRgasZH58o",
                "name" => "Will",
                "gender" => "male",
                "language" => "en",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "cgSgspJ2msm6clMCkdW9",
                "name" => "Jessica",
                "gender" => "female",
                "language" => "en",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "cjVigY5qzO86Huf0OWal",
                "name" => "Eric",
                "gender" => "male",
                "language" => "en",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "iP95p4xoKVk53GoZ742B",
                "name" => "Chris",
                "gender" => "male",
                "language" => "en",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "nPczCjzI2devNBz1zQrb",
                "name" => "Brian",
                "gender" => "male",
                "language" => "en",
                "use_case" => "social_media",
                "company_id" => "default"
            ],
            [
                "id" => "onwK4e9ZLuTAKqWW03F9",
                "name" => "Daniel",
                "gender" => "male",
                "language" => "en",
                "use_case" => "informative_educational",
                "company_id" => "default"
            ],
            [
                "id" => "pFZP5JQG7iQjIQuC4Bku",
                "name" => "Lily",
                "gender" => "female",
                "language" => "en",
                "use_case" => "narration",
                "company_id" => "default"
            ],
            [
                "id" => "pqHfZKP75CvOlQylNhV4",
                "name" => "Bill",
                "gender" => "male",
                "language" => "en",
                "use_case" => "advertisement",
                "company_id" => "default"
            ],
            [
                "id" => "1EVds7FNGSXoKeOiMXuf",
                "name" => "Denis - Russian male",
                "gender" => "male",
                "language" => "ru",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "2fzSNSOmb5nntInhUtfm",
                "name" => "Paloma",
                "gender" => "female",
                "language" => "es",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "3Kfr7NbSVkpOWCWA4Zgu",
                "name" => "Theo Morret",
                "gender" => "male",
                "language" => "fr",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "8KMBeKnOSHXjLqGuWsAE",
                "name" => "Sultan",
                "gender" => "male",
                "language" => "ar",
                "use_case" => "informative_educational",
                "company_id" => "default"
            ],
            [
                "id" => "Bwff1jnzl1s94AEcntUq",
                "name" => "Tanya- Upbeat and Expressive",
                "gender" => "female",
                "language" => "en",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "GNZJNyUmjtha6JKquA3M",
                "name" => "Lipakshi – Engaging Conversational Voice",
                "gender" => "female",
                "language" => "hi",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "IRHApOXLvnW57QJPQH2P",
                "name" => "Adam - Brooding, Dark, Tough American",
                "gender" => "male",
                "language" => "en",
                "use_case" => "characters_animation",
                "company_id" => "default"
            ],
            [
                "id" => "KXxZd16DiBqt82nbarJx",
                "name" => "Lucy Fennek - Witty Accomplice & Lively Conversation",
                "gender" => "female",
                "language" => "de",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "Ka6yOFdNGhzFuCVW6VyO",
                "name" => "Emma",
                "gender" => "female",
                "language" => "fr",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "KqUYf0Y6LOYccQaUCPkT",
                "name" => "Sergio Yuen",
                "gender" => "male",
                "language" => "es",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "MTgv1KRJpUnc34UMGTHK",
                "name" => "Matteo - Smooth & Authentic",
                "gender" => "male",
                "language" => "it",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "T7LqjbHitdbIpcfll9Bx",
                "name" => "Aditya - Indian Hindi podcast style",
                "gender" => "male",
                "language" => "hi",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "TWUKKXAylkYxxlPe4gx0",
                "name" => "Armando (realistic)",
                "gender" => "male",
                "language" => "en",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "TbMNBJ27fH2U0VgpSNko",
                "name" => "Lori - Happy and sweet",
                "gender" => "female",
                "language" => "en",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "U7wWSnxIJwCjioxt86mk",
                "name" => "Olaniyi Victor - A warm, calming African voice with a rich Lagos Nigerian accent",
                "gender" => "male",
                "language" => "en",
                "use_case" => "narrative_story",
                "company_id" => "default"
            ],
            [
                "id" => "eOHsvebhdtt0XFeHVMQY",
                "name" => "Olabisi – Warm & Relatable African Female",
                "gender" => "female",
                "language" => "en",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "jAAHNNqlbAX9iWjJPEtE",
                "name" => "Sara - Kind & Expressive",
                "gender" => "female",
                "language" => "ar",
                "use_case" => "social_media",
                "company_id" => "default"
            ],
            [
                "id" => "m5qndnI7u4OAdXhH0Mr5",
                "name" => "Krishna - Energetic Hindi Voice",
                "gender" => "male",
                "language" => "hi",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "nw6EIXCsQ89uJMjytYb8",
                "name" => "Ugochukwu - African, Nigerian, middle-aged male with Igbo accent.",
                "gender" => "male",
                "language" => "en",
                "use_case" => "narrative_story",
                "company_id" => "default"
            ],
            [
                "id" => "oC2pCZZWEDRe6lmZpaaw",
                "name" => "Bukola - Young Nigerian African Female - Conversational - Gentle",
                "gender" => "female",
                "language" => "en",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "ordbVDppyuwp96ZjvQOM",
                "name" => "Hakeem 2 - Conversational",
                "gender" => "male",
                "language" => "ar",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "s0phbFBBp708ZeIy8oGx",
                "name" => "Arcadays",
                "gender" => "male",
                "language" => "ru",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "tOo2BJ74frmnPadsDNIi",
                "name" => "Kate - Calm & Friendly",
                "gender" => "female",
                "language" => "ru",
                "use_case" => "conversational",
                "company_id" => "default"
            ],
            [
                "id" => "z1EhmmPwF0ENGYE8dBE6",
                "name" => "Christian Plasa - Conversational v2 Clean",
                "gender" => "male",
                "language" => "de",
                "use_case" => "conversational",
                "company_id" => "default"
            ]
        ];

        foreach ($audios as $audio) {
            DeepFakeAudio::create([
                'audio_id' => $audio['id'],
                'name' => $audio['name'],
                'gender' => $audio['gender'],
                'language' => $audio['language'],
                'use_case' => $audio['use_case'],
                'company_id' => $audio['company_id'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
