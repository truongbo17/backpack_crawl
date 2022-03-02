<?php

namespace App\Http\Controllers\Crawl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp;
use Symfony\Component\DomCrawler\Crawler;

class CrawlController extends Controller
{
    public function handle(Request $request)
    {
        $client = new GuzzleHttp\Client(['verify' => false]);

        $res = $client->request('GET', 'https://www.scirp.org/journal/articles.aspx');

        $crawler = new Crawler($res->getBody());

        $crawler->filter('li div.list_t span')->each(function (Crawler $node, $i) {

            //get link from node->tag a
            preg_match_all('/<a[^>]+href=([\'"])(?<href>.+?)\1[^>]*>/i', $node->html(), $result);

            if (!empty($result) && isset($result['href'][0])) {
                //check url (http,https,www)
                if (filter_var($result['href'][0], FILTER_VALIDATE_URL) === FALSE) {
                    //get url home
                    $array = explode('/', 'https://www.scirp.org/journal/articles.aspx');
                    array_pop($array);
                    $url = implode('/', $array) . '/' . $result['href'][0];
                } else {
                    $url = $result['href'][0];
                }

                echo $url;
            }
        });

    }
}
