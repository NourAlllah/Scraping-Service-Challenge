<?php

namespace App\Services;

use GuzzleHttp\Client as guzzle;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Product;

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
    

    public function getNumberOfPages($html)
    {
        $crawler = new Crawler($html);

        $pageNumbers = $crawler->filter('.pg')->each(function (Crawler $node) {
            return (int) trim($node->text());
        });

        return !empty($pageNumbers) ? max($pageNumbers) : 1; 
    }

    public function scrapProductsData()
    {
        /* $url="https://www.jumia.com.eg/category-fashion-by-jumia?page="; */
        $Url = "https://www.jumia.com.eg/flash-sales?page=";

        $userAgent = $this->getNewUserAgent();
        $allProducts = [];

        try {

            $response = $this->client->request('GET', $Url . "1", [
                'headers' => [
                    'User-Agent' => $userAgent,
                ],
            ]);

            $html = $response->getBody()->getContents();

            $totalPages = $this->getNumberOfPages($html);

            // Loop through all pages
            for ($page = 1; $page <= $totalPages; $page++) {
                $pageUrl = $Url . $page;
                
                $response = $this->client->request('GET', $pageUrl, [
                    'headers' => [
                        'User-Agent' => $userAgent,
                    ],
                ]);

                $html = $response->getBody()->getContents();
                $crawler = new Crawler($html);

                $products = $crawler->filter('.prd')->each(function (Crawler $node) {
                    $title = $node->filter('.name')->text();
                    $price = $node->filter('.prc')->text();
                    $image = $node->filter('img')->attr('data-src');

                    return [
                        'title' => trim($title),
                        'price' => trim($price),
                        'image_url' => $image,
                    ];
                });

                // Merge all products
                $allProducts = array_merge($allProducts, $products);
            }

            return $allProducts; 

        } catch (\Throwable $th) {
            return $th;
        }
    }

    
}
