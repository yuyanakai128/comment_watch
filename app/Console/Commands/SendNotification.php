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

class SendNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $driver;
    public const SENT_COUNT = 50;
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
            foreach($notifications as $notification) {
                
                set_time_limit(0);
                $this->initBrowser();

                foreach($notification->goods as $item) {
                    
                    $crawler = $this->getPageHTMLUsingBrowser($item->link);
                    try {
                        $crawler->filter('#item-info .mer-spacing-b-24 .mer-spacing-b-16 mer-text')->each(function($node) use ($item) {
                            $this->storeComments($node->text(),$item);
                        });
                        
                    }catch(\Throwable  $e){
                        continue;
                    }

                }

                $this->driver->close();

                // $this->sendEmail($this->results, $this->user);
            }
        }
        $this->info("end");
        return 0;
    }

    public function storeComments($comment, $goods) {
        $comments = Comment::where('goods_id',$goods->id)->where('comment',$comment)->get();
        if(count($comments) > 0) {
            $this->info('double comment');
        }else{
            $this->info($comment);
            Comment::create([
                'user_id' => $this->user->id,
                'goods_id' => $goods->id,
                'comment' => $comment,
            ]);
        }
    }

        /**
     * Get page using browser.
     */
    public function getPageHTMLUsingBrowser(string $url)
    {
        $response = $this->driver->get($url);

        $this->driver->wait(5000,1000)->until(
            function () {
                $elements = $this->driver->findElements(WebDriverBy::XPath("//div[contains(@id,'item-info')]"));
                sleep(2);
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

    public function sendEmail($results, $user) {

        $items = $results;
        $items = array_unique($items,SORT_REGULAR);

        $mailLimit = $user->mailLimit;
        
        $urls = TimeLine::where('user_id',$user->id)->get();
        foreach($urls as $url) {
            foreach($results as $key => $result) {
                if($url->url == $result['url']) {
                    unset($items[$key]);break;
                }
            }
        }
        $content = $user->name."様 商品があります。". PHP_EOL .PHP_EOL;
        
        if(count($items) > 0) {
            
            foreach($items as $item) {
                
                $content .= "商品名　".$item['itemName']. PHP_EOL ."商品価格　".$item['currentPrice']."円". PHP_EOL ."商品サービス　".$item['service']. PHP_EOL ."商品ページ ".$item['url']. PHP_EOL;
                if(isset($this->keyword)) {
                    $content .= "キーワード : " .$this->keyword. PHP_EOL . PHP_EOL . PHP_EOL;
                }
            }
            $email = $user->email;
            // $user_id = 'trialphoenix';
            // $api_key = '2aUSJ6gntGT6paez6XPaihMc0XEXZDWJqbwIVbRmpSWXwsDCKGjUZRDjfMIjt4Hw';
            $user_id = 'phoenix';
            $api_key = 'lTvBUaSpw6ZsG5erwfjiAcTpxOw3zM7t4jhqSBNa0D7hll5njQwKsMj1abBVt1cK';
            \Blastengine\Client::initialize($user_id, $api_key);
            $transaction = new \Blastengine\Transaction();
            $transaction
                ->to($email)
                ->from("devlife128@gmail.com")
                ->subject('商品があります。')
                ->text_part($content);
            try {
                $transaction->send();
            } catch ( Exception $ex ) {
                // Error
            }
            
            // 結果の出力
            $this->info("sent");

            DB::beginTransaction();
            try {
                $inserted_data = [];
                foreach($items as $item) {
                    array_push($inserted_data,[
                        'user_id' => $user->id,
                        'itemName' => $item['itemName'],
                        'keyword' => $this->keyword,
                        'itemImageUrl' => $item['itemImageUrl'],
                        'currentPrice' => $item['currentPrice'],
                        'url' => $item['url'],
                        'service' => $item['service'],
                        "created_at" =>  \Carbon\Carbon::now(), # new \Datetime()
                        "updated_at" => \Carbon\Carbon::now(),  # new \Datetime()
                    ]) ;
                }
                
                TimeLine::lockForUpdate()->insert($inserted_data);
                $availableUser = User::where('id',$user->id)->lockForUpdate()->first();
                $mailSent = $availableUser->mailSent;
                User::where('id',$user->id)->lockForUpdate()->update(array('mailSent' => $mailSent + 1));

                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                
            }
            
        } else {
            $this->info("There are no matching items");
        }
        
    }
}
