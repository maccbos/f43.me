<?php

namespace Tests\AppBundle\Extractor;

use AppBundle\Extractor\Deviantart;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Tests\AppBundle\AppTestCase;

class DeviantartTest extends AppTestCase
{
    public function dataMatch()
    {
        return [
            ['http://mibreit.deviantart.com/art/A-Piece-of-Heaven-357105002', true],
            ['http://lndi.deviantart.com/art/Maybe-we-ll-get-luck-and-we-ll-both-live-again-494273522', true],
            ['http://fav.me/d7gab7p', true],
            ['http://sta.sh/06x5m3s9bms', true],

            ['http://www.deviantart.com/browse/all/', false],
            ['http://nixielupus.deviantart.com/', false],
            ['http://sta.sh/', false],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $deviantart = new Deviantart();
        $this->assertSame($expected, $deviantart->match($url));
    }

    public function testContent()
    {
        $client = self::getMockClient([(new Response(200, [], (string) json_encode([
            'url' => 'http://0.0.0.0/youpi.jpg',
            'title' => 'youpi',
            'author_url' => 'http://youpi.0.0.0.0',
            'author_name' => 'youpi',
            'category' => 'Pic > Landscape',
            'html' => '<iframe></iframe>',
        ])))]);

        $deviantart = new Deviantart();
        $deviantart->setClient($client);

        // first test fail because we didn't match an url, so DeviantartId isn't defined
        $this->assertEmpty($deviantart->getContent());

        $deviantart->match('http://mibreit.deviantart.com/art/A-Piece-of-Heaven-357105002');

        $content = $deviantart->getContent();

        $this->assertContains('<img src="http://0.0.0.0/youpi.jpg" />', $content);
        $this->assertContains('<p>By <a href="http://youpi.0.0.0.0">@youpi</a></p>', $content);
        $this->assertContains('<iframe></iframe>', $content);
    }

    public function testContentWithException()
    {
        $client = self::getMockClient([(new Response(400, [], (string) json_encode('oops')))]);

        $deviantart = new Deviantart();
        $deviantart->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $deviantart->setLogger($logger);

        $deviantart->match('http://mibreit.deviantart.com/art/A-Piece-of-Heaven-357105002');

        // this one will catch an exception
        $this->assertEmpty($deviantart->getContent());

        $this->assertTrue($logHandler->hasWarning('Deviantart extract failed for: http://mibreit.deviantart.com/art/A-Piece-of-Heaven-357105002'), 'Warning message matched');
    }
}
