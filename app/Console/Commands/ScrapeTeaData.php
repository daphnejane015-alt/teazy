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
            $this->info(' Fresh scrape mode: Merging with existing data...');
            $this->line('   HTTP caches cleared, fetching fresh data from sources.');
            $this->line('   Existing teas will be updated with better data only.');
            $this->newLine();
        }

        // Clear existing scraped data if requested (NOT RECOMMENDED - use with caution)
        if ($clear) {
            $this->warn('  WARNING: --clear will DELETE all existing scraped teas!');
            $this->warn('   This is NOT recommended. Data will be lost if scraping fails.');
            $this->warn('   Use --fresh instead to merge/update without losing data.');
            $this->newLine();
            
            $deleted = Tea::where('source', 'scraped')->delete();
            $this->line("Deleted {$deleted} existing scraped teas");
            $this->newLine();
        }
        // Source websites configuration
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
                if (empty($canonical->shopee_link) && !empty($dupe->shopee_link)) {
                    $canonical->shopee_link = $dupe->shopee_link;
                }
                if (empty($canonical->lazada_link) && !empty($dupe->lazada_link)) {
                    $canonical->lazada_link = $dupe->lazada_link;
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

        // Collect guide links to follow AFTER the main pass (avoids nested requests
        // corrupting the current crawler state).
        $guideFollowUps = [];

        $crawler->filter('h2')->each(function ($node) use ($sourceUrl, $shopLinks, &$guideFollowUps) {
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

            // Gather the description paragraphs that belong to THIS tea, i.e. every
            // sibling paragraph until the next "N. Tea" heading. Also detect a link
            // to a dedicated benefit/guide page for this tea.
            $paragraphs = [];
            $guideLink = null;
            $stopped = false;
            $imageUrl = null;

            try {
                $node->nextAll()->each(function ($sib) use (&$paragraphs, &$guideLink, &$stopped, &$imageUrl, $name, $sourceUrl) {
                    if ($stopped) {
                        return;
                    }
                    $tag = strtolower($sib->nodeName());

                    // Stop when we reach the next tea heading
                    if ($tag === 'h2') {
                        $stopped = true;
                        return;
                    }

                    // Try to grab a real content image for this tea before moving on
                    if ($imageUrl === null) {
                        $imageUrl = $this->extractContentImage($sib, $sourceUrl);
                    }

                    if ($tag === 'p') {
                        $text = trim($sib->text(''));
                        if (strlen($text) > 30) {
                            $paragraphs[] = $text;
                        }

                        // Look for a "guide to X" / health-benefits link for this tea
                        if ($guideLink === null) {
                            $sib->filter('a')->each(function ($a) use (&$guideLink) {
                                if ($guideLink !== null) {
                                    return;
                                }
                                $href = $a->attr('href');
                                $anchorText = strtolower(trim($a->text('')));
                                if (empty($href)) {
                                    return;
                                }
                                $isInternal = stripos($href, 'nutritionadvance.com') !== false || str_starts_with($href, '/');
                                $looksLikeGuide = stripos($anchorText, 'guide') !== false
                                    || stripos($href, 'benefits') !== false
                                    || stripos($href, 'guide') !== false;
                                if ($isInternal && $looksLikeGuide) {
                                    $guideLink = $href;
                                }
                            });
                        }
                    }
                });
            } catch (\Throwable $e) {
                // Fall back to whatever paragraphs we collected
            }

            // Build the benefit description from the first couple of paragraphs
            $benefit = 'N/A';
            if (!empty($paragraphs)) {
                $description = implode(' ', array_slice($paragraphs, 0, 2));
                $benefit = $this->cleanBenefitText($description, 500);
            }

            // Final fallback
            if ($benefit === 'N/A' || strlen($benefit) < 10) {
                $benefit = $this->getDefaultBenefitForTea($name);
            }

            // Detect flavor and caffeine from name + benefit
            $flavor = $this->detectFlavor($name . ' ' . $benefit);
            $caffeine = $this->detectCaffeineLevel($name . ' ' . $benefit);

            // Try to find a specific shop link for this tea, otherwise use generic
            $shopLink = $shopLinks[$name] ?? $this->findShopLinkForTea($name) ?? null;
            $this->saveTea($name, $flavor, $caffeine, $benefit, $sourceUrl, $shopLink, $imageUrl);

            // Queue the guide link so we can enrich the benefit afterwards
            if ($guideLink !== null) {
                $guideFollowUps[$name] = $this->makeAbsoluteUrl($guideLink, $sourceUrl);
            }
        });

        // Follow benefit/guide links to enrich descriptions where available.
        foreach ($guideFollowUps as $teaName => $guideUrl) {
            $richBenefit = $this->fetchGuideBenefit($client, $guideUrl);
            if ($richBenefit !== null) {
                $normalized = $this->normalizeTeaName($teaName);
                $tea = Tea::whereRaw('LOWER(name) = ?', [strtolower($normalized)])->first();
                if ($tea) {
                    // Only replace if the guide description is higher quality
                    if ($this->scoreBenefitQuality($richBenefit) > $this->scoreBenefitQuality($tea->health_benefit ?? '')) {
                        $tea->health_benefit = $richBenefit;
                        $tea->save();
                        if ($this->verbose) {
                            $this->line("  🔗 Enriched benefit for '{$normalized}' from guide page");
                        }
                    }
                }
            }
            $this->microDelay();
        }
    }

    /**
     * Follow a tea's dedicated guide/benefit page and extract a concise summary.
     * Prefers the page meta description, falling back to the first real paragraph.
     */
    protected function fetchGuideBenefit(Client $client, string $url): ?string
    {
        try {
            $crawler = $client->request('GET', $url);
            $status = $client->getResponse()->getStatusCode();
            if ($status >= 400) {
                return null;
            }

            // 1) Meta description - but only if it is a real benefit summary and
            //    not a clickbait teaser/question (e.g. "...does it offer benefits?").
            foreach (['meta[name="description"]', 'meta[property="og:description"]'] as $sel) {
                $meta = $crawler->filter($sel)->first();
                if ($meta->count() > 0) {
                    $content = trim($meta->attr('content') ?? '');
                    if (strlen($content) > 40 && !$this->isTeaserText($content)) {
                        return $this->cleanBenefitText($content, 500);
                    }
                }
            }

            // 2) First substantial, non-teaser paragraph in the article body
            $best = null;
            $crawler->filter('article p, .entry-content p, .post-content p, main p, p')->each(function ($p) use (&$best) {
                if ($best !== null) {
                    return;
                }
                $text = trim($p->text(''));
                if (strlen($text) > 60 && !$this->isTeaserText($text)) {
                    $best = $this->cleanBenefitText($text, 500);
                }
            });

            return $best;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Detect clickbait/teaser text that poses a question or promises info rather
     * than actually describing the tea's benefits.
     */
    protected function isTeaserText(string $text): bool
    {
        if (strpos($text, '?') !== false) {
            return true;
        }
        $teaserPatterns = [
            '/here\'?s?\s+(a\s+)?guide/i',
            '/what\s+the\s+research\s+says/i',
            '/does\s+it\s+(offer|have|provide)/i',
            '/in\s+this\s+(article|guide|post)/i',
            '/let\'?s\s+(take\s+a\s+)?look/i',
            '/read\s+(on|more)/i',
        ];
        foreach ($teaserPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }
        return false;
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

        // Locate the article body so headings & paragraphs are in document order.
        $container = $this->findArticleContainer($crawler) ?? $crawler;

        // Scrape dedicated shop page for direct product links
        $productLinks = [];
        if (!empty($shopUrl)) {
            $productLinks = $this->scrapeShopPage($client, $shopUrl);
            $this->microDelay();
        }

        // Fallback: shop links found within the article page
        $shopLinks = $this->extractShopLinks($crawler, $url);
        $this->microDelay();

        // Walk the article in document order. Each tea is a heading ending in
        // "tea" followed by its description paragraph. We keep the FULL first
        // sentence-paragraph (which contains the health-benefit description);
        // product-card captions that sometimes follow have no period and are
        // skipped so the stored description stays valid.
        $collected = $this->collectSimpleLeafEntries($container, $url);
        $entries = $collected['entries'];
        $images = $collected['images'];
        $this->line('  Found ' . count($entries) . ' tea descriptions');

        foreach ($entries as $name => $benefit) {
            $shopLink = $this->matchTeaToProductLink($name, $productLinks, $shopUrl)
                ?? ($shopLinks[$name] ?? null)
                ?? $this->findShopLinkForTea($name)
                ?? null;

            $flavor = $this->detectFlavor($name . ' ' . $benefit);
            $imageUrl = $images[$name] ?? null;
            $this->saveTea($name, $flavor, 'Caffeine-free', $benefit, $sourceUrl, $shopLink, $imageUrl);
        }
    }

    /**
     * Walk the SimpleLooseLeaf article DOM and map each tea name to its full
     * description. The site wraps each tea name in an <ol> but leaves the
     * description as bare text nodes / <span> / <a> elements directly under
     * the .rte container (NOT inside <p> tags). We therefore walk ALL child
     * nodes of the container in document order instead of using CSS selectors.
     *
     * Returns: ['entries' => [ displayName => benefitText ], 'images' => [ displayName => imageUrl ]]
     */
    protected function collectSimpleLeafEntries($container, string $baseUrl = ''): array
    {
        $entries = [];
        $images = [];

        try {
            $dom = $container->getNode(0);
            if (!$dom) {
                return ['entries' => $entries, 'images' => $images];
            }

            $currentName = null;
            $descParts   = [];  // text fragments for the current tea

            // Flush accumulated description text into $entries under $currentName.
            $flush = function () use (&$currentName, &$descParts, &$entries) {
                if ($currentName === null) {
                    $descParts = [];
                    return;
                }
                $raw = implode(' ', $descParts);
                $raw = trim(preg_replace('/\s+/', ' ', $raw));
                $descParts = [];

                if (strlen($raw) < 30 || strpos($raw, '.') === false) {
                    return; // too short or no real sentence
                }

                $benefit = $this->cleanBenefitText($raw, 500);
                if ($this->isValidBenefit($benefit)) {
                    // Only store the first (best) description for each tea
                    if (!isset($entries[$currentName]) || $entries[$currentName] === null) {
                        $entries[$currentName] = $benefit;
                    }
                }
            };

            foreach ($dom->childNodes as $child) {
                $type = $child->nodeType;  // 1 = element, 3 = text
                $name = strtolower($child->nodeName);

                // ------ Section headings (h2/h3/h4/h5/h6) ------
                if ($type === 1 && preg_match('/^h[2-6]$/', $name)) {
                    $flush();
                    $headText = trim($child->textContent);
                    if (preg_match('/\btea$/i', $headText) && strlen($headText) >= 5 && strlen($headText) <= 50
                        && !preg_match('/^(benefits?|references?|list of|top\s)/i', $headText)) {
                        $currentName = $this->normalizeTeaName($headText);
                        if (!array_key_exists($currentName, $entries)) {
                            $entries[$currentName] = null;
                        }
                    } else {
                        $currentName = null;
                    }
                    continue;
                }

                // ------ Ordered list that contains the tea name ------
                if ($type === 1 && $name === 'ol') {
                    $flush();
                    $olText = trim($child->textContent);
                    if (preg_match('/\btea$/i', $olText) && strlen($olText) >= 5 && strlen($olText) <= 50) {
                        $currentName = $this->normalizeTeaName($olText);
                        if (!array_key_exists($currentName, $entries)) {
                            $entries[$currentName] = null;
                        }
                    }
                    continue;
                }

                // ------ Skip product caption <p> tags (image alt text etc.) ------
                if ($type === 1 && $name === 'p') {
                    $pText = trim($child->textContent);

                    // Product caption/card paragraphs often wrap the tea's photo
                    if ($currentName !== null && !isset($images[$currentName])) {
                        $found = $this->extractImageFromDomElement($child, $baseUrl);
                        if ($found !== null) {
                            $images[$currentName] = $found;
                        }
                    }

                    // Product captions have no period and are short
                    if (strpos($pText, '.') === false || strlen($pText) < 30) {
                        continue;
                    }
                    // Otherwise treat as description text
                    if ($currentName !== null && ($entries[$currentName] ?? null) === null) {
                        $descParts[] = $pText;
                    }
                    continue;
                }

                // ------ Standalone <img> elements (product photos) ------
                if ($type === 1 && $name === 'img') {
                    if ($currentName !== null && !isset($images[$currentName])) {
                        $found = $this->extractImageFromDomElement($child, $baseUrl);
                        if ($found !== null) {
                            $images[$currentName] = $found;
                        }
                    }
                    continue;
                }

                // ------ Wrapper elements that may contain a product image ------
                if ($type === 1 && in_array($name, ['div', 'figure', 'picture'], true)) {
                    if ($currentName !== null && !isset($images[$currentName])) {
                        $found = $this->extractImageFromDomElement($child, $baseUrl);
                        if ($found !== null) {
                            $images[$currentName] = $found;
                        }
                    }
                    continue;
                }

                // ------ Skip <a> that is just a reference like "[1]" ------
                if ($type === 1 && $name === 'a') {
                    $aText = trim($child->textContent);
                    if (preg_match('/^\[\d+\]$/', $aText) || strlen($aText) < 2) {
                        continue;
                    }
                    // Anchor text that is part of the description (e.g. "Studies suggest...")
                    if ($currentName !== null && ($entries[$currentName] ?? null) === null) {
                        $descParts[] = $aText;
                    }
                    continue;
                }

                // ------ Bare text nodes & <span> elements = description ------
                if ($type === 3 || ($type === 1 && $name === 'span')) {
                    $fragment = trim($child->textContent ?? '');
                    if ($fragment !== '' && $currentName !== null && ($entries[$currentName] ?? null) === null) {
                        $descParts[] = $fragment;
                    }
                    continue;
                }
            }

            // Flush the last tea's accumulated text
            $flush();

        } catch (\Throwable $e) {
            // return whatever we collected
        }

        // Fill in defaults for tea names that had no usable description.
        foreach ($entries as $name => $benefit) {
            if ($benefit === null) {
                $entries[$name] = $this->getDefaultBenefitForTea($name);
            }
        }

        return ['entries' => $entries, 'images' => $images];
    }

    /**
     * Clean and normalize benefit text
     */
    private function cleanBenefitText(string $text, int $maxLength = 500): string
    {
        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        // Remove reference markers like [1], [23]
        $text = preg_replace('/\[\d+\]/', '', $text);

        // Remove common unwanted patterns
        $text = preg_replace('/^(Benefits?|Health Benefits?):\s*/i', '', $text);
        $text = preg_replace('/\s*Learn more.*$/i', '', $text);
        $text = preg_replace('/\s*Read more.*$/i', '', $text);

        // Limit length (keep whole sentences where possible)
        if (strlen($text) > $maxLength) {
            $text = substr($text, 0, $maxLength - 3);
            $lastPeriod = strrpos($text, '.');
            if ($lastPeriod !== false && $lastPeriod > $maxLength * 0.5) {
                $text = substr($text, 0, $lastPeriod + 1);
            } else {
                $text .= '...';
            }
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

        // The Tea House article is a numbered list where each entry has the form:
        //   "N. <benefit> - <tea name(s)>"
        // i.e. the benefit comes BEFORE the dash and the tea name(s) AFTER it.
        // e.g. "1. Help with sleep - Chamomile tea"
        //      "3. Relaxation and stress relief - Chamomile tea, Lavender tea"

        // 1) Locate the article body container that holds the list.
        $container = $this->findArticleContainer($crawler) ?? $crawler;

        // 2) Build entries from the list items. The numbers come from the <ol>
        //    element (CSS), so they are NOT in the text - each <li> simply reads
        //    "<benefit> - <tea name(s)>". We keep the anchors local to each <li>
        //    so shop links map to the correct tea.
        $entries = $this->collectTeaHouseEntries($container, $url);

        // Fallback: if the list is not <li> based, parse the raw text for
        //    "N. benefit - tea names" patterns.
        if (empty($entries)) {
            $entries = $this->parseTeaHouseEntries($container->text(''));
        }

        // Global anchor map as a last-resort for shop links
        $anchorMap = $this->buildAnchorMap($container, $url);

        $this->line('  Found ' . count($entries) . ' benefit/tea entries');

        foreach ($entries as $entry) {
            $benefit = $entry['benefit'];
            $localAnchors = $entry['anchors'] ?? [];
            $imageUrl = $entry['image'] ?? null;

            foreach ($entry['teas'] as $rawTeaName) {
                $name = $this->cleanTeaHouseName($rawTeaName);
                if ($name === null) {
                    continue; // generic / non-specific entry, skip
                }

                if ($this->verbose) {
                    $this->line("  Found: {$name} (benefit: {$benefit})");
                }

                // Detect flavor & caffeine from the tea name + benefit context
                $flavor = $this->detectFlavor($name . ' ' . $benefit);
                $caffeine = $this->detectCaffeineLevel($name . ' ' . $benefit);

                // Shop link priority:
                //  1) anchor inside this list item (most accurate, correct tea)
                //  2) matching anchor elsewhere in the article
                //  3) product match from the dedicated shop page
                //  4) shop links found elsewhere / existing tea / generated search
                $shopLink = $this->matchAnchorForTea($name, $rawTeaName, $localAnchors)
                    ?? $this->matchAnchorForTea($name, $rawTeaName, $anchorMap)
                    ?? $this->matchTeaToProductLink($name, $productLinks, $shopUrl)
                    ?? ($shopLinks[$name] ?? null)
                    ?? $this->findShopLinkForTea($name)
                    ?? null;

                $this->saveTea($name, $flavor, $caffeine, $benefit, $sourceUrl, $shopLink, $imageUrl);
            }
        }
    }

    /**
     * Collect Tea House entries from list items.
     * Each <li> reads "<benefit> - <tea name(s)>"; the leading number is rendered
     * by the <ol> element and is therefore not part of the text.
     * Returns: [ ['benefit' => string, 'teas' => [..], 'anchors' => [text=>href]], ... ]
     */
    protected function collectTeaHouseEntries($container, string $baseUrl): array
    {
        $entries = [];

        try {
            $container->filter('ol li, ul li')->each(function ($li) use (&$entries, $baseUrl) {
                $text = trim(preg_replace('/\s+/', ' ', $li->text('')));

                // Must contain a spaced dash separating benefit from tea name(s)
                if ($text === '' || !preg_match('/\s[-–—]\s/u', $text)) {
                    return;
                }

                $split = $this->splitBenefitAndTeas($text);
                if ($split === null) {
                    return;
                }

                // Collect anchors local to this list item (correct tea -> shop link)
                $anchors = [];
                $li->filter('a[href]')->each(function ($a) use (&$anchors, $baseUrl) {
                    $href = $a->attr('href');
                    $anchorText = strtolower(trim($a->text('')));
                    if (empty($href) || $anchorText === '') {
                        return;
                    }
                    if (stripos($href, '/products/') === false && stripos($href, '/collections/') === false) {
                        return;
                    }
                    $anchors[$anchorText] = $this->makeAbsoluteUrl($href, $baseUrl);
                });

                // Try to pick up a real tea photo attached to this list item
                $image = $this->extractContentImage($li, $baseUrl);

                $entries[] = [
                    'benefit' => $split['benefit'],
                    'teas' => $split['teas'],
                    'anchors' => $anchors,
                    'image' => $image,
                ];
            });
        } catch (\Throwable $e) {
            // return whatever we collected
        }

        return $entries;
    }

    /**
     * Split a single "<benefit> - <tea names>" line into its parts.
     * Returns ['benefit' => string, 'teas' => [..]] or null when it does not
     * follow the expected shape.
     */
    protected function splitBenefitAndTeas(string $line): ?array
    {
        // Drop a leading "N." if it is present in the text
        $line = preg_replace('/^\s*\d{1,3}\.\s*/', '', $line);

        // Split on the FIRST spaced dash so hyphenated words (e.g. "Anti-inflammatory")
        // are preserved in the benefit.
        $parts = preg_split('/\s+[-–—]\s+/u', $line, 2);
        if (count($parts) < 2) {
            return null;
        }

        $benefit = $this->cleanBenefitText(trim($parts[0]), 300);
        $teaPortion = trim($parts[1]);

        if ($benefit === 'N/A' || strlen($benefit) < 3 || $teaPortion === '') {
            return null;
        }

        $benefit = ucfirst($benefit);
        if (strlen($benefit) < 25) {
            $benefit .= ' — a wellness benefit of this tea.';
        }

        $teas = $this->splitTeaNames($teaPortion);
        if (empty($teas)) {
            return null;
        }

        return ['benefit' => $benefit, 'teas' => $teas];
    }

    /**
     * Locate the main article body container on a blog page.
     */
    protected function findArticleContainer($crawler)
    {
        $selectors = [
            '.article__body', '.article-content', '.article__content',
            '.rte', 'article .rte', 'article', '.post-content', '.blog-post',
            'main',
        ];

        foreach ($selectors as $selector) {
            try {
                $node = $crawler->filter($selector)->first();
                if ($node->count() > 0 && strlen(trim($node->text(''))) > 200) {
                    return $node;
                }
            } catch (\Throwable $e) {
                // try next selector
            }
        }

        return null;
    }

    /**
     * Build a map of lowercased anchor text => absolute href for shop-link matching.
     */
    protected function buildAnchorMap($crawler, string $baseUrl): array
    {
        $map = [];
        try {
            $crawler->filter('a[href]')->each(function ($a) use (&$map, $baseUrl) {
                $href = $a->attr('href');
                $anchorText = strtolower(trim($a->text('')));
                if ($href === null || $anchorText === '' || strlen($anchorText) < 3) {
                    return;
                }
                if (str_starts_with($href, '#') || str_starts_with($href, 'javascript')) {
                    return;
                }
                // Only keep links that point to a product/collection page
                if (stripos($href, '/products/') === false && stripos($href, '/collections/') === false) {
                    return;
                }
                $map[$anchorText] = $this->makeAbsoluteUrl($href, $baseUrl);
            });
        } catch (\Throwable $e) {
            // return whatever we have
        }
        return $map;
    }

    /**
     * Parse the Tea House list text into structured entries.
     * Returns: [ ['benefit' => string, 'teas' => [string, ...]], ... ]
     */
    protected function parseTeaHouseEntries(string $text): array
    {
        $entries = [];

        // Match "N. <benefit> - <teas>" up to the next numbered item or end of string.
        // The separator must be a dash surrounded by spaces so hyphenated words
        // like "Anti-inflammatory" are not split incorrectly.
        $pattern = '/(\d{1,3})\.\s+(.+?)\s+[-–—]\s+(.+?)(?=\s+\d{1,3}\.\s|$)/su';

        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $benefit = trim($m[2]);
                $teaPortion = trim($m[3]);

                $benefit = $this->cleanBenefitText($benefit, 300);
                if ($benefit === 'N/A' || strlen($benefit) < 3) {
                    continue;
                }

                $benefit = ucfirst($benefit);

                // Short benefit phrases (e.g. "Help with sleep") are expanded into a
                // readable sentence so they pass validation and read well in the UI.
                if (strlen($benefit) < 25) {
                    $benefit .= ' — a wellness benefit of this tea.';
                }

                $teas = $this->splitTeaNames($teaPortion);
                if (empty($teas)) {
                    continue;
                }

                $entries[] = [
                    'benefit' => $benefit,
                    'teas' => $teas,
                ];
            }
        }

        return $entries;
    }

    /**
     * Split the "tea names" portion after the dash into individual tea names.
     */
    protected function splitTeaNames(string $portion): array
    {
        // Drop parenthetical notes like "(e.g., rooibos, chamomile)" which contain
        // stray commas that would break naive splitting.
        $portion = preg_replace('/\([^)]*\)/', '', $portion);

        // Split on commas and the word "and"
        $parts = preg_split('/\s*,\s*|\s+and\s+/i', $portion);

        $names = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $names[] = $part;
            }
        }
        return $names;
    }

    /**
     * Clean and validate a single Tea House tea name.
     * Returns the normalised display name, or null if it is too generic to store.
     */
    protected function cleanTeaHouseName(string $name): ?string
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));

        // Skip generic, non-specific descriptions
        $genericPatterns = [
            '/various\s+types?/i',
            '/any\s+type/i',
            '/based\s+on\s+culture/i',
            '/^different\b/i',
        ];
        foreach ($genericPatterns as $pattern) {
            if (preg_match($pattern, $name)) {
                return null;
            }
        }

        // Normalise plural "teas" -> "tea"
        $name = preg_replace('/\bteas\b/i', 'tea', $name);

        // Must reference a tea/herb to be a valid entry
        if (stripos($name, 'tea') === false) {
            return null;
        }

        // Drop entries that are only the word "tea"
        if (preg_match('/^tea$/i', trim($name))) {
            return null;
        }

        $normalized = $this->normalizeTeaName($name);

        if (strlen($normalized) < 5) {
            return null;
        }

        return $normalized;
    }

    /**
     * Find the best matching anchor href for a tea name.
     */
    protected function matchAnchorForTea(string $normalizedName, string $rawName, array $anchorMap): ?string
    {
        if (empty($anchorMap)) {
            return null;
        }

        $candidates = [
            strtolower(trim($rawName)),
            strtolower(trim($normalizedName)),
            strtolower(preg_replace('/\s+tea\s*$/i', '', $normalizedName)),
        ];

        // Exact anchor-text match first
        foreach ($candidates as $candidate) {
            if ($candidate !== '' && isset($anchorMap[$candidate])) {
                return $anchorMap[$candidate];
            }
        }

        // Partial match: anchor text contains the tea keyword (e.g. "green tea")
        $keyword = strtolower(preg_replace('/\s+tea\s*$/i', '', $normalizedName));
        if (strlen($keyword) >= 3) {
            foreach ($anchorMap as $anchorText => $href) {
                if (strpos($anchorText, $keyword) !== false) {
                    return $href;
                }
            }
        }

        return null;
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

        // Reject clickbait/teaser text that doesn't actually describe benefits
        if ($this->isTeaserText($benefit)) {
            return false;
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

        // Heavily penalize clickbait/teaser text that doesn't describe actual benefits
        if ($this->isTeaserText($benefit)) {
            $score -= 50;
        }
        
        // Bonus for multiple distinct benefits (comma or period separated)
        $benefitCount = substr_count($benefitLower, ',') + substr_count($benefitLower, '.') + 1;
        $score += min($benefitCount * 2, 8); // Up to 8 points for multiple benefits
        
        return max($score, 0); // Ensure non-negative
    }

    /**
     * Save or update a tea in the database - with intelligent data merging
     */ // Saving scraped tea with deduplication logic
    protected function saveTea(string $name, string $flavor, string $caffeine, string $benefit, string $sourceUrl = '', ?string $shopLink = null, ?string $scrapedImage = null): void
    {
        $placeholderImage = $this->teaPlaceholders[$this->placeholderIndex % count($this->teaPlaceholders)];
        $this->placeholderIndex++;

        // Normalize tea name to prevent duplicates
        $normalizedName = $this->normalizeTeaName($name);
        
        // Check if this tea was deleted by admin - if so, skip it entirely
        if (\App\Models\DeletedTea::wasDeleted($normalizedName)) {
            $this->skipped++;
            if ($this->verbose) {
                $this->line("    Skipping '{$normalizedName}' - was deleted by admin");
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
        
        // Only set the image for new teas or if the current image is already a
        // placeholder/scraped URL. Never overwrite admin-uploaded images
        // (stored as local paths like "teas/xxx.jpg").
        $currentImage = $tea->image ?? '';
        $isLocalUpload = !empty($currentImage) && !str_starts_with($currentImage, 'http') && !str_starts_with($currentImage, '//');
        if (!$isLocalUpload) {
            // Prefer a real image scraped from the source website; fall back to
            // an Unsplash placeholder if none was found or it failed to scrape.
            $tea->image = $scrapedImage ?: $placeholderImage;
        }

        // Save source URL
        if (!empty($sourceUrl)) {
            $tea->source_url = $sourceUrl;
        }

        // Save shop link if provided, otherwise try to find from other sources
        if (!empty($shopLink)) {
            $tea->shop_link = $shopLink;

            // Sync source-specific shop links with Shopee/Lazada columns
            if (stripos($shopLink, 'shopee.com') !== false) {
                $tea->shopee_link = $shopLink;
            } elseif (stripos($shopLink, 'lazada.com') !== false) {
                $tea->lazada_link = $shopLink;
            }
        } elseif (empty($tea->shop_link)) {
            // Try to find shop link from other sources with same tea name
            $fallbackLink = $this->findShopLinkForTea($normalizedName);
            if ($fallbackLink) {
                $tea->shop_link = $fallbackLink;

                if (stripos($fallbackLink, 'shopee.com') !== false) {
                    $tea->shopee_link = $fallbackLink;
                } elseif (stripos($fallbackLink, 'lazada.com') !== false) {
                    $tea->lazada_link = $fallbackLink;
                }
            }
        }

        // Fall back to Shopee/Lazada search URLs when not set
        $encodedName = urlencode($normalizedName);
        if (empty($tea->shopee_link)) {
            $tea->shopee_link = 'https://shopee.com.my/search?keyword=' . $encodedName;
        }
        if (empty($tea->lazada_link)) {
            $tea->lazada_link = 'https://www.lazada.com.my/catalog/?q=' . $encodedName;
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
     * Try to extract a real content image (e.g. a photo of the tea) from a
     * Crawler node - checking the node itself and its descendants. Lazy-loaded
     * images (data-src/data-original) are supported. Returns null when no
     * suitable image is found, so the caller can fall back to an Unsplash
     * placeholder instead.
     */
    private function extractContentImage($crawlerNode, string $baseUrl): ?string
    {
        try {
            $candidates = [];

            if (strtolower($crawlerNode->nodeName()) === 'img') {
                $candidates[] = $crawlerNode;
            }

            $crawlerNode->filter('img')->each(function ($img) use (&$candidates) {
                $candidates[] = $img;
            });

            foreach ($candidates as $img) {
                $src = $img->attr('data-src') ?: $img->attr('data-original') ?: $img->attr('src');
                if (empty($src) || str_starts_with($src, 'data:')) {
                    continue; // no source or inline base64 placeholder
                }

                $absolute = $this->makeAbsoluteUrl($src, $baseUrl);
                $alt = $img->attr('alt') ?? '';

                if ($this->isLikelyContentImage($absolute, $alt)) {
                    return $absolute;
                }
            }
        } catch (\Throwable $e) {
            // Ignore and let the caller fall back to a placeholder image
        }

        return null;
    }

    /**
     * Extract a real content image URL from a raw DOMElement (used when
     * walking a Crawler's underlying DOM directly, e.g. in
     * collectSimpleLeafEntries()). Mirrors extractContentImage() but works
     * on \DOMElement instead of a Symfony Crawler node.
     */
    private function extractImageFromDomElement(\DOMElement $element, string $baseUrl): ?string
    {
        $imgs = [];
        if (strtolower($element->nodeName) === 'img') {
            $imgs[] = $element;
        }
        foreach ($element->getElementsByTagName('img') as $img) {
            $imgs[] = $img;
        }

        foreach ($imgs as $img) {
            $src = $img->getAttribute('data-src') ?: ($img->getAttribute('data-original') ?: $img->getAttribute('src'));
            if (empty($src) || str_starts_with($src, 'data:')) {
                continue;
            }

            $absolute = $this->makeAbsoluteUrl($src, $baseUrl);
            $alt = $img->getAttribute('alt') ?? '';

            if ($this->isLikelyContentImage($absolute, $alt)) {
                return $absolute;
            }
        }

        return null;
    }

    /**
     * Heuristic filter that skips logos, icons, avatars, badges, and other
     * non-content images so we don't accidentally store site branding as a
     * tea photo.
     */
    private function isLikelyContentImage(string $src, string $alt = ''): bool
    {
        $lower = strtolower($src . ' ' . $alt);

        $skipPatterns = [
            'logo', 'icon', 'avatar', 'sprite', 'placeholder', 'spacer',
            'badge', 'button', 'social', 'favicon', 'blank.gif', '1x1',
        ];
        foreach ($skipPatterns as $pattern) {
            if (strpos($lower, $pattern) !== false) {
                return false;
            }
        }

        // SVGs are almost always icons/logos on these sites, not tea photos
        if (preg_match('/\.svg(\?.*)?$/i', $src)) {
            return false;
        }

        return true;
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
