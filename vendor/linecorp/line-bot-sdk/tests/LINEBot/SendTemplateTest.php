<?php

/**
 * Copyright 2016 LINE Corporation
 *
 * LINE Corporation licenses this file to you under the Apache License,
 * version 2.0 (the "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at:
 *
 *   https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

namespace LINE\Tests\LINEBot;

use LINE\LINEBot;
use LINE\LINEBot\Constant\ActionType;
use LINE\LINEBot\Constant\MessageType;
use LINE\LINEBot\Constant\PostbackInputOption;
use LINE\LINEBot\Constant\TemplateType;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselColumnTemplateBuilder;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\Uri\AltUriBuilder;
use LINE\LINEBot\TemplateActionBuilder\DatetimePickerTemplateActionBuilder;
use LINE\Tests\LINEBot\Util\DummyHttpClient;
use PHPUnit\Framework\TestCase;

class SendTemplateTest extends TestCase
{
    public function testReplyTemplate()
    {
        $mock = function ($testRunner, $httpMethod, $url, $data) {
            /** @var \PHPUnit\Framework\TestCase $testRunner */
            $testRunner->assertEquals('POST', $httpMethod);
            $testRunner->assertEquals('https://api.line.me/v2/bot/message/reply', $url);

            $testRunner->assertEquals('REPLY-TOKEN', $data['replyToken']);
            $testRunner->assertEquals(1, count($data['messages']));

            $message = $data['messages'][0];
            $testRunner->assertEquals(MessageType::TEMPLATE, $message['type']);
            $testRunner->assertEquals('alt test', $message['altText']);

            $template = $message['template'];
            $testRunner->assertEquals(TemplateType::BUTTONS, $template['type']);
            $testRunner->assertEquals('button title', $template['title']);
            $testRunner->assertEquals('button button', $template['text']);
            $testRunner->assertEquals('https://example.com/thumbnail.jpg', $template['thumbnailImageUrl']);

            $actions = $template['actions'];
            $testRunner->assertEquals(5, count($actions));
            $testRunner->assertEquals(ActionType::POSTBACK, $actions[0]['type']);
            $testRunner->assertEquals('postback label', $actions[0]['label']);
            $testRunner->assertEquals('post=back', $actions[0]['data']);

            $testRunner->assertEquals(ActionType::MESSAGE, $actions[1]['type']);
            $testRunner->assertEquals('message label', $actions[1]['label']);
            $testRunner->assertEquals('test message', $actions[1]['text']);

            $testRunner->assertEquals(ActionType::URI, $actions[2]['type']);
            $testRunner->assertEquals('uri label', $actions[2]['label']);
            $testRunner->assertEquals('https://example.com', $actions[2]['uri']);
            if (isset($actions[2]['altUri'])) {
                $testRunner->assertEquals(
                    ['desktop' => 'http://example.com/pc/page/222'],
                    $actions[2]['altUri']
                );
            }

            $testRunner->assertEquals(ActionType::POSTBACK, $actions[3]['type']);
            $testRunner->assertEquals('postback label2', $actions[3]['label']);
            $testRunner->assertEquals('post=back2', $actions[3]['data']);
            $testRunner->assertEquals('extend text', $actions[3]['displayText']);
            $testRunner->assertEquals('openKeyboard', $actions[3]['inputOption']);

            $testRunner->assertEquals(ActionType::POSTBACK, $actions[4]['type']);
            $testRunner->assertEquals('postback label3', $actions[4]['label']);
            $testRunner->assertEquals('post=back3', $actions[4]['data']);
            $testRunner->assertEquals('extend text2', $actions[4]['displayText']);
            $testRunner->assertEquals('openKeyboard', $actions[4]['inputOption']);
            $testRunner->assertEquals('fill in text', $actions[4]['fillInText']);

            return ['status' => 200];
        };
        $bot = new LINEBot(new DummyHttpClient($this, $mock), ['channelSecret' => 'CHANNEL-SECRET']);

        // test case that AltUriBuilder is set
        $res = $bot->replyMessage(
            'REPLY-TOKEN',
            new LINEBot\MessageBuilder\TemplateMessageBuilder(
                'alt test',
                new ButtonTemplateBuilder(
                    'button title',
                    'button button',
                    'https://example.com/thumbnail.jpg',
                    [
                        new PostbackTemplateActionBuilder('postback label', 'post=back'),
                        new MessageTemplateActionBuilder('message label', 'test message'),
                        new UriTemplateActionBuilder(
                            'uri label',
                            'https://example.com',
                            new AltUriBuilder('http://example.com/pc/page/222')
                        ),
                        new PostbackTemplateActionBuilder(
                            'postback label2',
                            'post=back2',
                            'extend text',
                            PostbackInputOption::OPEN_KEYBOARD
                        ),
                        new PostbackTemplateActionBuilder(
                            'postback label3',
                            'post=back3',
                            'extend text2',
                            PostbackInputOption::OPEN_KEYBOARD,
                            'fill in text'
                        ),
                    ]
                )
            )
        );

        $this->assertEquals(200, $res->getHTTPStatus());
        $this->assertTrue($res->isSucceeded());
        $this->assertEquals(200, $res->getJSONDecodedBody()['status']);

        // test case that AltUriBuilder is not set
        $res = $bot->replyMessage(
            'REPLY-TOKEN',
            new LINEBot\MessageBuilder\TemplateMessageBuilder(
                'alt test',
                new ButtonTemplateBuilder(
                    'button title',
                    'button button',
                    'https://example.com/thumbnail.jpg',
                    [
                        new PostbackTemplateActionBuilder('postback label', 'post=back'),
                        new MessageTemplateActionBuilder('message label', 'test message'),
                        new UriTemplateActionBuilder('uri label', 'https://example.com'),
                        new PostbackTemplateActionBuilder(
                            'postback label2',
                            'post=back2',
                            'extend text',
                            PostbackInputOption::OPEN_KEYBOARD
                        ),
                        new PostbackTemplateActionBuilder(
                            'postback label3',
                            'post=back3',
                            'extend text2',
                            PostbackInputOption::OPEN_KEYBOARD,
                            'fill in text'
                        ),
                    ]
                )
            )
        );

        $this->assertEquals(200, $res->getHTTPStatus());
        $this->assertTrue($res->isSucceeded());
        $this->assertEquals(200, $res->getJSONDecodedBody()['status']);
    }

    public function testPushTemplate()
    {
        $mock = function ($testRunner, $httpMethod, $url, $data) {
            /** @var \PHPUnit\Framework\TestCase $testRunner */
            $testRunner->assertEquals('POST', $httpMethod);
            $testRunner->assertEquals('https://api.line.me/v2/bot/message/push', $url);

            $testRunner->assertEquals('DESTINATION', $data['to']);
            $testRunner->assertEquals(1, count($data['messages']));

            $message = $data['messages'][0];
            $testRunner->assertEquals(MessageType::TEMPLATE, $message['type']);
            $testRunner->assertEquals('alt test', $message['altText']);

            $template = $message['template'];
            $testRunner->assertEquals(TemplateType::BUTTONS, $template['type']);
            $testRunner->assertEquals('button title', $template['title']);
            $testRunner->assertEquals('button button', $template['text']);
            $testRunner->assertEquals('https://example.com/thumbnail.jpg', $template['thumbnailImageUrl']);

            $actions = $template['actions'];
            $testRunner->assertEquals(6, count($actions));
            $testRunner->assertEquals(ActionType::POSTBACK, $actions[0]['type']);
            $testRunner->assertEquals('postback label', $actions[0]['label']);
            $testRunner->assertEquals('post=back', $actions[0]['data']);

            $testRunner->assertEquals(ActionType::POSTBACK, $actions[1]['type']);
            $testRunner->assertEquals('postback label2', $actions[1]['label']);
            $testRunner->assertEquals('post=back2', $actions[1]['data']);

            $testRunner->assertEquals(ActionType::MESSAGE, $actions[2]['type']);
            $testRunner->assertEquals('message label', $actions[2]['label']);
            $testRunner->assertEquals('test message', $actions[2]['text']);

            $testRunner->assertEquals(ActionType::URI, $actions[3]['type']);
            $testRunner->assertEquals('uri label', $actions[3]['label']);
            $testRunner->assertEquals('https://example.com', $actions[3]['uri']);
            if (isset($actions[3]['altUri'])) {
                $testRunner->assertEquals(
                    ['desktop' => 'http://example.com/pc/page/222'],
                    $actions[3]['altUri']
                );
            }

            $testRunner->assertEquals(ActionType::POSTBACK, $actions[4]['type']);
            $testRunner->assertEquals('postback label3', $actions[4]['label']);
            $testRunner->assertEquals('post=back3', $actions[4]['data']);
            $testRunner->assertEquals('extend text2', $actions[4]['displayText']);
            $testRunner->assertEquals('openKeyboard', $actions[4]['inputOption']);

            $testRunner->assertEquals(ActionType::POSTBACK, $actions[5]['type']);
            $testRunner->assertEquals('postback label4', $actions[5]['label']);
            $testRunner->assertEquals('post=back4', $actions[5]['data']);
            $testRunner->assertEquals('extend text3', $actions[5]['displayText']);
            $testRunner->assertEquals('openKeyboard', $actions[5]['inputOption']);
            $testRunner->assertEquals('fill in text', $actions[5]['fillInText']);

            $testRunner->assertEquals('rectangle', $template['imageAspectRatio']);
            $testRunner->assertEquals('cover', $template['imageSize']);
            $testRunner->assertEquals('#FFFFFF', $template['imageBackgroundColor']);

            return ['status' => 200];
        };
        $bot = new LINEBot(new DummyHttpClient($this, $mock), ['channelSecret' => 'CHANNEL-SECRET']);

        // test case that AltUriBuilder is set
        $res = $bot->pushMessage(
            'DESTINATION',
            new LINEBot\MessageBuilder\TemplateMessageBuilder(
                'alt test',
                new ButtonTemplateBuilder(
                    'button title',
                    'button button',
                    'https://example.com/thumbnail.jpg',
                    [
                        new PostbackTemplateActionBuilder('postback label', 'post=back'),
                        new PostbackTemplateActionBuilder('postback label2', 'post=back2', 'extend text'),
                        new MessageTemplateActionBuilder('message label', 'test message'),
                        new UriTemplateActionBuilder(
                            'uri label',
                            'https://example.com',
                            new AltUriBuilder('http://example.com/pc/page/222')
                        ),
                        new PostbackTemplateActionBuilder(
                            'postback label3',
                            'post=back3',
                            'extend text2',
                            PostbackInputOption::OPEN_KEYBOARD
                        ),
                        new PostbackTemplateActionBuilder(
                            'postback label4',
                            'post=back4',
                            'extend text3',
                            PostbackInputOption::OPEN_KEYBOARD,
                            'fill in text'
                        ),
                    ],
                    'rectangle',
                    'cover',
                    '#FFFFFF'
                )
            )
        );

        $this->assertEquals(200, $res->getHTTPStatus());
        $this->assertTrue($res->isSucceeded());
        $this->assertEquals(200, $res->getJSONDecodedBody()['status']);

        // test case that AltUriBuilder is not set
        $res = $bot->pushMessage(
            'DESTINATION',
            new LINEBot\MessageBuilder\TemplateMessageBuilder(
                'alt test',
                new ButtonTemplateBuilder(
                    'button title',
                    'button button',
                    'https://example.com/thumbnail.jpg',
                    [
                        new PostbackTemplateActionBuilder('postback label', 'post=back'),
                        new PostbackTemplateActionBuilder('postback label2', 'post=back2', 'extend text'),
                        new MessageTemplateActionBuilder('message label', 'test message'),
                        new UriTemplateActionBuilder('uri label', 'https://example.com'),

                        new PostbackTemplateActionBuilder(
                            'postback label3',
                            'post=back3',
                            'extend text2',
                            PostbackInputOption::OPEN_KEYBOARD
                        ),
                        new PostbackTemplateActionBuilder(
                            'postback label4',
                            'post=back4',
                            'extend text3',
                            PostbackInputOption::OPEN_KEYBOARD,
                            'fill in text'
                        ),
                    ],
                    'rectangle',
                    'cover',
                    '#FFFFFF'
                )
            )
        );

        $this->assertEquals(200, $res->getHTTPStatus());
        $this->assertTrue($res->isSucceeded());
        $this->assertEquals(200, $res->getJSONDecodedBody()['status']);
    }


    public function testImageCarouselTemplate()
    {
        $mock = function ($testRunner, $httpMethod, $url, $data) {
            /** @var \PHPUnit\Framework\TestCase $testRunner */
            $testRunner->assertEquals('POST', $httpMethod);
            $testRunner->assertEquals('https://api.line.me/v2/bot/message/push', $url);

            $testRunner->assertEquals('DESTINATION', $data['to']);
            $testRunner->assertEquals(1, count($data['messages']));

            $message = $data['messages'][0];
            $testRunner->assertEquals(MessageType::TEMPLATE, $message['type']);
            $testRunner->assertEquals('alt test', $message['altText']);

            $template = $message['template'];
            $testRunner->assertEquals(TemplateType::IMAGE_CAROUSEL, $template['type']);

            $columns = $template['columns'];
            $testRunner->assertEquals(4, count($columns));
            $testRunner->assertEquals('https://example.com/image1.png', $columns[0]['imageUrl']);
            $testRunner->assertEquals(ActionType::DATETIME_PICKER, $columns[0]['action']['type']);
            $testRunner->assertEquals('datetime picker date', $columns[0]['action']['label']);
            $testRunner->assertEquals('action=sell&itemid=2&mode=date', $columns[0]['action']['data']);
            $testRunner->assertEquals('date', $columns[0]['action']['mode']);
            $testRunner->assertEquals('2013-04-01', $columns[0]['action']['initial']);
            $testRunner->assertEquals('2011-06-23', $columns[0]['action']['max']);
            $testRunner->assertEquals('2017-09-08', $columns[0]['action']['min']);

            $testRunner->assertEquals('https://example.com/image2.png', $columns[1]['imageUrl']);
            $testRunner->assertEquals(ActionType::DATETIME_PICKER, $columns[1]['action']['type']);
            $testRunner->assertEquals('datetime picker time', $columns[1]['action']['label']);
            $testRunner->assertEquals('action=sell&itemid=2&mode=time', $columns[1]['action']['data']);
            $testRunner->assertEquals('time', $columns[1]['action']['mode']);
            $testRunner->assertEquals('10:00', $columns[1]['action']['initial']);
            $testRunner->assertEquals('00:00', $columns[1]['action']['max']);
            $testRunner->assertEquals('23:59', $columns[1]['action']['min']);

            $testRunner->assertEquals('https://example.com/image3.png', $columns[2]['imageUrl']);
            $testRunner->assertEquals(ActionType::DATETIME_PICKER, $columns[2]['action']['type']);
            $testRunner->assertEquals('datetime picker date', $columns[2]['action']['label']);
            $testRunner->assertEquals('action=sell&itemid=2&mode=date', $columns[2]['action']['data']);
            $testRunner->assertEquals('date', $columns[2]['action']['mode']);

            $testRunner->assertEquals('https://example.com/image4.png', $columns[3]['imageUrl']);
            $testRunner->assertEquals(ActionType::DATETIME_PICKER, $columns[3]['action']['type']);
            $testRunner->assertEquals('datetime picker time', $columns[3]['action']['label']);
            $testRunner->assertEquals('action=sell&itemid=2&mode=time', $columns[3]['action']['data']);
            $testRunner->assertEquals('time', $columns[3]['action']['mode']);

            return ['status' => 200];
        };
        $bot = new LINEBot(new DummyHttpClient($this, $mock), ['channelSecret' => 'CHANNEL-SECRET']);
        $res = $bot->pushMessage(
            'DESTINATION',
            new LINEBot\MessageBuilder\TemplateMessageBuilder(
                'alt test',
                new ImageCarouselTemplateBuilder(
                    [
                        new ImageCarouselColumnTemplateBuilder(
                            'https://example.com/image1.png',
                            new DatetimePickerTemplateActionBuilder(
                                'datetime picker date',
                                'action=sell&itemid=2&mode=date',
                                'date',
                                '2013-04-01',
                                '2011-06-23',
                                '2017-09-08'
                            )
                        ),
                        new ImageCarouselColumnTemplateBuilder(
                            'https://example.com/image2.png',
                            new DatetimePickerTemplateActionBuilder(
                                'datetime picker time',
                                'action=sell&itemid=2&mode=time',
                                'time',
                                '10:00',
                                '00:00',
                                '23:59'
                            )
                        ),
                        new ImageCarouselColumnTemplateBuilder(
                            'https://example.com/image3.png',
                            new DatetimePickerTemplateActionBuilder(
                                'datetime picker date',
                                'action=sell&itemid=2&mode=date',
                                'date'
                            )
                        ),
                        new ImageCarouselColumnTemplateBuilder(
                            'https://example.com/image4.png',
                            new DatetimePickerTemplateActionBuilder(
                                'datetime picker time',
                                'action=sell&itemid=2&mode=time',
                                'time'
                            )
                        ),
                    ]
                )
            )
        );

        $this->assertEquals(200, $res->getHTTPStatus());
        $this->assertTrue($res->isSucceeded());
        $this->assertEquals(200, $res->getJSONDecodedBody()['status']);
    }
}
