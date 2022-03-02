<?php

namespace App\Http\Controllers\Crawl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CrawlUrl;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $data = CrawlUrl::select('data', 'created_at')->get();

        return view('index', compact('data'));
    }
}
