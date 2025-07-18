<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $badges = [
            [
                'name' => 'Top Scorer',
                'description' => 'Scored 90% or more in a training.',
                'criteria_type' => 'score',
                'criteria_operator' => '>=',
                'criteria_value' => 90,
            ],
            [
                'name' => 'High Achiever',
                'description' => 'Scored 80% or more in a training.',
                'criteria_type' => 'score',
                'criteria_operator' => '>=',
                'criteria_value' => 80,
            ],
            [
                'name' => 'Passed Learner',
                'description' => 'Scored 60% or more in a training.',
                'criteria_type' => 'score',
                'criteria_operator' => '>=',
                'criteria_value' => 60,
            ],
            [
                'name' => 'Course Conqueror',
                'description' => 'Completed 5 courses.',
                'criteria_type' => 'courses_completed',
                'criteria_operator' => '>=',
                'criteria_value' => 5,
            ],
            [
                'name' => 'Training Champion',
                'description' => 'Completed 10 courses.',
                'criteria_type' => 'courses_completed',
                'criteria_operator' => '>=',
                'criteria_value' => 10,
            ],
            [
                'name' => 'Fast Learner',
                'description' => 'Completed a course within 2 days.',
                'criteria_type' => 'time_to_complete',
                'criteria_operator' => '<=',
                'criteria_value' => 2,
            ],
            [
                'name' => 'Consistent Performer',
                'description' => 'Maintained 3 consecutive scores over 70%.',
                'criteria_type' => 'consecutive_scores_over',
                'criteria_operator' => '>=',
                'criteria_value' => 3,
            ],
            [
                'name' => 'Quiz Whiz',
                'description' => 'Passed 5 quizzes.',
                'criteria_type' => 'quizzes_passed',
                'criteria_operator' => '>=',
                'criteria_value' => 5,
            ],
            [
                'name' => 'Badge Collector',
                'description' => 'Earned 10 different badges.',
                'criteria_type' => 'badges_earned',
                'criteria_operator' => '>=',
                'criteria_value' => 10,
            ],
            [
                'name' => 'Daily Learner',
                'description' => 'Logged in for 7 consecutive days.',
                'criteria_type' => 'login_days',
                'criteria_operator' => '>=',
                'criteria_value' => 7,
            ],
            [
                'name' => 'Forum Contributor',
                'description' => 'Posted 5 comments in discussions.',
                'criteria_type' => 'forum_posts',
                'criteria_operator' => '>=',
                'criteria_value' => 5,
            ],
            [
                'name' => 'Supportive Peer',
                'description' => 'Answered 3 peer questions.',
                'criteria_type' => 'peer_answers',
                'criteria_operator' => '>=',
                'criteria_value' => 3,
            ],
            [
                'name' => 'Streak Starter',
                'description' => 'Logged in 3 days in a row.',
                'criteria_type' => 'login_streak',
                'criteria_operator' => '>=',
                'criteria_value' => 3,
            ],
            [
                'name' => 'Perfect Attendance',
                'description' => 'Attended all sessions of a course.',
                'criteria_type' => 'sessions_attended',
                'criteria_operator' => '>=',
                'criteria_value' => 100, // as percentage
            ],
            [
                'name' => 'Feedback Giver',
                'description' => 'Gave feedback on 3 courses.',
                'criteria_type' => 'feedback_given',
                'criteria_operator' => '>=',
                'criteria_value' => 3,
            ],
        ];

        foreach ($badges as $badge) {
            Badge::create($badge);
        }
    }
}
