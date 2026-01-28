<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Goutte\Client;
use App\Models\Tea;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpClient\HttpClient;

class RobustTeaScraper extends Command
{
    protected $signature = 'scrape:robust-tea {--source=all} {--force : Force refresh cache} {--delay=3 : Delay between requests in seconds}';
    protected $description = 'Robust tea scraper with caching, delays, and anti-blocking measures';

    protected $teaPlaceholders = [
        'https://images.unsplash.com/photo-1564890369478-c89ca6d9cde9?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1597318181409-cf64d0b5d8a2?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1571934811356-5cc061b6821f?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1558160074-4d7d8bdf4256?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1563822249366-3efb23b8e0c9?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1576092768241-dec231879fc3?w=600&h=400&fit=crop',
    ];

    protected $created = 0;
    protected $updated = 0;
    protected $skipped = 0;
    protected $delay = 3;
    protected $cacheTtl = 86400; // 24 hours
    
    private $requestCount = 0;
    private $lastRequestTime = 0;

    public function handle()
    {
        // Prevent PHP timeout - set unlimited time for scraping
        set_time_limit(300); // 5 minutes max
        
        $this->delay = (int) $this->option('delay');
        $forceRefresh = $this->option('force');
        
        $this->info('Starting robust tea scraping with caching...');
        $this->info('Request delay: ' . $this->delay . ' seconds');
        $this->info('Cache TTL: ' . $this->cacheTtl . ' seconds');
        
        // Clear HTTP response caches if force refresh is enabled
        if ($forceRefresh) {
            $this->info('Force refresh enabled - clearing all caches...');
            $this->clearHttpCaches();
            Cache::forget('tea_scraping_results');
            
            // Use shorter delays for force refresh to prevent timeout
            if ($this->delay > 2) {
                $this->delay = 2;
                $this->info('Adjusted delay to 2s for faster force refresh');
            }
        }
        
        // Check cache first
        $cacheKey = 'tea_scraping_results';
        if (!$forceRefresh && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            $this->info('Using cached data from: ' . $cached['timestamp']);
            $this->applyCachedResults($cached['data']);
            return self::SUCCESS;
        }
        
        // Strategy 1: Try direct scraping first
        $this->tryDirectScraping();
        
        // Strategy 2: Use alternative sources if direct scraping fails
        $this->tryAlternativeSources();
        
        // Strategy 3: Use curated knowledge base as last resort
        $this->useKnowledgeBase();
        
        // Cache the results
        $this->cacheResults();
        
        $this->newLine();
        $this->info('Robust tea scraping completed.');
        $this->line('Created: ' . $this->created);
        $this->line('Updated: ' . $this->updated);
        $this->line('Skipped: ' . $this->skipped);
        $this->line('Total requests: ' . $this->requestCount);
        
        return self::SUCCESS;
    }
    
    private function tryDirectScraping()
    {
        $this->info('Attempting direct scraping...');
        
        $sources = [
            'simpleleaf' => 'https://simplelooseleaf.com/blogs/news/herbal-tea-list-benefits',
            'teahouse' => 'https://theteahouseonlosrios.com/blogs/news/the-power-of-tea-100-health-and-wellness-benefits',
            'nutrition' => 'https://www.nutritionadvance.com/healthy-foods/types-of-tea/',
        ];
        
        foreach ($sources as $name => $url) {
            $this->line("  Trying: {$name}");
            if ($this->scrapeWithFallback($url, $name)) {
                $this->line("  ✓ Success with {$name}");
                return true;
            }
            // Add delay between different sources
            $this->addDelay();
        }
        
        $this->line('  ✗ All direct scraping attempts failed');
        return false;
    }
    
    private function scrapeWithFallback($url, $sourceName)
    {
        // Check cache first
        $cacheKey = 'scrape_' . md5($url);
        if (Cache::has($cacheKey)) {
            $this->line('    Using cached content for ' . $url);
            $cachedHtml = Cache::get($cacheKey);
            $client = $this->createRobustClient();
            $crawler = new \Symfony\Component\DomCrawler\Crawler($cachedHtml);
            return $this->extractTeaData($crawler, $sourceName);
        }
        
        // Retry logic with exponential backoff (reduced for faster execution)
        $maxRetries = 2;  // Reduced from 3 to 2
        $baseDelay = 1;   // Reduced from 2 to 1
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                // Add delay between requests (longer for retries)
                $retryDelay = $attempt > 1 ? $baseDelay * pow(2, $attempt - 1) : $this->delay;
                $this->addDelay($retryDelay);
                
                $client = $this->createRobustClient();
                
                $this->requestCount++;
                $this->line("    Request #{$this->requestCount} (attempt {$attempt}/{$maxRetries}) to: " . $url);
                
                $crawler = $client->request('GET', $url);
                $html = $crawler->html();
                
                // Cache the successful response
                Cache::put($cacheKey, $html, $this->cacheTtl);
                
                // Validate response
                $validation = $this->validateResponse($html);
                if (!$validation['valid']) {
                    $this->line("    Invalid response: " . $validation['reason']);
                    if ($attempt === $maxRetries) {
                        return false;
                    }
                    continue;
                }
                
                // Try to extract tea data
                $result = $this->extractTeaData($crawler, $sourceName);
                if ($result) {
                    $this->line("    Success on attempt {$attempt}");
                    return true;
                }
                
                // If extraction failed but response was valid, try alternative extraction
                if ($attempt < $maxRetries) {
                    $this->line("    Extraction failed, trying alternative method...");
                    $this->line("    Waiting " . ($baseDelay * $attempt) . " seconds before retry...");
                    sleep($baseDelay * $attempt);
                }
                
            } catch (\Exception $e) {
                $this->line("    Attempt {$attempt} failed: " . $e->getMessage());
                
                if ($attempt === $maxRetries) {
                    $this->error("    All attempts failed for {$url}");
                    Log::error('Tea scraping failed', [
                        'url' => $url,
                        'attempts' => $maxRetries,
                        'error' => $e->getMessage()
                    ]);
                    return false;
                }
                
                // Wait before retry with exponential backoff
                $waitTime = $baseDelay * pow(2, $attempt - 1);
                $this->line("    Waiting {$waitTime} seconds before retry...");
                sleep($waitTime);
            }
        }
        
        return false;
    }
    
    /**
     * Validate HTTP response content
     */
    private function validateResponse($html)
    {
        // Check if content is too short
        if (strlen($html) < 100) {
            return ['valid' => false, 'reason' => 'Content too short'];
        }
        
        // Check if content contains readable text
        if (!preg_match('/[a-zA-Z]/', $html)) {
            return ['valid' => false, 'reason' => 'No readable text found'];
        }
        
        // Check for common blocking indicators
        $blockingIndicators = [
            'access denied',
            'blocked',
            'captcha',
            'robot check',
            'rate limit',
            'too many requests'
        ];
        
        foreach ($blockingIndicators as $indicator) {
            if (stripos($html, $indicator) !== false) {
                return ['valid' => false, 'reason' => 'Access blocked: ' . $indicator];
            }
        }
        
        // Check for tea-related content
        $teaKeywords = ['tea', 'herbal', 'chamomile', 'peppermint', 'ginger'];
        $foundTeaContent = false;
        foreach ($teaKeywords as $keyword) {
            if (stripos($html, $keyword) !== false) {
                $foundTeaContent = true;
                break;
            }
        }
        
        if (!$foundTeaContent) {
            return ['valid' => false, 'reason' => 'No tea-related content found'];
        }
        
        return ['valid' => true, 'reason' => 'Valid content'];
    }
    
    private function extractTeaData($crawler, $sourceName)
    {
        $teaCount = 0;
        
        // Try multiple extraction strategies
        $strategies = [
            'extractFromHeadings',
            'extractFromLists',
            'extractFromParagraphs',
            'extractFromStructuredData'
        ];
        
        foreach ($strategies as $strategy) {
            $count = $this->$strategy($crawler);
            if ($count > 0) {
                $teaCount += $count;
                $this->line("    ✓ {$strategy} found {$count} teas");
                break; // Stop at first successful strategy
            }
        }
        
        return $teaCount > 0;
    }
    
    private function extractFromHeadings($crawler)
    {
        $count = 0;
        $headings = $crawler->filter('h1, h2, h3, h4, h5, h6');
        
        $headings->each(function ($node) use (&$count) {
            $text = trim($node->text(''));
            if ($this->looksLikeTeaName($text)) {
                $benefit = $this->extractFollowingContent($node);
                $this->saveTea($text, 'Herbal', 'Caffeine-free', $benefit);
                $count++;
            }
        });
        
        return $count;
    }
    
    private function extractFromLists($crawler)
    {
        $count = 0;
        $lists = $crawler->filter('ul, ol');
        
        $lists->each(function ($list) use (&$count) {
            $items = $list->filter('li');
            $items->each(function ($item) use (&$count) {
                $text = trim($item->text(''));
                if ($this->looksLikeTeaName($text)) {
                    $benefit = $this->extractBenefitFromText($text);
                    $this->saveTea($text, 'Herbal', 'Caffeine-free', $benefit);
                    $count++;
                }
            });
        });
        
        return $count;
    }
    
    private function extractFromParagraphs($crawler)
    {
        $count = 0;
        $paragraphs = $crawler->filter('p');
        
        $paragraphs->each(function ($p) use (&$count) {
            $text = trim($p->text(''));
            $teaNames = $this->extractTeaNamesFromText($text);
            
            foreach ($teaNames as $teaName) {
                $benefit = $this->extractBenefitFromText($text);
                $this->saveTea($teaName, 'Herbal', 'Caffeine-free', $benefit);
                $count++;
            }
        });
        
        return $count;
    }
    
    private function extractFromStructuredData($crawler)
    {
        // Look for JSON-LD or other structured data
        $scripts = $crawler->filter('script[type="application/ld+json"]');
        $count = 0;
        
        $scripts->each(function ($script) use (&$count) {
            try {
                $data = json_decode($script->text(), true);
                $count += $this->extractFromJsonLd($data);
            } catch (\Exception $e) {
                // Ignore JSON parsing errors
            }
        });
        
        return $count;
    }
    
    private function looksLikeTeaName($text)
    {
        $teaIndicators = ['tea', 'herb', 'infusion', 'tisane'];
        $commonTeas = ['chamomile', 'peppermint', 'ginger', 'lavender', 'green', 'black', 'white', 'oolong'];
        
        $text = strtolower($text);
        
        // Check for tea indicators
        foreach ($teaIndicators as $indicator) {
            if (strpos($text, $indicator) !== false) {
                return true;
            }
        }
        
        // Check for common tea names
        foreach ($commonTeas as $tea) {
            if (strpos($text, $tea) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function extractTeaNamesFromText($text)
    {
        $teaNames = [];
        $commonTeas = ['chamomile', 'peppermint', 'ginger', 'lavender', 'green tea', 'black tea', 'white tea', 'oolong tea'];
        
        foreach ($commonTeas as $tea) {
            if (stripos($text, $tea) !== false) {
                $teaNames[] = ucwords($tea);
            }
        }
        
        return array_unique($teaNames);
    }
    
    private function extractBenefitFromText($text)
    {
        // Look for benefit-related keywords
        $benefitKeywords = ['help', 'benefit', 'aid', 'support', 'reduce', 'improve', 'promote'];
        
        $sentences = preg_split('/[.!?]+/', $text);
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (strlen($sentence) > 10) {
                foreach ($benefitKeywords as $keyword) {
                    if (stripos($sentence, $keyword) !== false) {
                        return substr($sentence, 0, 200);
                    }
                }
            }
        }
        
        return 'Herbal benefits and wellness support';
    }
    
    private function extractFollowingContent($node)
    {
        $content = '';
        $next = $node->nextAll();
        
        for ($i = 0; $i < 3; $i++) {
            if ($next->count() > $i) {
                $text = trim($next->eq($i)->text(''));
                if (strlen($text) > 10) {
                    $content = $text;
                    break;
                }
            }
        }
        
        return $content ?: 'Natural health benefits';
    }
    
    private function tryAlternativeSources()
    {
        $this->info('Trying alternative data sources...');
        
        // Use public APIs or other reliable sources
        $alternativeSources = [
            'wikipedia_tea' => 'https://en.wikipedia.org/wiki/Tea',
            'wikipedia_herbal_tea' => 'https://en.wikipedia.org/wiki/Herbal_tea',
        ];
        
        foreach ($alternativeSources as $name => $url) {
            $this->line("  Trying: {$name}");
            if ($this->scrapeWithFallback($url, $name)) {
                $this->line("  ✓ Success with {$name}");
                return true;
            }
            // Add delay between alternative sources
            $this->addDelay();
        }
        
        return false;
    }
    
    private function useKnowledgeBase()
    {
        $this->info('Using curated knowledge base...');
        
        // Comprehensive tea database as fallback
        $teaDatabase = [
            'Chamomile Tea' => [
                'benefit' => 'Promotes relaxation, reduces anxiety, improves sleep quality, anti-inflammatory properties',
                'caffeine' => 'Caffeine-free',
                'flavor' => 'Floral, apple-like'
            ],
            'Peppermint Tea' => [
                'benefit' => 'Aids digestion, relieves stomach discomfort, freshens breath, mental clarity',
                'caffeine' => 'Caffeine-free',
                'flavor' => 'Minty, refreshing'
            ],
            'Ginger Tea' => [
                'benefit' => 'Reduces nausea, anti-inflammatory, supports digestion, boosts immunity',
                'caffeine' => 'Caffeine-free',
                'flavor' => 'Spicy, warming'
            ],
            'Green Tea' => [
                'benefit' => 'Rich in antioxidants, boosts metabolism, supports brain function, heart health',
                'caffeine' => 'Low-Medium',
                'flavor' => 'Grassy, fresh'
            ],
            'Black Tea' => [
                'benefit' => 'Energy boost, heart health support, improved focus, antioxidant properties',
                'caffeine' => 'Medium-High',
                'flavor' => 'Bold, robust'
            ],
            'White Tea' => [
                'benefit' => 'Highest antioxidant content, skin health, anti-aging, minimal processing',
                'caffeine' => 'Low',
                'flavor' => 'Delicate, subtle'
            ],
            'Oolong Tea' => [
                'benefit' => 'Weight management, stress reduction, bone health, blood sugar regulation',
                'caffeine' => 'Medium',
                'flavor' => 'Complex, floral'
            ],
            'Lavender Tea' => [
                'benefit' => 'Calming effects, stress relief, sleep aid, mood enhancement',
                'caffeine' => 'Caffeine-free',
                'flavor' => 'Floral, aromatic'
            ],
            'Rooibos Tea' => [
                'benefit' => 'Rich in antioxidants, caffeine-free alternative, bone health support',
                'caffeine' => 'Caffeine-free',
                'flavor' => 'Sweet, earthy'
            ],
            'Echinacea Tea' => [
                'benefit' => 'Immune system support, cold and flu prevention, anti-inflammatory',
                'caffeine' => 'Caffeine-free',
                'flavor' => 'Floral, slightly sweet'
            ],
            'Hibiscus Tea' => [
                'benefit' => 'Blood pressure regulation, liver health support, rich in vitamin C',
                'caffeine' => 'Caffeine-free',
                'flavor' => 'Tart, cranberry-like'
            ],
            'Turmeric Tea' => [
                'benefit' => 'Powerful anti-inflammatory, joint health, antioxidant, immune support',
                'caffeine' => 'Caffeine-free',
                'flavor' => 'Earthy, spicy'
            ],
            'Lemon Balm Tea' => [
                'benefit' => 'Reduces stress and anxiety, improves sleep, cognitive function support',
                'caffeine' => 'Caffeine-free',
                'flavor' => 'Lemony, mild'
            ],
            'Matcha Tea' => [
                'benefit' => 'Enhanced focus, calm energy, high antioxidant content, metabolism boost',
                'caffeine' => 'Medium-High',
                'flavor' => 'Rich, umami'
            ],
            'Yerba Mate Tea' => [
                'benefit' => 'Natural energy boost, nutrient-rich, mental clarity, physical performance',
                'caffeine' => 'Medium-High',
                'flavor' => 'Earthy, vegetal'
            ],
        ];
        
        foreach ($teaDatabase as $name => $data) {
            $this->saveTea($name, $data['flavor'], $data['caffeine'], $data['benefit']);
        }
        
        $this->line('  ✓ Added ' . count($teaDatabase) . ' teas from knowledge base');
    }
    
    private function createRobustClient()
    {
        // Rotate user agents
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/121.0',
        ];
        
        // Create Symfony HttpClient - it automatically adds Accept-Encoding and handles decompression
        $httpClient = HttpClient::create([
            'headers' => [
                'User-Agent' => $userAgents[array_rand($userAgents)],
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'DNT' => '1',
                'Connection' => 'keep-alive',
            ],
            'max_redirects' => 5,
            'timeout' => 30,
        ]);
        
        // Create Goutte client with Symfony HttpClient
        $client = new Client($httpClient);
        
        return $client;
    }
    
    private function extractFromJsonLd($data)
    {
        $count = 0;
        
        if (isset($data['@graph'])) {
            foreach ($data['@graph'] as $item) {
                if (isset($item['name']) && $this->looksLikeTeaName($item['name'])) {
                    $benefit = $item['description'] ?? 'Natural health benefits';
                    $this->saveTea($item['name'], 'Herbal', 'Caffeine-free', $benefit);
                    $count++;
                }
            }
        }
        
        return $count;
    }
    
    private function saveTea($name, $flavor, $caffeine, $benefit)
    {
        // Check if this tea was deleted by admin - if so, skip it entirely
        if (\App\Models\DeletedTea::wasDeleted($name)) {
            $this->skipped++;
            $this->line("  ⚠️  Skipping '{$name}' - was deleted by admin");
            return;
        }
        
        $placeholderImage = $this->teaPlaceholders[$this->created % count($this->teaPlaceholders)];
        
        $tea = Tea::firstOrNew(['name' => $name]);
        $wasCreated = !$tea->exists;
        
        $tea->source = 'scraped';
        $tea->flavor = $flavor;
        $tea->caffeine_level = $caffeine;
        $tea->health_benefit = $benefit;
        $tea->image = $placeholderImage;
        
        $tea->save();
        
        if ($wasCreated) {
            $this->created++;
        } else {
            $this->updated++;
        }
    }
    
    /**
     * Add delay between requests to prevent blocking
     */
    private function addDelay($customDelay = null)
    {
        $delay = $customDelay ?? $this->delay;
        
        if ($this->lastRequestTime > 0) {
            $timeSinceLast = time() - $this->lastRequestTime;
            if ($timeSinceLast < $delay) {
                $sleepTime = $delay - $timeSinceLast;
                $this->line("    Waiting {$sleepTime} seconds to prevent blocking...");
                sleep($sleepTime);
            }
        } else {
            // First request, wait the full delay
            $this->line("    Waiting {$delay} seconds before first request...");
            sleep($delay);
        }
        
        $this->lastRequestTime = time();
    }
    
    /**
     * Cache the scraping results
     */
    private function cacheResults()
    {
        $cacheKey = 'tea_scraping_results';
        $cacheData = [
            'timestamp' => now()->toDateTimeString(),
            'data' => [
                'created' => $this->created,
                'updated' => $this->updated,
                'skipped' => $this->skipped,
                'request_count' => $this->requestCount,
                'total_teas' => Tea::where('source', 'scraped')->count()
            ]
        ];
        
        Cache::put($cacheKey, $cacheData, $this->cacheTtl);
        $this->info('Results cached until: ' . now()->addSeconds($this->cacheTtl)->toDateTimeString());
    }
    
    /**
     * Apply cached results to the current session
     */
    private function applyCachedResults($cachedData)
    {
        $this->created = $cachedData['created'] ?? 0;
        $this->updated = $cachedData['updated'] ?? 0;
        $this->skipped = $cachedData['skipped'] ?? 0;
        $this->requestCount = $cachedData['request_count'] ?? 0;
        
        $this->line('Created: ' . $this->created);
        $this->line('Updated: ' . $this->updated);
        $this->line('Skipped: ' . $this->skipped);
        $this->line('Total requests: ' . $this->requestCount);
        $this->line('Total teas in database: ' . ($cachedData['total_teas'] ?? 0));
    }
    
    /**
     * Get cache statistics
     */
    private function getCacheStats()
    {
        $cacheKey = 'tea_scraping_results';
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            $this->info('Cache Status: VALID');
            $this->line('Cached at: ' . $cached['timestamp']);
            $this->line('Expires at: ' . now()->addSeconds(Cache::ttl($cacheKey))->toDateTimeString());
            return true;
        }
        
        $this->info('Cache Status: EMPTY');
        return false;
    }
    
    /**
     * Clear all HTTP response caches for scraping
     */
    private function clearHttpCaches()
    {
        $sources = [
            'https://simplelooseleaf.com/blogs/news/herbal-tea-list-benefits',
            'https://theteahouseonlosrios.com/blogs/news/the-power-of-tea-100-health-and-wellness-benefits',
            'https://www.nutritionadvance.com/healthy-foods/types-of-tea/',
            'https://en.wikipedia.org/wiki/Tea',
            'https://en.wikipedia.org/wiki/Herbal_tea',
        ];
        
        $cleared = 0;
        foreach ($sources as $url) {
            $cacheKey = 'scrape_' . md5($url);
            if (Cache::has($cacheKey)) {
                Cache::forget($cacheKey);
                $cleared++;
            }
        }
        
        if ($cleared > 0) {
            $this->line("  Cleared {$cleared} HTTP response caches");
        } else {
            $this->line('  No HTTP caches to clear');
        }
    }
}
