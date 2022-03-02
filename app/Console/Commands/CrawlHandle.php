<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp;
use App\Models\Site;
use App\Models\CrawlUrl;

class CrawlHandle extends Command
{
    private $site;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:crawl';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $listSite = Site::where('status', 0)->get();

        if ($listSite === null) {
            return false;
        }

        foreach ($listSite as $site) {
            $this->site = $site;

            $site->update([
                // 'status' => $res->getStatusCode() //set status (200,500,404...)
            ]);

            if ($site->filter_parent !== null) {
                //crawl from home
                $this->parent();
            } else {
                //crawl from detail
                $this->child('');
            }
        }
    }

    public function parent()
    {
        $client = new GuzzleHttp\Client(['verify' => false]);
        $res = $client->request('GET', $this->site->url);
        $crawler = new Crawler($res->getBody());

        $crawler->filter($this->site->filter_parent)->each(function (Crawler $node, $i) {

            //get link from node->(don't tag a)
            preg_match_all('/<a[^>]+href=([\'"])(?<href>.+?)\1[^>]*>/i', $node->html(), $result);

            if (!empty($result) && isset($result['href'][0])) {
                //check url (http,https,www)
                if (filter_var($result['href'][0], FILTER_VALIDATE_URL) === FALSE) {
                    //get url home
                    $array = explode('/', $this->site->url);
                    array_pop($array);
                    $urlChild = implode('/', $array) . '/' . $result['href'][0];
                } else {
                    $urlChild = $result['href'][0];
                }

                $this->child($urlChild);
            }
        });
    }

    public function child($urlChild)
    {
        $urlChild = $urlChild === '' ? $this->site->url : $urlChild;

        $client = new GuzzleHttp\Client(['verify' => false]);
        $res = $client->request('GET', 'https://www.scirp.org/journal/paperinformation.aspx?paperid=115612');
        $crawler = new Crawler($res->getBody());

        //get data
        $title = $crawler->filter($this->site->filter_title)->each(function (Crawler $node, $i) {
            return $node->text();
        })[0];

        //description
        $arrayFilterDescription = explode(" ", "div.articles_main div 3 p 1");
        foreach ($arrayFilterDescription as $filter) {
            if (preg_match('/^[0-9 +-]*$/', $filter)) {
                $crawler = $crawler->eq($filter);
            } else {
                $crawler = $crawler->filter($filter);
            }
        }
        $description = $crawler->text();

        //link
        $arrayFilter = explode(" ", "div.articles_main div 2 a 10");
        foreach ($arrayFilter as $filter) {
            if (preg_match('/^[0-9 +-]*$/', $filter)) {
                $crawler = $crawler->eq($filter);
            } else {
                $crawler = $crawler->filter($filter);
            }
        }
        $link = $crawler->attr('href');
        dd($link);

        //config data
        // if ($title !== '' && $description !== '' && $link !== '') {
        //     $dataStatus = 1;
        //     $data = [];
        // } else {
        //     $dataStatus = 0;
        // }

        // CrawlUrl::create([
        //     'site' => $this->site->url,
        //     'url' => $urlChild,
        //     'data_status' => $dataStatus,
        //     'data' => '{}',
        //     'status' => $res->getStatusCode(),
        //     'visted' => 1
        // ]);
    }
}
