<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ScrapeCacheManager extends Command
{
    protected $signature = 'scrape:cache {action=status} {--force : Force action}';
    protected $description = 'Manage tea scraping cache';

    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'status':
                return $this->showStatus();
            case 'clear':
                return $this->clearCache();
            case 'clear-expired':
                return $this->clearExpired();
            case 'refresh':
                return $this->refreshCache();
            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: status, clear, clear-expired, refresh');
                return self::FAILURE;
        }
    }

    private function showStatus()
    {
        $this->info('=== Tea Scraping Cache Status ===');

        // Main results cache
        $mainCacheKey = 'tea_scraping_results';
        if (Cache::has($mainCacheKey)) {
            $cached = Cache::get($mainCacheKey);
            $this->info('Main Cache: VALID');
            $this->line('Cached at: ' . $cached['timestamp']);
            $this->line('Cache expires: ' . now()->addHours(24)->toDateTimeString());
            $this->line('Total teas cached: ' . ($cached['data']['total_teas'] ?? 0));
            $this->line('Requests made: ' . ($cached['data']['request_count'] ?? 0));
        } else {
            $this->info('Main Cache: EMPTY');
        }

        // Individual URL caches
        $urlCaches = $this->getUrlCaches();
        $this->newLine();
        $this->info('URL Caches: ' . count($urlCaches) . ' entries');

        foreach ($urlCaches as $key => $info) {
            $this->line("  - {$key}: {$info['size']} chars, expires {$info['expires']}");
        }

        // Cache statistics
        $this->newLine();
        $this->info('Cache Statistics:');
        $this->line('Total cache entries: ' . count($urlCaches) + (Cache::has($mainCacheKey) ? 1 : 0));
        $this->line('Cache driver: ' . config('cache.default'));
        $this->line('Cache prefix: ' . config('cache.prefix'));

        return self::SUCCESS;
    }

    private function clearCache()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to clear all scraping cache?')) {
                $this->info('Cache clear cancelled.');
                return self::SUCCESS;
            }
        }

        $cleared = 0;

        // Clear main results cache
        if (Cache::forget('tea_scraping_results')) {
            $cleared++;
            $this->info('Cleared main results cache');
        }

        // Clear individual URL caches
        $urlCaches = $this->getUrlCaches();
        foreach ($urlCaches as $key => $info) {
            if (Cache::forget($key)) {
                $cleared++;
            }
        }

        $this->info("Cleared {$cleared} cache entries");
        Log::info('Tea scraping cache cleared', ['entries' => $cleared]);

        return self::SUCCESS;
    }

    private function clearExpired()
    {
        $cleared = 0;
        $urlCaches = $this->getUrlCaches();

        foreach ($urlCaches as $key => $info) {
            if ($info['ttl'] <= 0) {
                if (Cache::forget($key)) {
                    $cleared++;
                }
            }
        }

        // Check main cache
        if (Cache::has('tea_scraping_results') && Cache::ttl('tea_scraping_results') <= 0) {
            if (Cache::forget('tea_scraping_results')) {
                $cleared++;
            }
        }

        $this->info("Cleared {$cleared} expired cache entries");
        Log::info('Expired tea scraping cache cleared', ['entries' => $cleared]);

        return self::SUCCESS;
    }

    private function refreshCache()
    {
        $this->info('Refreshing scraping cache...');

        // Clear existing cache
        $this->clearCache();

        // Run the scraper to refresh cache
        $exitCode = $this->call('scrape:robust-tea', [
            '--force' => true,
            '--delay' => 2
        ]);

        if ($exitCode === 0) {
            $this->info('Cache refreshed successfully');
        } else {
            $this->error('Cache refresh failed');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function getUrlCaches()
    {
        $caches = [];
        
        // This is a simplified approach - in production you might want to use cache tags
        // or maintain a registry of cache keys
        
        // For now, we'll check common patterns
        $patterns = [
            'scrape_' . md5('https://simplelooseleaf.com/blogs/news/herbal-tea-list-benefits'),
            'scrape_' . md5('https://theteahouseonlosrios.com/blogs/news/the-power-of-tea-100-health-and-wellness-benefits'),
            'scrape_' . md5('https://www.nutritionadvance.com/healthy-foods/types-of-tea/'),
            'scrape_' . md5('https://en.wikipedia.org/wiki/Tea'),
            'scrape_' . md5('https://en.wikipedia.org/wiki/Herbal_tea'),
        ];

        foreach ($patterns as $key) {
            if (Cache::has($key)) {
                $content = Cache::get($key);
                $caches[$key] = [
                    'size' => strlen($content),
                    'expires' => now()->addHours(24)->toDateTimeString()
                ];
            }
        }

        return $caches;
    }
}
