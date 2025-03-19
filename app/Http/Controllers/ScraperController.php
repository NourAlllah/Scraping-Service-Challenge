<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ScraperService;

class ScraperController extends Controller
{
    private $scraperService;

    public function __construct(ScraperService $scraperService)
    {
        $this->scraperService = $scraperService;
    }

    public function scrape()
    {

        $response = $this->scraperService->scrapProductsData();

        return $response;

    }

}
