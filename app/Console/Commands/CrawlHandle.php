<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp;
use App\Models\Site;

class CrawlHandle extends Command
{
    protected $url;
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
            $this->url = $site->url;

            $client = new GuzzleHttp\Client(['verify' => false]);

            $res = $client->request('GET', $this->url);

            $crawler = new Crawler($res->getBody());

            //crawl from home
            if ($site->filter_parent !== null) {
                $this->parent($crawler, $site);
            }
        }
    }

    public function parent($crawler, $site)
    {
        $crawler->filter($site->filter_parent)->each(function (Crawler $node, $i) {

            //get link from node->(don't tag a)
            preg_match_all('/<a[^>]+href=([\'"])(?<href>.+?)\1[^>]*>/i', $node->html(), $result);

            if (!empty($result) && isset($result['href'][0])) {
                //check url (http,https,www)
                if (filter_var($result['href'][0], FILTER_VALIDATE_URL) === FALSE) {
                    //get url home
                    $array = explode('/', $this->url);
                    array_pop($array);
                    $urlChild = implode('/', $array) . '/' . $result['href'][0];
                } else {
                    $urlChild = $result['href'][0];
                }

                $this->child($urlChild);
            }
        });
    }

    public function child($url)
    {
        print($url);
    }
}
