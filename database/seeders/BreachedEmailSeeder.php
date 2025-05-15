<?php

namespace Database\Seeders;

use App\Models\BreachedEmail;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class BreachedEmailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            [
                'email' => 'sana@sparrowhost.in',
                'data' => '[
                            {
                                "Name": "BVD",
                                "Title": "Public Business Data",
                                "Domain": "bvdinfo.com",
                                "LogoPath": "https://haveibeenpwned.com/Content/Images/PwnedLogos/List.png",
                                "PwnCount": 27917714,
                                "AddedDate": "2023-10-09T07:05:10Z",
                                "IsMalware": false,
                                "IsRetired": false,
                                "BreachDate": "2021-08-19",
                                "IsSpamList": false,
                                "IsVerified": true,
                                "DataClasses": [
                                    "Dates of birth",
                                    "Email addresses",
                                    "Job titles",
                                    "Names",
                                    "Phone numbers",
                                    "Physical addresses"
                                ],
                                "Description": "In approximately August 2021, <a href=\"https://kaduu.io/blog/2022/02/04/us-strategic-company-bureau-van-dijk-hacked/\" target=\"_blank\" rel=\"noopener\">hundreds of gigabytes of business data collated from public sources was obtained and later published to a popular hacking forum</a>. Sourced from a customer of Bureau van Dijks (BvD) &quot;Orbis&quot; product, the corpus of data released contained hundreds of millions of lines about corporations and individuals, including personal information such as names and dates of birth. The data also included 28M unique email addresses along with physical addresses (presumedly corporate locations), phone numbers and job titles. There was no unauthorised access to BvDs systems, nor did the incident expose any of their or parent companys Moodys clients.",
                                "IsSensitive": false,
                                "IsFabricated": false,
                                "IsStealerLog": false,
                                "ModifiedDate": "2023-12-06T22:45:20Z",
                                "IsSubscriptionFree": false
                            }
                        ]',
                'company_id' => 'bc2d3bf2-4eb0-47db-8f1e-6d2a76b94608'
            ],
            [
                'email' => 'sana@yopmail.com',
                'data' => '[
                        {
                            "Name": "BVD",
                            "Title": "Public Business Data",
                            "Domain": "bvdinfo.com",
                            "LogoPath": "https://haveibeenpwned.com/Content/Images/PwnedLogos/List.png",
                            "PwnCount": 27917714,
                            "AddedDate": "2023-10-09T07:05:10Z",
                            "IsMalware": false,
                            "IsRetired": false,
                            "BreachDate": "2021-08-19",
                            "IsSpamList": false,
                            "IsVerified": true,
                            "DataClasses": [
                                "Dates of birth",
                                "Email addresses",
                                "Job titles",
                                "Names",
                                "Phone numbers",
                                "Physical addresses"
                            ],
                            "Description": "In approximately August 2021, <a href=\"https://kaduu.io/blog/2022/02/04/us-strategic-company-bureau-van-dijk-hacked/\" target=\"_blank\" rel=\"noopener\">hundreds of gigabytes of business data collated from public sources was obtained and later published to a popular hacking forum</a>. Sourced from a customer of Bureau van Dijks (BvD) &quot;Orbis&quot; product, the corpus of data released contained hundreds of millions of lines about corporations and individuals, including personal information such as names and dates of birth. The data also included 28M unique email addresses along with physical addresses (presumedly corporate locations), phone numbers and job titles. There was no unauthorised access to BvDs systems, nor did the incident expose any of their or parent companys Moodys clients.",
                            "IsSensitive": false,
                            "IsFabricated": false,
                            "IsStealerLog": false,
                            "ModifiedDate": "2023-12-06T22:45:20Z",
                            "IsSubscriptionFree": false
                        }
                    ]',
                'company_id' => 'bc2d3bf2-4eb0-47db-8f1e-6d2a76b94609'
            ],
            [
                'email' => 'test@yopmail.com',
                'data' => '[
                        {
                            "Name": "BVD",
                            "Title": "Public Business Data",
                            "Domain": "bvdinfo.com",
                            "LogoPath": "https://haveibeenpwned.com/Content/Images/PwnedLogos/List.png",
                            "PwnCount": 27917714,
                            "AddedDate": "2023-10-09T07:05:10Z",
                            "IsMalware": false,
                            "IsRetired": false,
                            "BreachDate": "2021-08-19",
                            "IsSpamList": false,
                            "IsVerified": true,
                            "DataClasses": [
                                "Dates of birth",
                                "Email addresses",
                                "Job titles",
                                "Names",
                                "Phone numbers",
                                "Physical addresses"
                            ],
                            "Description": "In approximately August 2021, <a href=\"https://kaduu.io/blog/2022/02/04/us-strategic-company-bureau-van-dijk-hacked/\" target=\"_blank\" rel=\"noopener\">hundreds of gigabytes of business data collated from public sources was obtained and later published to a popular hacking forum</a>. Sourced from a customer of Bureau van Dijks (BvD) &quot;Orbis&quot; product, the corpus of data released contained hundreds of millions of lines about corporations and individuals, including personal information such as names and dates of birth. The data also included 28M unique email addresses along with physical addresses (presumedly corporate locations), phone numbers and job titles. There was no unauthorised access to BvDs systems, nor did the incident expose any of their or parent companys Moodys clients.",
                            "IsSensitive": false,
                            "IsFabricated": false,
                            "IsStealerLog": false,
                            "ModifiedDate": "2023-12-06T22:45:20Z",
                            "IsSubscriptionFree": false
                        }
                    ]',
                'company_id' => 'bc2d3bf2-4eb0-47db-8f1e-6d2a76b94600'
            ]
        ];

        foreach ($records as $record) {
            BreachedEmail::create($record);
        }
    }
}
