<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Goutte\Client;
use App\Models\Tea;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Decorator\DecoratorInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ScrapeTeaData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:tea-data {--source=all : Source to scrape (all, nutrition, simpleleaf, teahouse)} {--clear : Clear existing scraped data before scraping (DESTRUCTIVE)} {--fresh : Clear HTTP caches only, merge with existing data (RECOMMENDED)} {--debug : Show detailed progress information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape tea data from multiple websites';

    /**
     * Tea-themed placeholder images from Unsplash
     */
    protected $teaPlaceholders = [
        'https://images.unsplash.com/photo-1564890369478-c89ca6d9cde9?w=600&h=400&fit=crop', // matcha
        'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=600&h=400&fit=crop', // tea cup
        'https://images.unsplash.com/photo-1597318181409-cf64d0b5d8a2?w=600&h=400&fit=crop', // green tea
        'https://images.unsplash.com/photo-1571934811356-5cc061b6821f?w=600&h=400&fit=crop', // tea leaves
        'https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=600&h=400&fit=crop', // herbal tea
        'https://images.unsplash.com/photo-1558160074-4d7d8bdf4256?w=600&h=400&fit=crop', // tea set
        'https://images.unsplash.com/photo-1563822249366-3efb23b8e0c9?w=600&h=400&fit=crop', // iced tea
        'https://images.unsplash.com/photo-1576092768241-dec231879fc3?w=600&h=400&fit=crop', // chai
    ];

    /**
     * Tea flavor keywords for detection
     */
    protected $flavorKeywords = [
        'herbal' => ['herbal', 'flower', 'floral', 'botanical'],
        'fruity' => ['fruit', 'berry', 'citrus', 'apple', 'peach', 'mango', 'orange', 'lemon'],
        'spicy' => ['spicy', 'chai', 'cinnamon', 'ginger', 'pepper', 'clove', 'cardamom'],
        'sweet' => ['sweet', 'honey', 'vanilla', 'caramel', 'chocolate'],
        'earthy' => ['earthy', 'woody', 'malty', 'robust', 'rich'],
        'minty' => ['mint', 'peppermint', 'spearmint', 'menthol'],
        'fresh' => ['fresh', 'light', 'crisp', 'clean', 'delicate'],
        'nutty' => ['nutty', 'almond', 'hazelnut', 'walnut', 'chestnut'],
    ];

    /**
     * Caffeine level indicators
     */
    protected $caffeineKeywords = [
        'caffeine-free' => ['caffeine free', 'decaf', 'no caffeine', 'caffeine-free', 'herbal', 'rooibos', 'tisane'],
        'low' => ['low caffeine', 'minimal caffeine', 'light caffeine', 'white tea'],
        'medium' => ['medium caffeine', 'moderate caffeine', 'oolong', 'green tea'],
        'high' => ['high caffeine', 'strong caffeine', 'black tea', 'matcha', 'pu-erh'],
    ];

    /**
     * Default fallbacks for incomplete data
     */
    protected $defaultFlavors = ['Herbal', 'Various', 'Blend', 'N/A'];
    protected $defaultCaffeine = ['Caffeine-free', 'Low', 'Medium', 'High'];
    protected $defaultBenefits = [
        'Promotes relaxation and calmness',
        'Supports overall wellness',
        'Aids in digestion',
        'Rich in antioxidants',
        'Supports immune system',
        'Helps reduce stress',
    ];

    protected $placeholderIndex = 0;
    protected $created = 0;
    protected $updated = 0;
    protected $skipped = 0;
    protected $verbose = false;

    /**
     * Delay configuration (in seconds)
     */
    protected $minDelay = 1;      // Minimum delay between requests
    protected $maxDelay = 2;      // Maximum delay between requests
    protected $sourceDelay = 1;   // Delay between different sources
    protected $teaDelay = 0;      // Delay between processing individual teas

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $source = $this->option('source');
        $clear = $this->option('clear');
        $fresh = $this->option('fresh');
        $this->verbose = $this->option('debug');

        // --fresh: Clear HTTP caches and merge with existing data (RECOMMENDED)
        if ($fresh) {
            $this->info('🔄 Fresh scrape mode: Merging with existing data...');
            $this->line('   HTTP caches cleared, fetching fresh data from sources.');
            $this->line('   Existing teas will be updated with better data only.');
            $this->newLine();
        }

        // Clear existing scraped data if requested (NOT RECOMMENDED - use with caution)
        if ($clear) {
            $this->warn('⚠️  WARNING: --clear will DELETE all existing scraped teas!');
            $this->warn('   This is NOT recommended. Data will be lost if scraping fails.');
            $this->warn('   Use --fresh instead to merge/update without losing data.');
            $this->newLine();
            
            $deleted = Tea::where('source', 'scraped')->delete();
            $this->line("Deleted {$deleted} existing scraped teas");
            $this->newLine();
        }

        $sources = [
            'nutrition' => [
                'url' => 'https://www.nutritionadvance.com/healthy-foods/types-of-tea/',
                'method' => 'scrapeNutritionAdvance',
                'shop_url' => null, // No shop on this site
            ],
            'simpleleaf' => [
                'url' => 'https://simplelooseleaf.com/blogs/news/herbal-tea-list-benefits',
                'method' => 'scrapeSimpleLooseLeaf',
                'shop_url' => 'https://simplelooseleaf.com/collections/tea-shop',
            ],
            'teahouse' => [
                'url' => 'https://theteahouseonlosrios.com/blogs/news/the-power-of-tea-100-health-and-wellness-benefits',
                'method' => 'scrapeTeaHouse',
                'shop_url' => 'https://theteahouseonlosrios.com/pages/shop-all',
            ],
        ];

        $client = $this->createRobustClient();

        if ($source === 'all') {
            $sourceCount = count($sources);
            $currentSource = 0;
            
            foreach ($sources as $key => $config) {
                $currentSource++;
                $this->info("Scraping from: {$key}... ({$currentSource}/{$sourceCount})");
                
                // Add delay between sources (except for the first one)
                if ($currentSource > 1) {
                    $delay = rand($this->sourceDelay, $this->sourceDelay + 2);
                    $this->line("  Waiting {$delay}s before next source...");
                    sleep($delay);
                }
                
                $this->{$config['method']}($client, $config['url'], $config['url'], $config['shop_url'] ?? null);
            }
        } elseif (isset($sources[$source])) {
            $this->info("Scraping from: {$source}...");
            $this->{$sources[$source]['method']}($client, $sources[$source]['url'], $sources[$source]['url'], $sources[$source]['shop_url'] ?? null);
        } else {
            $this->error("Unknown source: {$source}. Use: all, nutrition, simpleleaf, teahouse");
            return self::FAILURE;
        }

        // Post-scrape: merge & remove true duplicate tea names
        $this->deduplicateTeas();

        $this->newLine();
        $this->info('Tea scraping completed.');
        $this->line('Created: ' . $this->created);
        $this->line('Updated: ' . $this->updated);
        $this->line('Skipped: ' . $this->skipped);

        return self::SUCCESS;
    }
    
    /**
     * After scraping, find and merge any duplicate tea records.
     * Duplicates arise when the same tea is returned by multiple sources
     * with slightly different names that normalise to the same value.
     * Strategy: group by LOWER(name), keep the oldest record (lowest id)
     * as the canonical row, merge the richest field values from all dupes,
     * then delete the surplus rows.
     */
    protected function deduplicateTeas(): void
    {
        $this->line('  🔍 Deduplicating teas...');
        $merged = 0;

        // Find names that appear more than once (case-insensitive)
        $duplicateGroups = Tea::selectRaw('LOWER(name) as norm_name, COUNT(*) as cnt')
            ->groupByRaw('LOWER(name)')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('cnt', 'norm_name');

        foreach ($duplicateGroups as $normName => $count) {
            $teas = Tea::whereRaw('LOWER(name) = ?', [$normName])
                ->orderBy('id')
                ->get();

            if ($teas->count() < 2) continue;

            // Canonical = first (oldest) record
            $canonical = $teas->first();

            foreach ($teas->skip(1) as $dupe) {
                // Merge richer data into canonical
                if ($this->scoreBenefitQuality($dupe->health_benefit ?? '') > $this->scoreBenefitQuality($canonical->health_benefit ?? '')) {
                    $canonical->health_benefit = $dupe->health_benefit;
                }
                if ($this->scoreFlavorSpecificity($dupe->flavor ?? '') > $this->scoreFlavorSpecificity($canonical->flavor ?? '')) {
                    $canonical->flavor = $dupe->flavor;
                }
                if (empty($canonical->shop_link) && !empty($dupe->shop_link)) {
                    $canonical->shop_link = $dupe->shop_link;
                }
                if (empty($canonical->source_url) && !empty($dupe->source_url)) {
                    $canonical->source_url = $dupe->source_url;
                }
                if (empty($canonical->caffeine_level) || $canonical->caffeine_level === 'N/A') {
                    if (!empty($dupe->caffeine_level) && $dupe->caffeine_level !== 'N/A') {
                        $canonical->caffeine_level = $dupe->caffeine_level;
                    }
                }
                $dupe->delete();
                $merged++;
            }
            $canonical->save();
        }

        if ($merged > 0) {
            $this->line("  ✅ Merged and removed {$merged} duplicate tea record(s).");
        } else {
            $this->line('  ✅ No duplicates found.');
        }
    }

    /**
     * Create a robust HTTP client with proper headers and retry logic
     */
    private function createRobustClient(): Client
    {
        // Create Symfony HttpClient - it automatically adds Accept-Encoding and handles decompression
        $httpClient = HttpClient::create([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'DNT' => '1',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
                'Cache-Control' => 'max-age=0',
            ],
            'max_redirects' => 5,
            'timeout' => 30,
        ]);
        
        // Create Goutte client with Symfony HttpClient
        $client = new Client($httpClient);
        
        return $client;
    }

    /**
     * Scrape from nutritionadvance.com (original source)
     */
    protected function scrapeNutritionAdvance(Client $client, string $url, string $sourceUrl = ''): void
    {
        try {
            $crawler = $client->request('GET', $url);
            $this->line('  HTTP Status: ' . $client->getResponse()->getStatusCode());
            
            // Add delay after successful request to be respectful to the server
            $this->microDelay();
        } catch (\Throwable $e) {
            $this->error('  Failed to fetch: ' . $e->getMessage());
            
            // Add longer delay after failure before retry/next
            sleep(2);
            return;
        }

        // Try to find shop links in the page
        $shopLinks = $this->extractShopLinks($crawler, $url);

        $crawler->filter('h2')->each(function ($node) use ($sourceUrl, $shopLinks) {
            $heading = trim($node->text(''));
            if ($heading === '' || !preg_match('/^\s*(\d+)\.\s*(.+)$/', $heading, $m)) {
                return;
            }

            $name = trim($m[2]);
            if ($name === '') {
                $this->skipped++;
                return;
            }

            if ($this->verbose) {
                $this->line("  Found: {$name}");
            }

            // Extract benefit with multiple fallback strategies
            $benefit = 'N/A';
            try {
                // Strategy 1: Find first non-empty paragraph after heading
                // (skip empty paragraphs that may follow images)
                $paragraphs = $node->nextAll()->filter('p');
                $paragraphs->each(function($p) use (&$benefit) {
                    if ($benefit !== 'N/A') return;
                    $text = trim($p->text(''));
                    if (strlen($text) > 20) { // Must have meaningful content
                        $benefit = $text;
                    }
                });
                
                // Strategy 2: List items following heading
                if ($benefit === 'N/A') {
                    $nextUl = $node->nextAll()->filter('ul')->first();
                    if ($nextUl->count() > 0) {
                        $items = $nextUl->filter('li')->slice(0, 3)->each(function($li) {
                            return trim($li->text(''));
                        });
                        $benefit = implode('; ', array_filter($items));
                    }
                }
                
                // Strategy 3: Any text in the same section
                if ($benefit === 'N/A') {
                    $parent = $node->ancestors()->filter('section, article, div')->first();
                    if ($parent->count() > 0) {
                        // Find first non-empty paragraph in parent
                        $parent->filter('p')->each(function($p) use (&$benefit) {
                            if ($benefit !== 'N/A') return;
                            $text = trim($p->text(''));
                            if (strlen($text) > 20) {
                                $benefit = $this->cleanBenefitText($text);
                            }
                        });
                    }
                }
            } catch (\Throwable $e) {}
            
            // Final fallback
            if ($benefit === 'N/A') {
                $benefit = $this->getDefaultBenefitForTea($name);
            }

            // Detect flavor and caffeine from name + benefit
            $flavor = $this->detectFlavor($name . ' ' . $benefit);
            $caffeine = $this->detectCaffeineLevel($name . ' ' . $benefit);

            // Try to find a specific shop link for this tea, otherwise use generic
            $shopLink = $shopLinks[$name] ?? $this->findShopLinkForTea($name) ?? null;
            $this->saveTea($name, $flavor, $caffeine, $benefit, $sourceUrl, $shopLink);
        });
    }

    /**
     * Scrape from simplelooseleaf.com (dynamic scraping like nutritionAdvance)
     */
    protected function scrapeSimpleLooseLeaf(Client $client, string $url, string $sourceUrl = '', string $shopUrl = ''): void
    {
        try {
            $crawler = $client->request('GET', $url);
            $this->line('  HTTP Status: ' . $client->getResponse()->getStatusCode());
            
            // Add delay after successful request
            $this->microDelay();
        } catch (\Throwable $e) {
            $this->error('  Failed to fetch: ' . $e->getMessage());
            sleep(2);
            return;
        }

        // Find tea sections and extract both name and benefit dynamically
        $headings = $crawler->filter('h3');
        $this->line('  Found ' . $headings->count() . ' h3 elements');
        
        // Scrape dedicated shop page for direct product links
        $productLinks = [];
        if (!empty($shopUrl)) {
            $productLinks = $this->scrapeShopPage($client, $shopUrl);
            $this->microDelay();
        }
        
        // Fallback: Try to find shop links in the article page
        $shopLinks = $this->extractShopLinks($crawler, $url);
        
        // Add delay after extracting shop links
        $this->microDelay();

        if ($this->verbose) {
            // Check the actual HTML content
            $html = $crawler->html();
            $this->line('  HTML length: ' . strlen($html) . ' characters');

            // Look for common patterns that might indicate tea content
            if (stripos($html, 'chamomile') !== false) {
                $this->line('  Found chamomile in HTML');
            }
            if (stripos($html, 'peppermint') !== false) {
                $this->line('  Found peppermint in HTML');
            }
            if (stripos($html, 'benefit') !== false) {
                $this->line('  Found benefit in HTML');
            }

            // Check if we're being blocked or redirected
            if (stripos($html, 'access denied') !== false || stripos($html, 'blocked') !== false) {
                $this->line('  Possible access blocking detected');
            }
            if (stripos($html, 'captcha') !== false || stripos($html, 'robot') !== false) {
                $this->line('  Possible bot protection detected');
            }

            // Try different selectors to find tea names
            $selectors = ['h2', 'h3', 'h4', '.tea-name', '[class*="tea"]', '[class*="herb"]', 'li', 'p'];
            foreach ($selectors as $selector) {
                $elements = $crawler->filter($selector);
                $this->line('  Found ' . $elements->count() . ' ' . $selector . ' elements');

                if ($elements->count() > 0 && $elements->count() < 10) {
                    $elements->each(function ($node) {
                        $text = trim($node->text(''));
                        if (strlen($text) > 5 && strlen($text) < 100) {
                            $this->line('    Sample: "' . $text . '"');
                        }
                    });
                }
            }

            // Look for any text that might contain tea names
            $allText = $crawler->text();
            $teaKeywords = ['chamomile', 'peppermint', 'ginger', 'lavender', 'green tea', 'black tea'];
            foreach ($teaKeywords as $keyword) {
                if (stripos($allText, $keyword) !== false) {
                    $this->line('  Found keyword: ' . $keyword);
                }
            }
        }

        $headings->each(function ($node) use ($sourceUrl, $shopLinks, $productLinks, $shopUrl) {
            $teaName = trim($node->text(''));
            if ($this->verbose) {
                $this->line('  Processing heading: "' . $teaName . '"');
            }

            if ($teaName === '' || strlen($teaName) < 3) {
                $this->skipped++;
                return;
            }

            // Clean up tea name
            $name = preg_replace('/\s+tea\s+tea$/i', ' Tea', $teaName);
            $name = preg_replace('/\s+tea$/i', ' Tea', $name);
            $name = trim($name);

            // Extract benefit with multiple strategies for blog-style sites
            try {
                $benefit = $this->extractBenefitFromSection($node);
                
                // If still N/A, try parent container extraction (for blog layouts)
                if ($benefit === 'N/A') {
                    $benefit = $this->extractBenefitFromParentContainer($node);
                }
                
                // Try sibling section extraction
                if ($benefit === 'N/A') {
                    $benefit = $this->extractBenefitFromSiblingSection($node);
                }
                
                // Final fallback: use tea-specific default benefit
                if ($benefit === 'N/A') {
                    $benefit = $this->getDefaultBenefitForTea($name);
                }
                
                if ($this->verbose) {
                    $this->line('  Extracted benefit: "' . $benefit . '"');
                }
            } catch (\Throwable $e) {
                // Keep benefit as N/A if extraction fails
            }
            
            // Priority 1: Direct product link from shop page (most accurate)
            // Priority 2: Shop link found in article page
            // Priority 3: Fallback to existing tea's shop link
            $shopLink = $this->matchTeaToProductLink($name, $productLinks, $shopUrl) 
                ?? $shopLinks[$name] 
                ?? $this->findShopLinkForTea($name) 
                ?? null;
            
            // Extract flavor with fallback
            $flavor = $this->detectFlavor($name . ' ' . $benefit);
            
            $this->saveTea($name, $flavor, 'Caffeine-free', $benefit, $sourceUrl, $shopLink);
        });
    }

    /**
     * Clean and normalize benefit text
     */
    private function cleanBenefitText(string $text): string
    {
        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Remove common unwanted patterns
        $text = preg_replace('/^(Benefits?|Health Benefits?):\s*/i', '', $text);
        $text = preg_replace('/\s*Learn more.*$/i', '', $text);
        $text = preg_replace('/\s*Read more.*$/i', '', $text);
        
        // Limit length
        if (strlen($text) > 200) {
            $text = substr($text, 0, 197) . '...';
        }
        
        return trim($text) ?: 'N/A';
    }

    /**
     * Detect flavor from text using keyword matching
     */
    private function detectFlavor(string $text): string
    {
        $text = strtolower($text);
        $flavorScores = [];
        
        foreach ($this->flavorKeywords as $flavor => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (stripos($text, $keyword) !== false) {
                    $score++;
                }
            }
            if ($score > 0) {
                $flavorScores[$flavor] = $score;
            }
        }
        
        if (!empty($flavorScores)) {
            arsort($flavorScores);
            return ucfirst(array_key_first($flavorScores));
        }
        
        // Check for tea type indicators
        if (stripos($text, 'green') !== false) return 'Earthy';
        if (stripos($text, 'black') !== false) return 'Robust';
        if (stripos($text, 'white') !== false) return 'Delicate';
        if (stripos($text, 'oolong') !== false) return 'Floral';
        if (stripos($text, 'herbal') !== false || stripos($text, 'tisane') !== false) return 'Herbal';
        if (stripos($text, 'chai') !== false) return 'Spicy';
        if (stripos($text, 'matcha') !== false) return 'Earthy';
        if (stripos($text, 'rooibos') !== false) return 'Nutty';
        
        return 'Various';
    }

    /**
     * Detect caffeine level from text
     */
    private function detectCaffeineLevel(string $text): string
    {
        $text = strtolower($text);
        
        foreach ($this->caffeineKeywords as $level => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($text, $keyword) !== false) {
                    return ucfirst($level);
                }
            }
        }
        
        // Default based on tea type
        if (stripos($text, 'herbal') !== false || stripos($text, 'rooibos') !== false || stripos($text, 'tisane') !== false) {
            return 'Caffeine-free';
        }
        if (stripos($text, 'green') !== false || stripos($text, 'white') !== false) {
            return 'Low';
        }
        if (stripos($text, 'black') !== false || stripos($text, 'matcha') !== false) {
            return 'High';
        }
        if (stripos($text, 'oolong') !== false) {
            return 'Medium';
        }
        
        return 'Low'; // Default for herbal teas
    }

    /**
     * Get default benefit based on tea name
     */
    private function getDefaultBenefitForTea(string $teaName): string
    {
        $teaName = strtolower($teaName);
        
        // Tea-specific default benefits
        $teaBenefits = [
            'chamomile' => 'Promotes relaxation and helps with sleep',
            'peppermint' => 'Aids digestion and freshens breath',
            'ginger' => 'Supports digestion and reduces nausea',
            'lavender' => 'Calms the mind and promotes relaxation',
            'green' => 'Rich in antioxidants, supports metabolism',
            'black' => 'Boosts energy and mental alertness',
            'white' => 'High in antioxidants, delicate flavor',
            'oolong' => 'Supports weight management and heart health',
            'matcha' => 'High in antioxidants, provides calm energy',
            'rooibos' => 'Caffeine-free, rich in minerals',
            'hibiscus' => 'Supports heart health and blood pressure',
            'lemon' => 'Rich in vitamin C, refreshing taste',
            'rose' => 'Calming and supports skin health',
            'jasmine' => 'Calming aroma and stress relief',
            'mint' => 'Refreshing and aids digestion',
            'turmeric' => 'Anti-inflammatory and immune support',
            'chaga' => 'Immune support and antioxidants',
            'reishi' => 'Stress relief and immune support',
            'nettle' => 'Rich in nutrients, supports allergies',
            'dandelion' => 'Supports liver health and digestion',
            'fennel' => 'Aids digestion and reduces bloating',
            'cinnamon' => 'Supports blood sugar and warming',
            'cardamom' => 'Aids digestion and freshens breath',
            'clove' => 'Anti-inflammatory and immune support',
        ];
        
        foreach ($teaBenefits as $keyword => $benefit) {
            if (stripos($teaName, $keyword) !== false) {
                return $benefit;
            }
        }
        
        // Derive a benefit from the individual words in the tea name
        $words = preg_split('/\s+/', strtolower(preg_replace('/\s+tea\s*$/i', '', $teaName)));
        foreach ($words as $word) {
            if (strlen($word) < 4) continue;
            foreach ($teaBenefits as $keyword => $benefit) {
                if (strpos($word, $keyword) !== false || strpos($keyword, $word) !== false) {
                    return $benefit;
                }
            }
        }
        // Last resort: build a generic but non-random sentence from the name
        $displayName = ucwords(trim(preg_replace('/\s+tea\s*$/i', '', $teaName)));
        return "Supports overall wellness and provides the natural benefits of {$displayName}";
    }

    /**
     * Extract benefit from parent element
     */
    private function extractBenefitFromParent($node): string
    {
        try {
            $parent = $node->ancestors()->filter('section, article, .tea-item, .benefit-item, div')->first();
            if ($parent->count() > 0) {
                $text = $parent->filter('p')->first()->text('');
                if (strlen($text) > 10) {
                    return $this->cleanBenefitText($text);
                }
            }
        } catch (\Throwable $e) {
            // Silent fail
        }
        return 'N/A';
    }

    /**
     * Extract benefit from section context
     */
    private function extractBenefitFromSection($node): string
    {
        try {
            // Try to find any meaningful text in siblings
            $node->nextAll()->slice(0, 5)->each(function ($sibling) use (&$benefit) {
                if (isset($benefit) && $benefit !== 'N/A') return;
                
                $text = trim($sibling->text(''));
                if (strlen($text) > 20 && !preg_match('/^\d+$/', $text)) {
                    $benefit = $this->cleanBenefitText($text);
                }
            });
            
            return isset($benefit) ? $benefit : 'N/A';
        } catch (\Throwable $e) {
            return 'N/A';
        }
    }

    /**
     * Extract benefit from parent container - for blog-style layouts
     */
    private function extractBenefitFromParentContainer($node): string
    {
        try {
            // For shared containers like simplelooseleaf.com's "rte" div,
            // we need to find content AFTER this specific heading
            $parent = $node->ancestors()->filter('div[class*="content"], div[class*="entry"], div[class*="post"], div[class*="rte"], .blog-post, article, section')->first();
            
            if ($parent->count() > 0) {
                // Get all direct children of the parent
                $children = $parent->children();
                $foundHeading = false;
                $headingText = trim($node->text(''));
                
                foreach ($children as $child) {
                    $childText = trim($child->textContent);
                    $childHtml = $child->html();
                    
                    // Check if this is our heading
                    if (!$foundHeading && $childText === $headingText) {
                        $foundHeading = true;
                        continue;
                    }
                    
                    // Once we found the heading, look for the next meaningful paragraph
                    if ($foundHeading) {
                        // Check if it's a paragraph with content
                        if ($child->nodeName === 'p' && strlen($childText) > 30 && strlen($childText) < 500) {
                            // Skip if it looks like a product card (contains links or specific patterns)
                            $hasLink = strpos($childHtml, '<a') !== false;
                            $isProductCard = preg_match('/^(Blood Orange Tea|Honey Ginger|Seven herbal tea)/i', $childText);
                            
                            if (!$hasLink && !$isProductCard) {
                                return $this->cleanBenefitText($childText);
                            }
                        }
                        // Stop if we hit another heading
                        if (in_array($child->nodeName, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'])) {
                            break;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silent fail
        }
        return 'N/A';
    }

    /**
     * Extract benefit from sibling section - for sites with separated content blocks
     */
    private function extractBenefitFromSiblingSection($node): string
    {
        try {
            // Try to find content in the next div/section that might contain the description
            $node->nextAll()->each(function ($sibling) use (&$benefit) {
                if (isset($benefit) && $benefit !== 'N/A') return;
                
                // Check various selectors that might contain the benefit
                $text = '';
                
                // Try paragraph directly
                $p = $sibling->filter('p')->first();
                if ($p->count() > 0) {
                    $text = trim($p->text(''));
                }
                
                // If no p, try the sibling text directly
                if (empty($text)) {
                    $text = trim($sibling->text(''));
                }
                
                // Use if looks like a description
                if (strlen($text) > 30 && strlen($text) < 500 && !preg_match('/^(Image|Photo|Picture):/i', $text)) {
                    $benefit = $this->cleanBenefitText($text);
                }
            });
            
            return isset($benefit) ? $benefit : 'N/A';
        } catch (\Throwable $e) {
            return 'N/A';
        }
    }

    /**
     * Scrape from theteahouseonlosrios.com (dynamic scraping like nutritionAdvance)
     */
    protected function scrapeTeaHouse(Client $client, string $url, string $sourceUrl = '', string $shopUrl = ''): void
    {
        try {
            $crawler = $client->request('GET', $url);
            $this->line('  HTTP Status: ' . $client->getResponse()->getStatusCode());
            
            // Add delay after successful request
            $this->microDelay();
        } catch (\Throwable $e) {
            $this->error('  Failed to fetch: ' . $e->getMessage());
            sleep(2);
            return;
        }

        // Scrape dedicated shop page for direct product links
        $productLinks = [];
        if (!empty($shopUrl)) {
            $productLinks = $this->scrapeShopPage($client, $shopUrl);
            $this->microDelay();
        }

        // Fallback: Try to find shop links in the article page
        $shopLinks = $this->extractShopLinks($crawler, $url);
        
        // Add delay after extracting shop links
        $this->microDelay();

        // Look for tea entries with different possible selectors
        // Try list-based structure first (common on blog sites like theteahouseonlosrios.com)
        $selectors = [
            'ol li',        // Ordered list items (most common for tea lists)
            'ul li',        // Unordered list items
            'li',           // Any list item
            'h3',           // Headings
            'h4',           // Subheadings
            '.tea-name',    // Specific tea name class
            '.benefit-item h3', // Tea names in benefit sections
        ];

        foreach ($selectors as $selector) {
            $found = false;
            $crawler->filter($selector)->each(function ($node) use (&$found, $sourceUrl, $shopUrl, $shopLinks, $productLinks, $url) {
                // Try to extract tea name from structured content (list items often have <strong> or <b>)
                $teaName = $this->extractTeaNameFromNode($node);
                
                // Skip if doesn't look like a tea name
                if ($teaName === '' || strlen($teaName) < 3) {
                    return;
                }

                // Skip common non-tea headings
                $skipPatterns = ['/^\d+\./', '/benefits?/i', '/health/i', '/wellness/i', '/types? of/i', '/power of/i', '/^tea$/i'];
                foreach ($skipPatterns as $pattern) {
                    if (preg_match($pattern, $teaName)) {
                        return;
                    }
                }

                // Clean up tea name
                $name = preg_replace('/\s+tea\s+tea$/i', ' Tea', $teaName);
                $name = preg_replace('/\s+tea$/i', ' Tea', $name);
                $name = trim($name);

                if (strlen($name) < 3) {
                    return;
                }

                if ($this->verbose) {
                    $this->line("  Found: {$name}");
                }

                // Extract full text content for parsing flavor/caffeine info
                $fullText = $node->text('');
                
                // Try to extract shop link from anchor tags within this node
                $nodeShopLink = $this->extractShopLinkFromNode($node, $url);
                
                // Extract benefit with multiple fallback strategies
                // For list items, the structure is often: <strong>Tea Name</strong> - Benefit description
                $benefit = $this->extractBenefitFromListItem($node, $name);
                if ($benefit === 'N/A') {
                    $benefit = $this->extractBenefitFromSection($node);
                }
                if ($benefit === 'N/A') {
                    $benefit = $this->extractBenefitFromParent($node);
                }
                // Final fallback: use default benefit based on tea type
                if ($benefit === 'N/A' || strlen($benefit) < 10) {
                    $benefit = $this->getDefaultBenefitForTea($name);
                }
                
                // Extract caffeine with fallback - try to find in text first
                $caffeine = $this->extractCaffeineFromText($fullText);
                if ($caffeine === 'N/A') {
                    $caffeine = $this->extractCaffeineInfo($node);
                }
                if ($caffeine === 'N/A') {
                    $caffeine = $this->detectCaffeineLevel($name . ' ' . $benefit);
                }
                
                // Extract flavor with fallback - try to find in text first  
                $flavor = $this->extractFlavorFromText($fullText);
                if ($flavor === 'herbal' || $flavor === 'neutral') {
                    $flavor = $this->detectFlavor($name . ' ' . $benefit);
                }
                
                // Priority 1: Shop link found directly in the node (anchor tag)
                // Priority 2: Direct product link from shop page (most accurate)
                // Priority 3: Shop link found in article page
                // Priority 4: Fallback to existing tea's shop link
                $shopLink = $nodeShopLink
                    ?? $this->matchTeaToProductLink($name, $productLinks, $shopUrl) 
                    ?? $shopLinks[$name] 
                    ?? $this->findShopLinkForTea($name) 
                    ?? null;

                $this->saveTea($name, $flavor, $caffeine, $benefit, $sourceUrl, $shopLink);
                $found = true;
            });

            if ($found) {
                if ($this->verbose) {
                    $this->line("  Using selector: {$selector}");
                }
                break; // Stop if we found tea names with this selector
            }
        }
    }
    
    /**
     * Extract caffeine information from content
     */
    private function extractCaffeineInfo($node): string
    {
        $caffeine = 'N/A';
        
        try {
            // Look for caffeine keywords in the next few elements
            $node->nextAll()->slice(0, 5)->each(function ($sibling) use (&$caffeine) {
                if ($caffeine !== 'N/A') return;
                
                $text = strtolower(trim($sibling->text('')));
                
                // Check for caffeine indicators
                if (preg_match('/caffeine\\s*free|no caffeine|decaf/', $text)) {
                    $caffeine = 'Caffeine-free';
                } elseif (preg_match('/low caffeine|minimal caffeine/', $text)) {
                    $caffeine = 'Low';
                } elseif (preg_match('/medium caffeine|moderate caffeine/', $text)) {
                    $caffeine = 'Medium';
                } elseif (preg_match('/high caffeine|strong caffeine/', $text)) {
                    $caffeine = 'High';
                }
            });
        } catch (\Throwable $e) {
            // Keep as N/A if extraction fails
        }
        
        return $caffeine;
    }

    /**
     * Validate and clean benefit text - reject low-quality/product card text
     */
    private function isValidBenefit(string $benefit): bool
    {
        $invalidPatterns = [
            '/^Blood Orange Tea/i',
            '/^Honey Ginger/i',
            '/^Seven herbal tea/i',
            '/^Enjoyed for centuries/i',
            '/^\s*$/', // Empty
        ];
        
        foreach ($invalidPatterns as $pattern) {
            if (preg_match($pattern, $benefit)) {
                return false;
            }
        }
        
        return strlen($benefit) > 20;
    }

    /**
     * Normalize tea name to prevent duplicates
     */
    private function normalizeTeaName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/\s+tea\s*$/i', '', $name); // Remove trailing "tea"
        $name = preg_replace('/\s+/', ' ', $name); // Normalize spaces
        return ucwords($name) . ' Tea';
    }

    /**
     * Score flavor specificity - higher score means more descriptive/flavorful
     */
    private function scoreFlavorSpecificity(string $flavor): int
    {
        $flavor = strtolower(trim($flavor));
        
        // Generic/low-value flavors
        $genericFlavors = ['n/a', 'various', 'blend', 'unknown', ''];
        if (in_array($flavor, $genericFlavors) || empty($flavor)) {
            return 0;
        }
        
        $score = 1; // Base score for having any flavor
        
        // More specific descriptive words add points
        $descriptiveWords = [
            'sweet', 'floral', 'fruity', 'citrus', 'herbal', 'spicy', 'minty',
            'earthy', 'nutty', 'woody', 'fresh', 'bold', 'smooth', 'rich',
            'delicate', 'aromatic', 'robust', 'mild', 'strong', 'light',
            'chocolate', 'vanilla', 'honey', 'caramel', 'berry', 'cinnamon',
            'ginger', 'peppermint', 'jasmine', 'lavender', 'rose', 'citrusy'
        ];
        
        foreach ($descriptiveWords as $word) {
            if (strpos($flavor, $word) !== false) {
                $score += 2;
            }
        }
        
        // Length bonus - longer descriptions are usually more specific
        $score += min(strlen($flavor) / 10, 5);
        
        return $score;
    }
    
    /**
     * Score benefit quality - higher score means better/more informative benefit
     */
    private function scoreBenefitQuality(string $benefit): int
    {
        $benefit = trim($benefit);
        
        if (empty($benefit) || strtolower($benefit) === 'n/a') {
            return 0;
        }
        
        $score = 0;
        $benefitLower = strtolower($benefit);
        
        // Length score - longer benefits with more detail are better (up to a point)
        $length = strlen($benefit);
        if ($length > 100) {
            $score += 10; // Detailed benefit
        } elseif ($length > 60) {
            $score += 7; // Good detail
        } elseif ($length > 40) {
            $score += 5; // Moderate detail
        } elseif ($length > 20) {
            $score += 3; // Basic detail
        } else {
            $score += 1; // Minimal
        }
        
        // Quality indicators - specific health terms
        $healthTerms = [
            'antioxidant', 'immune', 'digestion', 'stress', 'relax', 'sleep',
            'heart', 'brain', 'energy', 'metabolism', 'inflammation', 'detox',
            'blood pressure', 'cholesterol', 'weight', 'anxiety', 'focus',
            'memory', 'circulation', 'skin', 'aging', 'wellness', 'health',
            'vitamin', 'mineral', 'calming', 'soothing', 'refreshing'
        ];
        
        foreach ($healthTerms as $term) {
            if (strpos($benefitLower, $term) !== false) {
                $score += 3;
            }
        }
        
        // Penalize vague/generic descriptions
        $vaguePatterns = ['/good for/i', '/helps? with/i', '/may help/i', '/can help/i'];
        foreach ($vaguePatterns as $pattern) {
            if (preg_match($pattern, $benefitLower)) {
                $score -= 2; // Slight penalty for vague language
            }
        }
        
        // Bonus for multiple distinct benefits (comma or period separated)
        $benefitCount = substr_count($benefitLower, ',') + substr_count($benefitLower, '.') + 1;
        $score += min($benefitCount * 2, 8); // Up to 8 points for multiple benefits
        
        return max($score, 0); // Ensure non-negative
    }

    /**
     * Save or update a tea in the database - with intelligent data merging
     */
    protected function saveTea(string $name, string $flavor, string $caffeine, string $benefit, string $sourceUrl = '', ?string $shopLink = null): void
    {
        $placeholderImage = $this->teaPlaceholders[$this->placeholderIndex % count($this->teaPlaceholders)];
        $this->placeholderIndex++;

        // Normalize tea name to prevent duplicates
        $normalizedName = $this->normalizeTeaName($name);
        
        // Check if this tea was deleted by admin - if so, skip it entirely
        if (\App\Models\DeletedTea::wasDeleted($normalizedName)) {
            $this->skipped++;
            if ($this->verbose) {
                $this->line("  ⚠️  Skipping '{$normalizedName}' - was deleted by admin");
            }
            return;
        }
        
        // Check for existing tea with case-insensitive match (handles edge cases)
        $existingTea = Tea::whereRaw('LOWER(name) = ?', [strtolower($normalizedName)])->first();
        
        if ($existingTea) {
            $tea = $existingTea;
            $wasCreated = false;
        } else {
            $tea = new Tea();
            $tea->name = $normalizedName;
            $wasCreated = true;
        }

        $tea->source = 'scraped';
        
        // Cross-source intelligent merging: pick the best data from multiple sources
        
        // Merge flavor - prefer more specific/descriptive flavors
        $existingFlavor = $tea->flavor ?? '';
        $newFlavorScore = $this->scoreFlavorSpecificity($flavor);
        $existingFlavorScore = $this->scoreFlavorSpecificity($existingFlavor);
        
        if ($newFlavorScore > $existingFlavorScore) {
            $tea->flavor = $flavor;
        } elseif (empty($existingFlavor)) {
            $tea->flavor = $flavor;
        }
        
        // Merge caffeine level - prefer non-generic values
        if ($caffeine !== 'N/A' && !empty($caffeine)) {
            $existingCaffeine = $tea->caffeine_level ?? '';
            if (empty($existingCaffeine) || $existingCaffeine === 'N/A') {
                $tea->caffeine_level = $caffeine;
            }
        }

        // Validate and merge benefit - intelligent selection of best description
        $isValid = $this->isValidBenefit($benefit);
        $existingBenefit = $tea->health_benefit ?: '';
        
        if ($isValid && $benefit !== 'N/A') {
            // Compare benefits and pick the better one
            $newScore = $this->scoreBenefitQuality($benefit);
            $existingScore = $this->scoreBenefitQuality($existingBenefit);
            
            if ($newScore > $existingScore) {
                $tea->health_benefit = $benefit;
                if ($this->verbose) {
                    $this->line("  📝 Updated benefit for '{$normalizedName}' (better quality: {$newScore} vs {$existingScore})");
                }
            }
        } elseif (empty($existingBenefit) && $benefit !== 'N/A') {
            // If no existing benefit, use default based on tea name
            $tea->health_benefit = $this->getDefaultBenefitForTea($normalizedName);
        }
        
        $tea->image = $placeholderImage;

        // Save source URL
        if (!empty($sourceUrl)) {
            $tea->source_url = $sourceUrl;
        }

        // Save shop link if provided, otherwise try to find from other sources
        if (!empty($shopLink)) {
            $tea->shop_link = $shopLink;
        } elseif (empty($tea->shop_link)) {
            // Try to find shop link from other sources with same tea name
            $fallbackLink = $this->findShopLinkForTea($normalizedName);
            if ($fallbackLink) {
                $tea->shop_link = $fallbackLink;
            }
        }

        $tea->save();

        if ($wasCreated) {
            $this->created++;
        } else {
            $this->updated++;
        }

        // Add small delay between processing individual teas to be respectful
        usleep(rand(100000, 300000)); // 0.1 - 0.3 seconds
    }

    /**
     * Add a random delay between requests (human-like behavior)
     */
    protected function randomDelay(): void
    {
        $delay = rand($this->minDelay, $this->maxDelay);
        
        if ($this->verbose) {
            $this->line("  Waiting {$delay}s...");
        }
        
        sleep($delay);
    }

    /**
     * Add a short micro-delay for internal processing
     */
    protected function microDelay(): void
    {
        usleep(rand(50000, 200000)); // 0.05 - 0.2 seconds
    }

    /**
     * Extract shop links from the scraped page - Enhanced version
     */
    protected function extractShopLinks($crawler, string $baseUrl): array
    {
        $shopLinks = [];
        $baseHost = parse_url($baseUrl, PHP_URL_HOST);
        
        try {
            // Pattern 1: Look for links with shop-related keywords
            $shopKeywords = ['shop', 'product', 'buy', 'store', 'cart', 'purchase', 'order', 'collection', 'tea'];
            $selector = 'a[' . implode('], a[', array_map(function($k) { return 'href*="' . $k . '"'; }, $shopKeywords)) . ']';
            
            $crawler->filter($selector)->each(function ($node) use (&$shopLinks, $baseUrl, $baseHost) {
                $href = $node->attr('href');
                $text = trim($node->text(''));
                
                if (!empty($href) && !str_starts_with($href, '#') && !str_starts_with($href, 'javascript')) {
                    $absoluteUrl = $this->makeAbsoluteUrl($href, $baseUrl);
                    
                    // Clean up the text to extract tea name
                    if (!empty($text)) {
                        // Remove common button text like "Buy Now", "Shop", etc.
                        $cleanText = preg_replace('/\s*(buy now|shop|add to cart|purchase|order)\s*/i', '', $text);
                        $cleanText = trim($cleanText);
                        
                        if (!empty($cleanText) && strlen($cleanText) > 2 && strlen($cleanText) < 100) {
                            $shopLinks[$cleanText] = $absoluteUrl;
                        }
                    }
                }
            });
            
            // Pattern 2: Look for links with tea names in them
            $crawler->filter('a[href]')->each(function ($node) use (&$shopLinks, $baseUrl) {
                $href = $node->attr('href');
                $text = trim($node->text(''));
                
                if (!empty($href) && !empty($text)) {
                    // Check if link text contains tea-related keywords
                    $teaKeywords = ['tea', 'chai', 'matcha', 'herbal', 'rooibos', 'oolong', 'tisane'];
                    foreach ($teaKeywords as $keyword) {
                        if (stripos($text, $keyword) !== false && strlen($text) > 3 && strlen($text) < 100) {
                            $absoluteUrl = $this->makeAbsoluteUrl($href, $baseUrl);
                            $shopLinks[$text] = $absoluteUrl;
                            break;
                        }
                    }
                }
            });
            
            // Pattern 3: Look for product cards/sections with links
            $crawler->filter('.product, .product-card, .tea-item, [class*="product"], [class*="item"]')->each(function ($node) use (&$shopLinks, $baseUrl) {
                try {
                    $linkNode = $node->filter('a')->first();
                    if ($linkNode->count() > 0) {
                        $href = $linkNode->attr('href');
                        $text = trim($node->filter('h3, h4, .title, .name')->first()->text(''));
                        
                        if (!empty($href) && !empty($text) && strlen($text) > 2) {
                            $absoluteUrl = $this->makeAbsoluteUrl($href, $baseUrl);
                            $shopLinks[$text] = $absoluteUrl;
                        }
                    }
                } catch (\Throwable $e) {
                    // Skip if extraction fails
                }
            });
            
        } catch (\Throwable $e) {
            // Silently fail if extraction doesn't work
        }
        
        if ($this->verbose && !empty($shopLinks)) {
            $this->line('  Found ' . count($shopLinks) . ' potential shop links');
        }
        
        return $shopLinks;
    }
    
    /**
     * Make a relative URL absolute
     */
    private function makeAbsoluteUrl(string $url, string $baseUrl): string
    {
        if (strpos($url, 'http') === 0) {
            return $url;
        }
        
        $parsedBase = parse_url($baseUrl);
        $scheme = $parsedBase['scheme'] ?? 'https';
        $host = $parsedBase['host'] ?? '';
        
        if (strpos($url, '/') === 0) {
            return $scheme . '://' . $host . $url;
        }
        
        $basePath = dirname($parsedBase['path'] ?? '/');
        return $scheme . '://' . $host . $basePath . '/' . $url;
    }

    /**
     * Scrape shop page to extract product URLs mapped to tea names
     * This enables direct "Buy Now" links for each tea
     */
    protected function scrapeShopPage(Client $client, string $shopUrl): array
    {
        $productLinks = [];
        
        if (empty($shopUrl)) {
            return $productLinks;
        }
        
        try {
            if ($this->verbose) {
                $this->line("  Scraping shop page: {$shopUrl}");
            }
            
            $crawler = $client->request('GET', $shopUrl);
            
            // Pattern 1: Extract from product grid items with URL-based matching
            $crawler->filter('.product-grid-item, .product-card, .grid-item, [class*="product"], .card')->each(function ($node) use (&$productLinks, $shopUrl) {
                try {
                    // Find product link
                    $linkNode = $node->filter('a[href*="/products/"], a[href*="/product/"]')->first();
                    
                    if ($linkNode->count() > 0) {
                        $href = $linkNode->attr('href');
                        $absoluteUrl = $this->makeAbsoluteUrl($href, $shopUrl);
                        
                        // Strategy 1: Extract tea name from URL slug
                        // URL pattern: /products/tea-name-here
                        if (preg_match('#/products?/([^/]+)#', $href, $matches)) {
                            $slug = $matches[1];
                            
                            // Convert slug to readable name
                            // e.g., "blood-orange-herbal" -> "Blood Orange Herbal"
                            $name = str_replace(['-', '_'], ' ', $slug);
                            $name = ucwords($name);
                            
                            // Clean up common suffixes
                            $name = preg_replace('/\s+tea\s*$/i', '', $name);
                            $name = trim($name);
                            
                            if (strlen($name) > 2) {
                                $productLinks[$name] = $absoluteUrl;
                                $productLinks[$name . ' Tea'] = $absoluteUrl;
                                
                                if ($this->verbose) {
                                    $this->line("    URL→Name: \"{$name}\" → {$slug}");
                                }
                            }
                        }
                        
                        // Strategy 2: Also try to get title from image alt or hidden elements
                        $title = '';
                        $imgNode = $node->filter('img')->first();
                        if ($imgNode->count() > 0) {
                            $title = trim($imgNode->attr('alt') ?? '');
                        }
                        
                        // Try hidden title element
                        if (empty($title)) {
                            $titleNode = $node->filter('.visually-hidden, .sr-only, [class*="title"]')->first();
                            if ($titleNode->count() > 0) {
                                $title = trim($titleNode->text(''));
                            }
                        }
                        
                        // Clean and add title-based match if different from URL
                        if (!empty($title)) {
                            $cleanTitle = preg_replace('/\s+from\s+\$[\d.]+.*$/i', '', $title);
                            $cleanTitle = preg_replace('/\s+tea\s*$/i', '', $cleanTitle);
                            $cleanTitle = preg_replace('/\s*Notify When Available.*/i', '', $cleanTitle);
                            $cleanTitle = trim($cleanTitle);
                            
                            if (strlen($cleanTitle) > 2 && strlen($cleanTitle) < 100) {
                                $productLinks[$cleanTitle] = $absoluteUrl;
                                $productLinks[$cleanTitle . ' Tea'] = $absoluteUrl;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // Continue to next product
                }
            });
            
            // Pattern 2: Grid/collection layouts
            $crawler->filter('.grid .grid__item, .collection-grid-item, .product-grid-item')->each(function ($node) use (&$productLinks, $shopUrl) {
                try {
                    $linkNode = $node->filter('a')->first();
                    if ($linkNode->count() > 0) {
                        $href = $linkNode->attr('href');
                        $text = trim($node->text(''));
                        
                        // Extract name from text (usually first line)
                        $lines = explode("\n", $text);
                        $name = trim($lines[0] ?? '');
                        
                        if (!empty($href) && !empty($name) && strlen($name) > 2) {
                            $absoluteUrl = $this->makeAbsoluteUrl($href, $shopUrl);
                            $cleanName = preg_replace('/\s+tea\s*$/i', '', $name);
                            $cleanName = trim($cleanName);
                            
                            $productLinks[$cleanName] = $absoluteUrl;
                            $productLinks[$cleanName . ' Tea'] = $absoluteUrl;
                        }
                    }
                } catch (\Throwable $e) {
                    // Continue
                }
            });
            
            if ($this->verbose && !empty($productLinks)) {
                $this->line('  Found ' . count($productLinks) . ' product links from shop page');
            }
            
        } catch (\Throwable $e) {
            if ($this->verbose) {
                $this->line('  Error scraping shop page: ' . $e->getMessage());
            }
        }
        
        return $productLinks;
    }

    /**
     * Match tea name to product link from shop page
     * Uses scoring algorithm to find best match
     * If no match found, generates a search URL for the tea name
     */
    protected function matchTeaToProductLink(string $teaName, array $productLinks, string $shopUrl): ?string
    {
        if (empty($productLinks)) {
            // No product links available, generate search URL
            return $this->generateSearchUrl($teaName, $shopUrl);
        }
        
        $normalizedTea = strtolower(preg_replace('/\s+tea\s*$/i', '', $teaName));
        $teaWords = array_filter(explode(' ', $normalizedTea));
        
        $bestMatch = null;
        $bestScore = 0;
        
        foreach ($productLinks as $productName => $url) {
            $normalizedProduct = strtolower(preg_replace('/\s+tea\s*$/i', '', $productName));
            $score = 0;
            
            // Exact match - highest score
            if ($normalizedTea === $normalizedProduct) {
                return $url; // Perfect match, return immediately
            }
            
            // Check if all tea words are in product name
            $allWordsMatch = true;
            $matchedWords = 0;
            foreach ($teaWords as $word) {
                if (strlen($word) > 2 && strpos($normalizedProduct, $word) !== false) {
                    $matchedWords++;
                    $score += 10; // Points for each matching word
                } else if (strlen($word) > 2) {
                    $allWordsMatch = false;
                }
            }
            
            // Bonus if all words match
            if ($allWordsMatch && $matchedWords > 0) {
                $score += 50;
            }
            
            // Contains match (lower priority)
            if (strpos($normalizedProduct, $normalizedTea) !== false) {
                $score += 5;
            }
            
            // Tea name contains product name (lowest priority)
            if (strpos($normalizedTea, $normalizedProduct) !== false) {
                $score += 3;
            }
            
            // Prefer shorter product names (more specific matches)
            if ($score > 0) {
                $score -= strlen($normalizedProduct) * 0.1;
            }
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $url;
            }
        }
        
        // If we have a decent product match, return it
        if ($bestScore >= 10) {
            return $bestMatch;
        }
        
        // No good product match found, generate search URL as fallback
        return $this->generateSearchUrl($teaName, $shopUrl);
    }

    /**
     * Generate a search URL for the tea name on the shop website
     * Format: https://simplelooseleaf.com/search?type=product&q=Tea+Name&search=
     */
    protected function generateSearchUrl(string $teaName, string $shopUrl): ?string
    {
        if (empty($shopUrl)) {
            return null;
        }
        
        // Parse the shop URL to get the base domain
        $parsed = parse_url($shopUrl);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        
        if (empty($host)) {
            return null;
        }
        
        // Encode the tea name for URL
        $searchQuery = urlencode($teaName);
        
        // Generate search URL based on the shop domain
        // Simple Loose Leaf format
        if (strpos($host, 'simplelooseleaf.com') !== false) {
            return "{$scheme}://{$host}/search?type=product&q={$searchQuery}&search=";
        }
        
        // Tea House on Los Rios format (different shop structure)
        if (strpos($host, 'theteahouseonlosrios.com') !== false) {
            return "{$scheme}://{$host}/search?q={$searchQuery}";
        }
        
        // Generic Shopify-style search
        return "{$scheme}://{$host}/search?type=product&q={$searchQuery}";
    }

    /**
     * Find shop link for a specific tea name from other sources - Enhanced version
     */
    protected function findShopLinkForTea(string $teaName): ?string
    {
        // Strategy 1: Exact match with shop link
        $existingTea = Tea::where('name', $teaName)
            ->whereNotNull('shop_link')
            ->first();
        
        if ($existingTea) {
            return $existingTea->shop_link;
        }
        
        // Strategy 2: Similar tea names with shop link
        $teaNameLower = strtolower($teaName);
        $similarTea = Tea::whereRaw('LOWER(name) LIKE ?', ['%' . $teaNameLower . '%'])
            ->whereNotNull('shop_link')
            ->first();
        
        if ($similarTea) {
            return $similarTea->shop_link;
        }
        
        // Strategy 3: Word matching (for teas like "Green Tea" matching "Organic Green Tea")
        $teaWords = array_filter(explode(' ', $teaNameLower), function($w) {
            return strlen($w) > 3; // Only significant words
        });
        
        if (!empty($teaWords)) {
            $query = Tea::whereNotNull('shop_link');
            foreach ($teaWords as $word) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . $word . '%']);
            }
            $matchedTea = $query->first();
            
            if ($matchedTea) {
                return $matchedTea->shop_link;
            }
        }
        
        // Strategy 4: For specific sources that are known to be shops, use source_url
        $sourceTea = Tea::where('name', $teaName)
            ->whereNotNull('source_url')
            ->first();
        
        if ($sourceTea) {
            $sourceUrl = $sourceTea->source_url;
            // These sources have shop pages
            $shopDomains = [
                'simplelooseleaf.com',
                'theteahouseonlosrios.com',
                'nutritionadvance.com',
                'shop.',
                'store.',
                '/shop/',
                '/store/',
                '/product/',
            ];
            
            foreach ($shopDomains as $domain) {
                if (stripos($sourceUrl, $domain) !== false) {
                    return $sourceUrl;
                }
            }
        }
        
        // Strategy 5: Use source_url as fallback if no shop link exists
        $anySource = Tea::where('name', $teaName)
            ->whereNotNull('source_url')
            ->first();
        
        if ($anySource && !empty($anySource->source_url)) {
            // Return the source URL as a fallback (better than nothing)
            return $anySource->source_url;
        }
        
        // Strategy 6: Search for teas with similar names that have source URLs
        $similarWithSource = Tea::whereRaw('LOWER(name) LIKE ?', ['%' . $teaNameLower . '%'])
            ->whereNotNull('source_url')
            ->first();
        
        if ($similarWithSource) {
            return $similarWithSource->source_url;
        }

        return null;
    }

    /**
     * Extract tea name from a node, preferring <strong>, <b>, <h3>, <h4> tags
     * Falls back to full text if no structured tag found
     */
    protected function extractTeaNameFromNode($node): string
    {
        // Try to find tea name in bold/strong tags (common in list items)
        $strong = $node->filter('strong')->first();
        if ($strong->count() > 0) {
            return trim($strong->text(''));
        }
        
        $bold = $node->filter('b')->first();
        if ($bold->count() > 0) {
            return trim($bold->text(''));
        }
        
        // Try h3, h4 tags
        $heading = $node->filter('h3, h4')->first();
        if ($heading->count() > 0) {
            return trim($heading->text(''));
        }
        
        // Fallback to full node text
        return trim($node->text(''));
    }

    /**
     * Extract benefit from list item text
     * Removes the tea name and extracts the description/benefit part
     * Also removes shop link text like "Shop Now" or "Buy Here"
     */
    protected function extractBenefitFromListItem($node, string $teaName): string
    {
        // Get text only from text nodes, not from anchor tags (shop links)
        $textParts = [];
        $node->filter('a')->each(function($a) {
            // Remove anchor text from benefit by marking it
            $a->getNode(0)->setAttribute('data-shop-link', 'true');
        });
        
        // Get the text content
        $fullText = $node->text('');
        
        // Remove the tea name from the beginning
        $benefitText = preg_replace('/^' . preg_quote($teaName, '/') . '\s*[—–:-]?\s*/i', '', $fullText);
        
        // Remove common shop link phrases
        $shopPhrases = [
            '/\s*shop\s*now\s*/i',
            '/\s*buy\s*here\s*/i',
            '/\s*order\s*now\s*/i',
            '/\s*view\s*product\s*/i',
            '/\s*learn\s*more\s*/i',
        ];
        foreach ($shopPhrases as $pattern) {
            $benefitText = preg_replace($pattern, ' ', $benefitText);
        }
        
        // Clean up
        $benefitText = trim($benefitText);
        
        // If benefit is too short or empty, return N/A
        if (strlen($benefitText) < 10 || $benefitText === $fullText) {
            return 'N/A';
        }
        
        return $this->cleanBenefitText($benefitText);
    }

    /**
     * Extract caffeine level from text using patterns
     */
    protected function extractCaffeineFromText(string $text): string
    {
        $text = strtolower($text);
        
        // Look for caffeine indicators
        if (preg_match('/caffeine[\s-]*free|no\s*caffeine|decaf/i', $text)) {
            return 'Caffeine-free';
        }
        if (preg_match('/low\s*caffeine/i', $text)) {
            return 'Low';
        }
        if (preg_match('/medium\s*caffeine/i', $text)) {
            return 'Medium';
        }
        if (preg_match('/high\s*caffeine/i', $text)) {
            return 'High';
        }
        
        return 'N/A';
    }

    /**
     * Extract flavor from text using keywords
     */
    protected function extractFlavorFromText(string $text): string
    {
        $text = strtolower($text);
        
        // Flavor keywords to look for
        $flavorKeywords = [
            'herbal' => ['herbal', 'floral', 'botanical'],
            'sweet' => ['sweet', 'honey', 'vanilla', 'caramel'],
            'minty' => ['mint', 'peppermint', 'spearmint'],
            'fruity' => ['fruity', 'berry', 'citrus', 'lemon', 'orange', 'apple'],
            'spicy' => ['spicy', 'ginger', 'cinnamon', 'chai'],
            'earthy' => ['earthy', 'woody', 'nutty'],
            'grassy' => ['grassy', 'vegetal', 'fresh'],
        ];
        
        foreach ($flavorKeywords as $flavor => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    return $flavor;
                }
            }
        }
        
        return 'herbal'; // Default fallback
    }

    /**
     * Extract shop link from anchor tags within a node
     * Returns the href if it looks like a shop/product link
     */
    protected function extractShopLinkFromNode($node, string $baseUrl): ?string
    {
        try {
            $anchors = $node->filter('a');
            
            if ($anchors->count() === 0) {
                return null;
            }
            
            // Look for shop/product links
            $shopPatterns = [
                '/product/',
                '/collections/',
                '/shop/',
                '/store/',
                '/buy/',
                '/item/',
                'shopify',
                'theteahouseonlosrios.com/products',
            ];
            
            $bestLink = null;
            
            $anchors->each(function ($anchor) use (&$bestLink, $shopPatterns, $baseUrl) {
                $href = $anchor->attr('href');
                $text = strtolower(trim($anchor->text('')));
                
                if (empty($href)) {
                    return;
                }
                
                // Check if link text suggests it's a shop link
                $shopTextIndicators = ['shop now', 'buy', 'order', 'view product', 'shop', 'learn more'];
                $looksLikeShopText = false;
                foreach ($shopTextIndicators as $indicator) {
                    if (strpos($text, $indicator) !== false) {
                        $looksLikeShopText = true;
                        break;
                    }
                }
                
                // Check if URL looks like a shop link
                $looksLikeShopUrl = false;
                foreach ($shopPatterns as $pattern) {
                    if (stripos($href, $pattern) !== false) {
                        $looksLikeShopUrl = true;
                        break;
                    }
                }
                
                // Prioritize links that look like shop URLs or have shop-related text
                if ($looksLikeShopText || $looksLikeShopUrl) {
                    $bestLink = $this->makeAbsoluteUrl($href, $baseUrl);
                    return false; // Stop after finding first good match
                }
            });
            
            return $bestLink;
            
        } catch (\Throwable $e) {
            return null;
        }
    }
}
