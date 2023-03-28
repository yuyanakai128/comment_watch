<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverCheckboxes;
use Facebook\WebDriver\WebDriverRadios;
use Facebook\WebDriver\WebDriverSelect;
use Symfony\Component\DomCrawler\Crawler;

use DB;
use App\Models\User;
use App\Models\Goods;
use App\Models\Comment;
use App\Models\Notification;

class listGoods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'list:goods';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $driver;
    public const SENT_COUNT = 100;
    protected $count;
    protected $user;
    protected $results = [];

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
        $this->info("start");

        $availableUsers = User::where('is_admin',0)->where('active',1)->get();
        foreach($availableUsers as $user) {
            $this->user = $user;
            $notifications = $user->notifications;

            if($user->mailSent >= $user->mailLimit) {
                $this->info("mail limited");
                continue;
            }
            if($user->mailStatus == "off") {
                $this->info("mail notification off");
                continue;
            }

            set_time_limit(0);

            foreach($notifications as $notification) {
                $this->initBrowser();
                $this->count = 1;
                $this->results = [];

                $url = "https://jp.mercari.com/search?keyword=".$notification->keyword;
                if(isset($notification->lower_price)){
                    $url .= '&price_min='.$notification->lower_price;
                }
                if(isset($notification->category_id)){
                    $url .= '&category_id='.$notification->category_id;
                }
                $url .= '&status=on_sale';
                if(isset($notification->upper_price)){
                    $url .= '&price_max='.$notification->upper_price;
                }
                if(isset($notification->upper_price)){
                    $url .= '&price_max='.$notification->upper_price;
                }
                $this->info($url);

                $crawler = $this->getPageHTMLUsingBrowser($url);
                try {
                    $crawler->filter('#item-grid li')->each(function ($node) {
                        if($this->count > self::SENT_COUNT) return false;
                        $url = $node->filter('a')->attr('href');
                        $itemImageUrl = $node->filter('mer-item-thumbnail')->attr('src');
                        $itemName   = $node->filter('mer-item-thumbnail')->attr('alt');
                        $itemName = str_replace("のサムネイル","",$itemName);
                        $price = $node->filter('mer-item-thumbnail')->attr('price');
                        array_push($this->results, [
                            'link' => 'https://jp.mercari.com'.$url,
                            'itemImageUrl' => $itemImageUrl,
                            'itemName' => $itemName,
                            'price' => $price,
                        ]);
                       
                        $this->count++;
                    });
                }catch(\Throwable  $e){
                    $this->info($e);
                }
                $this->driver->close();
                $this->info('next notification');

                $this->storeGoods($this->results, $notification->id);
            }

        }

        $this->info("end");

        sleep(60);
        
        return 0;
    }

    /**
     * Get page using browser.
     */
    public function getPageHTMLUsingBrowser(string $url)
    {
        $response = $this->driver->get($url);

        $this->driver->wait(5000,1000)->until(
            function () {
                $elements = $this->driver->findElements(WebDriverBy::XPath("//div[contains(@id,'search-result')]"));
                sleep(5);
                return count($elements) > 0;
            },
        );
        
        return new Crawler($response->getPageSource(), $url);
    }
    /**
     * Init browser.
     */
    public function initBrowser()
    {
        $options = new ChromeOptions();
        $arguments = ['--disable-gpu', '--no-sandbox', '--disable-images', '--headless'];

        $options->addArguments($arguments);

        $caps = DesiredCapabilities::chrome();
        $caps->setCapability('acceptSslCerts', false);
        $caps->setCapability(ChromeOptions::CAPABILITY, $options);
        
        $this->driver = RemoteWebDriver::create('http://localhost:4444', $caps);
    }

    public function storeGoods($results,$notification_id) {
        $inserted_data = [];
        $items = $results;
        $goods = Goods::where('notification_id',$notification_id)->get();
        foreach($goods as $item) {
            foreach($results as $key => $result) {
                if($item->link == $result['link']) {
                    unset($items[$key]);break;;
                }
            }
        }
        foreach($items as $item) {
            if((count($goods) + count($inserted_data) ) >= self::SENT_COUNT) {
                $this->info('delete');
                $limit = count($goods) + count($inserted_data) - self::SENT_COUNT;

                $deleted_items = Goods::where('notification_id',$notification_id)->orderBy('id','DESC')->take($limit)->get();
                $ids = [];
                foreach($deleted_items as $item) {
                    array_push('ids',$item->id);
                }
                Comment::whereIn('goods_id',$ids)->delete();
                Goods::where('notification_id',$notification_id)->orderBy('id','DESC')->take($limit)->delete();
            }
            array_push($inserted_data,array(
                'notification_id' => $notification_id,
                'itemName' => $item['itemName'],
                'link' => $item['link'],
                'itemImageUrl' => $item['itemImageUrl'],
                'price' => $item['price'],
            ));
        }
        Goods::lockForUpdate()->insert($inserted_data);
    }

}
