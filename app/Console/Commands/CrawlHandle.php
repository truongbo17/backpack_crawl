<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp;
use App\Models\Site;
use App\Models\CrawlUrl;
use stdClass;

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

            $client = new GuzzleHttp\Client(['verify' => false]);
            $res = $client->request('GET', $this->site->url);
            $site->update([
                // 'status' => $res->getStatusCode() //set status (200,500,404...)
            ]);

            if ($site->filter_parent !== "") {
                //crawl from home
                $this->parent();
            } else {
                //crawl from detail
                $this->child();
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

    public function child($urlChild = false)
    {
        if (!$urlChild) {
            $urlChild = $this->site->url;
        } else {
            $urlChild = $urlChild;
        };
        $client = new GuzzleHttp\Client(['verify' => false]);
        $res = $client->request('GET', $urlChild);
        $crawler = new Crawler($res->getBody());
        $crawlerTitle = $crawlerDescription = $crawlerLink = $crawler;

        //title
        $arrayFilterTitle = explode(" ", $this->site->filter_title);
        foreach ($arrayFilterTitle as $filter) {
            if (preg_match('/^[0-9 +-]*$/', $filter)) {
                $crawlerTitle = $crawlerTitle->eq($filter);
            } else {
                $crawlerTitle = $crawlerTitle->filter($filter);
            }
        }
        $title = $crawlerTitle->text();

        //description
        $arrayFilterDescription = explode(" ", $this->site->filter_description);
        foreach ($arrayFilterDescription as $filter) {
            if (preg_match('/^[0-9 +-]*$/', $filter)) {
                $crawlerDescription = $crawlerDescription->eq($filter);
            } else {
                $crawlerDescription = $crawlerDescription->filter($filter);
            }
        }
        $description = $crawlerDescription->text();

        //link
        $arrayFilter = explode(" ", $this->site->filter_view);
        foreach ($arrayFilter as $filter) {
            if (preg_match('/^[0-9 +-]*$/', $filter)) {
                $crawlerLink = $crawlerLink->eq($filter);
            } else {
                $crawlerLink = $crawlerLink->filter($filter);
            }
        }
        $link = $crawlerLink->attr('href');

        // config data
        if ($title !== '' || $description !== '' || $link !== '') {
            $dataStatus = 1;
            $data = (object)[
                'link' => $link,
                'title' => $title,
                'desription' => $description,
            ];
        } else {
            $dataStatus = 0;
        }


        CrawlUrl::create([
            'site' => $this->site->url,
            'url' => $urlChild,
            'data_status' => $dataStatus,
            'data' => $data,
            'status' => $res->getStatusCode(),
            'visited' => 1
        ]);
    }
}
