<?php
require('vendor/autoload.php');

use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

// チャンネルアクセストークンとチャネルシークレットキー
$channel_access_token = 'vqEGyu5CJdyFmEOqTgCkQYf30AxrXLchtPJv+4fIoACpZw3Gy6ojpxOnMt+k6M+jHqs/9hKJDpEMdTXvcFTthv6LtpWQ3mQhiay/ueBGnKX6tERojSgEU3IfodtvmKh9a0wLuMr6l66fs7pNOX0iuAdB04t89/1O/w1cDnyilFU=';
$channel_secret = '62f1841afc49ba6dc6886a4053c1a53f';



// $hoge = new TextMessageBuilder('hoge', 'foo');
// echo "<pre>";
// var_dump($hoge);
// echo "</pre>";

// 
$http_client = new CurlHTTPClient($channel_access_token);
$bot = new LINEBot($http_client, ['channelSecret' => $channel_secret]);

// 署名の検証
$http_request_body = file_get_contents('php://input');
$hash = hash_hmac('sha256', $http_request_body, $channel_secret, true);
$signature = base64_encode($hash);

// WebhookイベントオブジェクトをJSONで取得
$events = $bot->parseEventRequest($http_request_body, $signature);
$event = $events[0];

$reply_token = $event->getReplyToken();
$reply_text = $event->getText();
$user_id = $event->getUserId();

// ユーザー情報取得
$response = $bot->getProfile($user_id);
if ($response->isSucceeded()) {
    $profile = $response->getJSONDecodedBody();
    echo $profile['displayName'];
    echo $profile['pictureUrl'];
    echo $profile['statusMessage'];
}


// 入力されたメッセージを返却
// $messageData = [
//   'type' => 'text',
//   'text' => '何ですか？？',
// ];
// $bot->replyText($reply_token, 'hello',$messageData);

// 任意のテキストメッセージを返却
$textMessageBuilder = new TextMessageBuilder('はじめまして', $profile['displayName'].'さん！');
// $textMessageBuilder = new TextMessageBuilder('はじめまして', $profile['displayName'].'さん！');
// $stamp = new StickerMessageBuilder("446", "1988");
// if($reply_text === "aaa"){
//   $textMessageBuilder = new StickerMessageBuilder("446", "1988");
// }


// $response = $bot->replyMessage($reply_token, $textMessageBuilder);
// echo $response->getHTTPStatus() . ' ' . $response->getRawBody();


replyMultiMessage($bot, $event->getReplyToken(),
    new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("TextMessage"),
    new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder("https://" . $_SERVER["HTTP_HOST"] . "/imgs/original.jpg", "https://" . $_SERVER["HTTP_HOST"] . "/imgs/preview.jpg"),
    new \LINE\LINEBot\MessageBuilder\LocationMessageBuilder("LINE", "東京都渋谷区渋谷2-21-1 ヒカリエ27階", 35.659025, 139.703473),
    new \LINE\LINEBot\MessageBuilder\StickerMessageBuilder(1, 1)
);

// 複数のメッセージを送る関数
function replyMultiMessage($bot, $replyToken, ...$msgs) {
    $builder = new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder();
    foreach($msgs as $value) {
        $builder->add($value);
    }
    $response = $bot->replyMessage($replyToken, $builder);
    if (!$response->isSucceeded()) {
        error_log('Failed!'. $response->getHTTPStatus . ' ' . $response->getRawBody());
    }
}

// デバッグ
error_log(print_r($event, true) . "\n", 3, 'debug.log');

