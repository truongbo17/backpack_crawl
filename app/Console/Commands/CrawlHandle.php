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

            if (strlen($site->filter_parent) > 0) {
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

            //get link from node->(don't tag a,parent of a)
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
        $crawlerTitle = $crawlerDescription = $crawlerLink = $crawlerLinkChild = $crawler;

        //title
        $arrayFilterTitle = explode(" ", $this->site->filter_title);
        foreach ($arrayFilterTitle as $filter) {
            if (preg_match('/^[0-9 +-]*$/', $filter)) {
                $crawlerTitle = $crawlerTitle->eq($filter);
            } else {
                $crawlerTitle = $crawlerTitle->filter($filter);
            }
        }
        if ($crawlerTitle->count()) {
            $title = $crawlerTitle->text();
        }

        //description
        $arrayFilterDescription = explode(" ", $this->site->filter_description);
        foreach ($arrayFilterDescription as $filter) {
            if (preg_match('/^[0-9 +-]*$/', $filter)) {
                $crawlerDescription = $crawlerDescription->eq($filter);
            } else {
                $crawlerDescription = $crawlerDescription->filter($filter);
            }
        }
        if ($crawlerDescription->count()) {
            $description = $crawlerDescription->text();
        }

        //view
        $arrayFilter = explode(" ", $this->site->filter_view);
        foreach ($arrayFilter as $filter) {
            if (preg_match('/^[0-9 +-]*$/', $filter)) {
                $crawlerLink = $crawlerLink->eq($filter);
            } else {
                $crawlerLink = $crawlerLink->filter($filter);
            }
        }
        if ($crawlerLink->count()) {
            $link = $crawlerLink->attr('href');
        }

        // config data
        if (isset($title) && isset($description) && isset($link)) {
            $dataStatus = 1;

            $data = (object)[
                'link' => $link,
                'title' => $title,
                'desription' => $description,
            ];
        } else {
            $dataStatus = 0;
            $data = (object)[];
        }

        // store data
        CrawlUrl::create([
            'site' => $this->site->url,
            'url' => $urlChild,
            'data_status' => $dataStatus,
            'data' => $data,
            'status' => $res->getStatusCode(),
            'visited' => 1
        ]);

        //-------------------------------------------------------------------------------------------
        if ($this->site->filter_link === null) {
            return;
        }
        //link child from involve post
        $arrayFilterLinkChild = explode(" ", $this->site->filter_link);
        foreach ($arrayFilterLinkChild as $filter) {
            if (preg_match('/^[0-9 +-]*$/', $filter)) {
                $crawlerLinkChild = $crawlerLinkChild->eq($filter);
            } else {
                $crawlerLinkChild = $crawlerLinkChild->filter($filter);
            }
        }
        if ($crawlerLinkChild->count()) {
            $linkChildInvolve = $crawlerLinkChild->attr('');

            // //check url (http,https,www)
            if (filter_var($linkChildInvolve, FILTER_VALIDATE_URL) === FALSE) {
                //get url home
                $array = explode('/', $this->site->url);
                array_pop($array);
                $urlLinkChild = implode('/', $array) . '/' . $linkChildInvolve;
            } else {
                $urlLinkChild = $linkChildInvolve;
            }
            $this->child($urlLinkChild);
        }
    }
}
