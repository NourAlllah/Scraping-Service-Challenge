<?php

namespace App\Services;

use GuzzleHttp\Client as guzzle;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Product;
use Illuminate\Support\Facades\Log;


class ScraperService
{
    private $client;
    private $userAgents;

    public function __construct()
    {
        $this->client = new guzzle([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->userAgents = [
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36 Edg/119.0.0.0",
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36",
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36",
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/119.0",
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
            "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36",
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 12_6_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36",
            "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36",
            "Mozilla/5.0 (iPhone; CPU iPhone OS 17_3 like Mac OS X) AppleWebKit/537.36 (KHTML, like Gecko) Version/17.3 Mobile/15E148 Safari/537.36",
            "Mozilla/5.0 (iPad; CPU OS 17_3 like Mac OS X) AppleWebKit/537.36 (KHTML, like Gecko) Version/17.3 Mobile/15E148 Safari/537.36",
            "Mozilla/5.0 (Android 14; Mobile; rv:120.0) Gecko/120.0 Firefox/120.0"
        ];
    }

    public function getNewUserAgent()
    {
        return $this->userAgents[array_rand($this->userAgents)];
    }
   
    public function getNumberOfPages($url)
    {
        $userAgent = $this->getNewUserAgent();
    
        try {
            $response = $this->client->request('GET', $url . "1", [
                'headers' => [
                    'User-Agent' => $userAgent,
                ],
            ]);
    
            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html);
    
            $lastPageLink = $crawler->filter('a[aria-label="Last Page"]')->attr('href');
    
            if ($lastPageLink) {
                preg_match('/page=(\d+)/', $lastPageLink, $matches);
                return isset($matches[1]) ? (int) $matches[1] : 1;
            }
    
            return 1; 
    
        } catch (\Throwable $th) {
            return 1; 
        }
    }

    public function saveProductsToDB($products)
    {
        foreach ($products as $productData) {
            Product::updateOrCreate(
                ['title' => $productData['title'], 'price' => $productData['price']], 
                ['image_url' => $productData['image_url']]
            );
        }
    }
    
    public function scrapProductsData()
    {
        //YOU CAN ADD ANY CATEGORY Link
        $Url = "https://www.jumia.com.eg/cameras/?page=";


        $userAgent = $this->getNewUserAgent();
        $allProducts = [];

        try {

            $totalPages = $this->getNumberOfPages($Url);

            //looping here over the pages 
            for ($page = 1; $page <= $totalPages; $page++) {
                $pageUrl = $Url . $page;
                
                //Without Proxy
                $response = $this->client->request('GET', $pageUrl, [
                    'headers' => [
                        'User-Agent' => $userAgent,
                    ],
                ]);

                //With Proxy 
                /* $proxyClient = new guzzle();
                $proxyResponse = $proxyClient->get('http://localhost:8080/get_proxy');
                $proxyUrl = (string) $proxyResponse->getBody();

                $client = new guzzle([
                    'proxy' => $proxyUrl,
                    'headers' => [
                        'User-Agent' => $userAgent,
                    ],
                ]);
                $response = $client->get($pageUrl); */

                $html = $response->getBody()->getContents();
                $crawler = new Crawler($html);

                $products = $crawler->filter('.prd')->each(function (Crawler $node) {
                    $title = $node->filter('.name')->text();
                    $price = $node->filter('.prc')->text();
                    $image = $node->filter('img')->attr('data-src');

                    if (empty($image)) {
                        $image = 'https://thumb.ac-illust.com/b1/b170870007dfa419295d949814474ab2_t.jpeg'; 
                    }
                    
                    $price = preg_replace('/[^\d.]/', '', $price); 

                    return [
                        'title' => trim($title),
                        'price' => trim($price),
                        'image_url' => $image,
                    ];
                });

                $allProducts = array_merge($allProducts, $products);
            }

            // Saving products to DB 
            try {
                $this->saveProductsToDB($allProducts);
            } catch (\Throwable $th) {
                Log::error("Saving products failed: " . $th->getMessage());
                return $th;
            }

            return $allProducts; 

        } catch (\Throwable $th) {
            Log::error("Scraper failed: " . $th->getMessage());
            return $th;
        }
    }

    
}
