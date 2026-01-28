<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ScrapeCacheStatus extends Command
{
    protected $signature = 'scrape:cache-status';
    protected $description = 'Show tea scraping cache status';

    public function handle()
    {
        $this->info('=== Tea Scraping Cache Status ===');
        
        $mainCacheKey = 'tea_scraping_results';
        
        if (Cache::has($mainCacheKey)) {
            $cached = Cache::get($mainCacheKey);
            $this->info('Cache Status: VALID');
            $this->line('Cached at: ' . $cached['timestamp']);
            $this->line('Cache expires: ' . now()->addHours(24)->toDateTimeString());
            $this->line('Total teas: ' . ($cached['data']['total_teas'] ?? 0));
            $this->line('Created: ' . ($cached['data']['created'] ?? 0));
            $this->line('Updated: ' . ($cached['data']['updated'] ?? 0));
            $this->line('Requests: ' . ($cached['data']['request_count'] ?? 0));
        } else {
            $this->info('Cache Status: EMPTY');
            $this->line('No cached scraping results found');
        }
        
        // Show individual URL caches
        $urlCaches = $this->getUrlCaches();
        $this->newLine();
        $this->info('URL Caches: ' . count($urlCaches) . ' entries');
        
        foreach ($urlCaches as $key => $info) {
            $this->line("  - {$key}: {$info['size']} chars, expires {$info['expires']}");
        }
        
        return self::SUCCESS;
    }
    
    private function getUrlCaches()
    {
        $caches = [];
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
