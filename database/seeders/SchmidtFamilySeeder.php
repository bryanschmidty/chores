<?php

namespace Database\Seeders;

use App\Models\Chore;
use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SchmidtFamilySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Schmidt Family
        $family = Family::firstOrCreate(
            ['name' => 'Schmidt Family'],
            [
                'settings' => [],
            ]
        );

        $this->command->info('Schmidt Family created');

        // Create admin users
        $adminUsers = [
            [
                'name' => 'Bryan Schmidt',
                'email' => 'bryan.schmidty@gmail.com',
            ],
            [
                'name' => 'Heidi Schmidt',
                'email' => 'heidi.schmidty@gmail.com',
            ],
        ];

        foreach ($adminUsers as $adminData) {
            User::firstOrCreate(
                ['email' => $adminData['email']],
                [
                    'name' => $adminData['name'],
                    'password' => Hash::make('password'),
                    'family_id' => $family->id,
                    'role' => 'admin',
                    'email_verified_at' => now(),
                    'notification_preferences' => [
                        'email_chore_assigned' => true,
                        'email_chore_completed' => true,
                        'email_chore_verified' => true,
                        'email_overdue_reminder' => true,
                        'browser_chore_assigned' => true,
                        'browser_chore_completed' => true,
                        'browser_chore_verified' => true,
                        'browser_overdue_reminder' => true,
                    ],
                ]
            );
            $this->command->info("Admin user created: {$adminData['email']}");
        }

        // Create member users
        $memberUsers = [
            [
                'name' => 'Kyle Kurt Schmidt',
                'email' => 'kylekurt.schmidty@gmail.com',
            ],
            [
                'name' => 'Jocelyn Schmidt',
                'email' => 'jocelyn.schmidty@gmail.com',
            ],
            [
                'name' => 'Landon Luke Schmidt',
                'email' => 'landonluke.schmidty@gmail.com',
            ],
        ];

        foreach ($memberUsers as $memberData) {
            User::firstOrCreate(
                ['email' => $memberData['email']],
                [
                    'name' => $memberData['name'],
                    'password' => Hash::make('password'),
                    'family_id' => $family->id,
                    'role' => 'member',
                    'email_verified_at' => now(),
                    'notification_preferences' => [
                        'email_chore_assigned' => true,
                        'email_chore_completed' => false,
                        'email_chore_verified' => true,
                        'email_overdue_reminder' => true,
                        'browser_chore_assigned' => true,
                        'browser_chore_completed' => false,
                        'browser_chore_verified' => true,
                        'browser_overdue_reminder' => true,
                    ],
                ]
            );
            $this->command->info("Member user created: {$memberData['email']}");
        }

        // Create daily chores
        $dailyChores = [
            [
                'name' => 'Make Bed',
                'description' => 'Make your bed neatly with sheets and blankets arranged properly.',
                'points' => 5,
            ],
            [
                'name' => 'Put Away Clothes',
                'description' => 'Put away clean clothes in drawers or closet. Hang up items that need hanging.',
                'points' => 5,
            ],
            [
                'name' => 'Clear Dishes from Table',
                'description' => 'Clear all dishes, cups, and utensils from the dining table after meals.',
                'points' => 5,
            ],
            [
                'name' => 'Wipe Down Kitchen Counters',
                'description' => 'Clean and wipe down kitchen counters and surfaces.',
                'points' => 5,
            ],
            [
                'name' => 'Take Out Trash',
                'description' => 'Take out trash and recycling to the appropriate bins.',
                'points' => 5,
            ],
        ];

        foreach ($dailyChores as $chore) {
            Chore::firstOrCreate(
                [
                    'family_id' => $family->id,
                    'name' => $chore['name'],
                ],
                array_merge($chore, [
                    'frequency' => 'daily',
                    'review_required' => false,
                    'photos_required' => 'none',
                ])
            );
        }

        // Create twice weekly chores
        $twiceWeeklyChores = [
            [
                'name' => 'Vacuum Living Room',
                'description' => 'Vacuum the living room carpet and rugs thoroughly.',
                'points' => 15,
            ],
            [
                'name' => 'Clean Bathroom',
                'description' => 'Clean bathroom including sink, mirror, toilet, and floor.',
                'points' => 20,
            ],
            [
                'name' => 'Take Out Recycling',
                'description' => 'Take recycling bins to the curb or appropriate location.',
                'points' => 10,
            ],
            [
                'name' => 'Water Plants',
                'description' => 'Water all indoor plants and check for any that need attention.',
                'points' => 10,
            ],
        ];

        foreach ($twiceWeeklyChores as $chore) {
            Chore::firstOrCreate(
                [
                    'family_id' => $family->id,
                    'name' => $chore['name'],
                ],
                array_merge($chore, [
                    'frequency' => 'twice_weekly',
                    'review_required' => false,
                    'photos_required' => 'none',
                ])
            );
        }

        // Create weekly chores
        $weeklyChores = [
            [
                'name' => 'Vacuum All Bedrooms',
                'description' => 'Vacuum all bedroom carpets and rugs.',
                'points' => 20,
            ],
            [
                'name' => 'Deep Clean Bathroom',
                'description' => 'Thoroughly clean bathroom including scrubbing tub/shower, cleaning toilet, and mopping floor.',
                'points' => 30,
                'photos_required' => 'only_after',
            ],
            [
                'name' => 'Dust All Surfaces',
                'description' => 'Dust all furniture, shelves, and surfaces throughout the house.',
                'points' => 20,
            ],
            [
                'name' => 'Clean Kitchen Appliances',
                'description' => 'Clean inside and outside of kitchen appliances (microwave, oven, refrigerator).',
                'points' => 25,
            ],
            [
                'name' => 'Mop Floors',
                'description' => 'Mop all hard surface floors throughout the house.',
                'points' => 25,
            ],
            [
                'name' => 'Change Bed Linens',
                'description' => 'Change bed sheets and pillowcases on all beds.',
                'points' => 15,
            ],
            [
                'name' => 'Clean Windows',
                'description' => 'Clean interior windows and mirrors throughout the house.',
                'points' => 20,
            ],
            [
                'name' => 'Organize Common Areas',
                'description' => 'Organize and tidy up common areas like living room, family room, etc.',
                'points' => 15,
            ],
        ];

        foreach ($weeklyChores as $chore) {
            Chore::firstOrCreate(
                [
                    'family_id' => $family->id,
                    'name' => $chore['name'],
                ],
                array_merge($chore, [
                    'frequency' => 'weekly',
                    'review_required' => false,
                    'photos_required' => $chore['photos_required'] ?? 'none',
                ])
            );
        }

        // Create twice monthly chores
        $twiceMonthlyChores = [
            [
                'name' => 'Clean Baseboards',
                'description' => 'Dust and clean all baseboards throughout the house.',
                'points' => 30,
            ],
            [
                'name' => 'Organize Closets',
                'description' => 'Organize and declutter closets, removing items that no longer fit or are needed.',
                'points' => 40,
            ],
            [
                'name' => 'Clean Light Fixtures',
                'description' => 'Clean light fixtures and replace any burnt-out bulbs.',
                'points' => 25,
            ],
            [
                'name' => 'Wash Windows (Exterior)',
                'description' => 'Clean exterior windows and window sills.',
                'points' => 35,
            ],
        ];

        foreach ($twiceMonthlyChores as $chore) {
            Chore::firstOrCreate(
                [
                    'family_id' => $family->id,
                    'name' => $chore['name'],
                ],
                array_merge($chore, [
                    'frequency' => 'twice_monthly',
                    'review_required' => false,
                    'photos_required' => 'none',
                ])
            );
        }

        // Create monthly chores
        $monthlyChores = [
            [
                'name' => 'Deep Clean Refrigerator',
                'description' => 'Remove all items, clean shelves and drawers, and organize contents.',
                'points' => 50,
                'photos_required' => 'before_and_after',
            ],
            [
                'name' => 'Clean Oven',
                'description' => 'Deep clean the oven, removing all grease and food residue.',
                'points' => 50,
                'photos_required' => 'before_and_after',
            ],
            [
                'name' => 'Organize Garage',
                'description' => 'Organize garage, put items in proper places, and dispose of unwanted items.',
                'points' => 60,
            ],
            [
                'name' => 'Clean Air Vents',
                'description' => 'Vacuum and clean air vents and return vents throughout the house.',
                'points' => 40,
            ],
            [
                'name' => 'Wash Curtains/Blinds',
                'description' => 'Remove, wash, and rehang curtains or clean blinds.',
                'points' => 45,
            ],
            [
                'name' => 'Deep Clean Carpets',
                'description' => 'Vacuum thoroughly and spot clean any stains on carpets.',
                'points' => 50,
            ],
        ];

        foreach ($monthlyChores as $chore) {
            Chore::firstOrCreate(
                [
                    'family_id' => $family->id,
                    'name' => $chore['name'],
                ],
                array_merge($chore, [
                    'frequency' => 'monthly',
                    'review_required' => true,
                    'photos_required' => $chore['photos_required'] ?? 'none',
                ])
            );
        }

        // Create adhoc chores
        $adhocChores = [
            [
                'name' => 'Rake Leaves',
                'description' => 'Rake and bag leaves in the yard.',
                'points' => 40,
            ],
            [
                'name' => 'Shovel Snow',
                'description' => 'Shovel snow from driveways and walkways.',
                'points' => 50,
            ],
            [
                'name' => 'Wash Car',
                'description' => 'Wash and vacuum the family car inside and out.',
                'points' => 45,
                'photos_required' => 'only_after',
            ],
            [
                'name' => 'Organize Pantry',
                'description' => 'Organize pantry, check expiration dates, and dispose of expired items.',
                'points' => 35,
            ],
            [
                'name' => 'Clean Out Closet',
                'description' => 'Deep clean and organize a specific closet, removing items to donate.',
                'points' => 50,
            ],
            [
                'name' => 'Paint Room',
                'description' => 'Paint a room or touch up paint as needed.',
                'points' => 100,
                'photos_required' => 'before_and_after',
                'review_required' => true,
            ],
        ];

        foreach ($adhocChores as $chore) {
            Chore::firstOrCreate(
                [
                    'family_id' => $family->id,
                    'name' => $chore['name'],
                ],
                array_merge($chore, [
                    'frequency' => 'adhoc',
                    'review_required' => $chore['review_required'] ?? false,
                    'photos_required' => $chore['photos_required'] ?? 'none',
                ])
            );
        }

        $this->command->info('All chores created successfully!');
        $this->command->info('Daily chores: ' . count($dailyChores));
        $this->command->info('Twice weekly chores: ' . count($twiceWeeklyChores));
        $this->command->info('Weekly chores: ' . count($weeklyChores));
        $this->command->info('Twice monthly chores: ' . count($twiceMonthlyChores));
        $this->command->info('Monthly chores: ' . count($monthlyChores));
        $this->command->info('Adhoc chores: ' . count($adhocChores));
    }
}

