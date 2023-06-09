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

use Illuminate\Support\Carbon;

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
    protected $status = true;

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
            $this->status = true;
            if($user->mailSent >= $user->mailLimit) {
                $this->info("mail limited");
                $this->status = false;
                continue;
            }
            if($user->mailStatus == "off") {
                $this->info("mail notification off");
                continue;
            }
            foreach($notifications as $notification) {
                
                set_time_limit(0);

                if($this->status) {
                    foreach($notification->goods as $item) {
                        $this->initBrowser();
                        if($this->status) {
                            try {
                                $crawler = $this->getPageHTMLUsingBrowser($item->link);
                                $crawler->filter('#item-info .mer-spacing-b-24 .mer-spacing-b-16 mer-text')->each(function($node) use ($item) {
                                    $text = $node->siblings()->filter('span')->text();
                                    if(str_contains($text,'分前')){
                                        $availableUser = User::find($this->user->id);
                                        if($availableUser->mailSent >= $availableUser->mailLimit) {
                                            $this->info("mail limited");
                                            $this->status = false;
                                        }else{
                                            $this->storeComments($node->text(),$item);
                                        }
                                    }else{
                                        $this->info("old comment");
                                    }
                                    
                                });
                            }catch(\Throwable  $e){
                                
                            }
                            
                        }else{
                            break;
                        }
                        $this->driver->close();
                        $this->info("next goods");
                    }
                    $this->info("next notification");

                }else{
                    break;
                }
            }

            $this->info("next user");
        }
        $this->info("end");
        return 0;
    }

    public function storeComments($comment, $goods) {
        $comment_checker = Comment::where('goods_id',$goods->id)->where('comment',$comment)->sharedLock()->exists();
        if($comment_checker) {
            $this->info('double comment');
        }else{
            $this->sendEmail($goods,$comment);
            sleep(5);
        }
    }

    /**
     * Get page using browser.
     */
    public function getPageHTMLUsingBrowser(string $url)
    {
        $this->info($url);

        $response = $this->driver->get($url);

        $this->driver->wait(5000,1000)->until(
            function () {
                $elements = $this->driver->findElements(WebDriverBy::XPath("//main[contains(@id,'main')]"));
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

    public function sendEmail($goods, $comment) {

        $user = $this->user;

        Comment::create([
            'user_id' => $user->id,
            'goods_id' => $goods->id,
            'comment' => $comment,
        ]);

        $content = $user->name."様 コメントがあります。". PHP_EOL .PHP_EOL;

        $title =  "商品名 :　".$goods->itemName. PHP_EOL ." : ".$goods->price."円". PHP_EOL ."商品ページ : ".$goods->link. PHP_EOL;
        
        $content .= "商品名　: ".$goods->itemName. PHP_EOL ."商品価格　: ".$goods->price."円". PHP_EOL ."商品ページ : ".$goods->link. PHP_EOL;
        $content .= "コメント　: ". PHP_EOL .$comment. PHP_EOL;
        // $content .= "<img src='".$goods->itemImageUrl."' alt='出品画面' >";
        // if(isset($this->keyword)) {
        //     $content .= "キーワード : " .$this->keyword. PHP_EOL . PHP_EOL . PHP_EOL;
        // }
            
        $email = $user->email;

        $user_id = 'be36961ex';
        $api_key = '9Vya0hHkGEzbplMrCMEIhSolrwy8PVmVq98JBTr7ZmWCUXW8mNRMnM4njwXjwiju';
        // $user_id = 'phoenix';
        // $api_key = 'lTvBUaSpw6ZsG5erwfjiAcTpxOw3zM7t4jhqSBNa0D7hll5njQwKsMj1abBVt1cK';

        $img = storage_path('image') . '/' . $goods->itemName .'.jpg';
        copy($goods->itemImageUrl, $img);

        \Blastengine\Client::initialize($user_id, $api_key);
        $transaction = new \Blastengine\Transaction();
        $transaction
            ->to($email)
            ->from("superdev195128@gmail.com")
            ->subject($title)
            ->text_part($content)
            ->attachment($img);
        try {
            $transaction->send();
        } catch ( Exception $ex ) {
            $this->info(json_encode($ex));
        }
        
        // 結果の出力
        $this->info("sent");
        $this->info(json_encode($user));

        User::where('id',$user->id)->lockForUpdate()->increment('mailSent', 1);

        // DB::transaction(function() {
            // User::where('id',$user->id)->lockForUpdate()->update(array('mailSent' => $mailSent + 1));
            
        // });
        
    }
}
