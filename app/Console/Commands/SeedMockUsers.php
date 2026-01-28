<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Preference;
use App\Models\Tea;
use App\Models\Rating;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SeedMockUsers extends Command
{
    protected $signature = 'seed:mock-users {count=20 : Number of users to create} {--clear : Clear existing mock users first}';
    protected $description = 'Seed the database with mock users and random preferences for testing recommendations';

    private $flavors = ['fruity', 'herbal', 'bitter', 'sweet', 'spicy', 'earthy', 'minty', 'floral'];
    private $caffeine = ['caffeine_free', 'low', 'medium', 'high'];
    private $healthGoals = ['relax_calm', 'digest', 'stress', 'weight_loss', 'blood_circulation', 'body_relief', 'energy', 'immune'];
    private $cities = ['Kuala Lumpur', 'George Town', 'Ipoh', 'Kuching', 'Johor Bahru', 'Kota Kinabalu', 'Malacca City'];

    public function handle()
    {
        if ($this->option('clear')) {
            $this->clearMockUsers();
        }

        $count = $this->argument('count');
        $this->info("Creating {$count} mock users...");

        $teas = Tea::all();
        if ($teas->isEmpty()) {
            $this->error('No teas found in database. Please scrape tea data first.');
            return;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($i = 0; $i < $count; $i++) {
            $user = $this->createUser();
            $this->createPreference($user);
            $this->createRandomRatings($user, $teas);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully seeded {$count} mock users with preferences and ratings.");
    }

    private function createUser()
    {
        $name = "Mock User " . Str::random(5);
        $email = "mock_" . Str::random(8) . "@example.com";

        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
    }

    private function createPreference(User $user)
    {
        $flavor = $this->flavors[array_rand($this->flavors)];
        $caffeine = $this->caffeine[array_rand($this->caffeine)];
        $healthGoal = $this->healthGoals[array_rand($this->healthGoals)];
        $city = $this->cities[array_rand($this->cities)];

        return Preference::create([
            'user_id' => $user->id,
            'preferred_flavor' => $flavor,
            'preferred_caffeine' => $caffeine,
            'health_goal' => $healthGoal,
            'city' => $city,
            'country' => 'Malaysia',
            'weather_based_recommendations' => true,
            'weather_preference' => 'auto',
        ]);
    }

    private function createRandomRatings(User $user, $teas)
    {
        $pref = $user->preference;
        // Each user rates 5-15 teas
        $numRatings = rand(5, 15);
        $sampledTeas = $teas->random(min($numRatings, $teas->count()));

        foreach ($sampledTeas as $tea) {
            $rating = $this->calculateRating($tea, $pref);
            
            Rating::create([
                'user_id' => $user->id,
                'tea_id' => $tea->id,
                'rating' => $rating,
                'comment' => 'Automated test rating.',
                'source' => 'manual'
            ]);
        }
    }

    private function calculateRating($tea, $pref)
    {
        // Base rating 1-3
        $score = rand(1, 3);

        // Boost score if it matches preferences (making data more "realistic" for the engine)
        if (str_contains(strtolower($tea->flavor), strtolower($pref->preferred_flavor))) {
            $score += 1;
        }

        // Check caffeine
        $teaCaffeine = strtolower($tea->caffeine_level);
        if ($pref->preferred_caffeine === 'caffeine_free' && str_contains($teaCaffeine, 'free')) {
            $score += 1;
        } elseif ($pref->preferred_caffeine === 'high' && str_contains($teaCaffeine, 'high')) {
            $score += 1;
        }

        // Add some noise/randomness
        if (rand(1, 10) > 8) {
            $score = rand(1, 5); // 20% chance of completely random rating
        }

        return min(5, max(1, $score));
    }

    private function clearMockUsers()
    {
        $this->info("Clearing existing mock users...");
        $mockUsers = User::where('email', 'like', 'mock_%@example.com')->get();
        foreach ($mockUsers as $user) {
            Rating::where('user_id', $user->id)->delete();
            Preference::where('user_id', $user->id)->delete();
            $user->delete();
        }
        $this->info("Cleared " . $mockUsers->count() . " users.");
    }
}
