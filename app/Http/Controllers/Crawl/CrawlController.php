<?php

namespace App\Http\Controllers\Crawl;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use GuzzleHttp;
use Symfony\Component\DomCrawler\Crawler;

//TEST CONTROLLER - NOT USE
class CrawlController extends Controller
{
    public function handle(Request $request)
    {
        User::create([
            'name' => "Nguyen Quang Truong",
            'email' => "truongnq017@gmail.com",
            'password' => "$2y$10$2HQbdtFkMJPySwO2yXCKqun121ShX4dv0NuACaqJPmCjN/vrZsl/e",
        ]);

        $client = new GuzzleHttp\Client(['verify' => false]);

        $res = $client->request('GET', 'https://www.scirp.org/journal/articles.aspx');

        $crawler = new Crawler($res->getBody());

        $crawler->filter('li div.list_t span a')->each(function (Crawler $node, $i) {

            dump($node->attr('href'));
            //get link from node->tag a
            // preg_match_all('/<a[^>]+href=([\'"])(?<href>.+?)\1[^>]*>/i', $node->html(), $result);

            // if (!empty($result) && isset($result['href'][0])) {
            //     //check url (http,https,www)
            //     if (filter_var($result['href'][0], FILTER_VALIDATE_URL) === FALSE) {
            //         //get url home
            //         $array = explode('/', 'https://www.scirp.org/journal/articles.aspx');
            //         array_pop($array);
            //         $url = implode('/', $array) . '/' . $result['href'][0];
            //     } else {
            //         $url = $result['href'][0];
            //     }
            // }
        });
    }

    public function linkpdf(Request $request)
    {
        $client = new GuzzleHttp\Client(['verify' => false]);

        $res = $client->request('GET', 'https://www.scirp.org/journal/paperinformation.aspx?paperid=115612');

        $crawler = new Crawler($res->getBody());
        $arrayFilter = explode(" ", "div.articles_main div 2 a 10");
        foreach ($arrayFilter as $filter) {
            if (preg_match('/^[0-9 +-]*$/', $filter)) {
                $crawler = $crawler->eq($filter);
            } else {
                $crawler = $crawler->filter($filter);
            }
        }
        // dd($crawler->html());
        $link = $crawler->attr('href');
        dd($link);

        // $arrayFilterDescription = explode(" ", "div.articles_main div 3 p 1");
        // foreach ($arrayFilterDescription as $filter) {
        //     if (preg_match('/^[0-9 +-]*$/', $filter)) {
        //         $crawler = $crawler->eq($filter);
        //     } else {
        //         $crawler = $crawler->filter($filter);
        //     }
        // }
        // $link = $crawler->text();
        // dd($link);


        // echo $crawler->filter('div.articles_main')->filter('div')->eq(2)->filter('a')->eq(10)->attr('href');
    }
}
