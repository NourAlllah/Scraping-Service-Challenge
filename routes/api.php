<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScraperController;
use App\Http\Controllers\ProductController;


Route::get('/scrape', [ScraperController::class, 'scrape']);

Route::get('/products', [ProductController::class, 'index']);
