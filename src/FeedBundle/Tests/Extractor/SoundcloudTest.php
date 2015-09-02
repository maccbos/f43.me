<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\Soundcloud;
use GuzzleHttp\Exception\RequestException;
use Monolog\Logger;
use Monolog\Handler\TestHandler;

class SoundcloudTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('https://soundcloud.com/birdfeeder/jurassic-park-theme-1000-slower', true),
            array('http://soundcloud.com/birdfeeder/jurassic-park-theme-1000-slower#t=0:02', true),
            array('https://soundcloud.com/birdfeeder', true),
            array('https://goog.co', false),
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $soundCloud = new Soundcloud();
        $soundCloud->setGuzzle($guzzle);
        $this->assertEquals($expected, $soundCloud->match($url));
    }

    public function testContent()
    {
        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('GuzzleHttp\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('GuzzleHttp\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('json')
            ->will($this->onConsecutiveCalls(
                $this->returnValue(array('title' => 'my title', 'description' => 'my description', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'html' => '<iframe/>')),
                $this->returnValue(''),
                $this->throwException(new RequestException('oops', $request))
            ));

        $soundCloud = new Soundcloud();
        $soundCloud->setGuzzle($guzzle);

        $logHandler = new TestHandler();
        $logger = new Logger('test', array($logHandler));
        $soundCloud->setLogger($logger);

        // first test fail because we didn't match an url, so SoundcloudUrl isn't defined
        $this->assertEmpty($soundCloud->getContent());

        $soundCloud->match('https://soundcloud.com/birdfeeder/jurassic-park-theme-1000-slower');

        // consecutive calls
        $this->assertEquals('<div><h2>my title</h2><p>my description</p><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $soundCloud->getContent());
        // this one will got an empty array
        $this->assertEmpty($soundCloud->getContent());
        // this one will catch an exception
        $this->assertEmpty($soundCloud->getContent());

        $this->assertTrue($logHandler->hasWarning('Soundcloud extract failed for: https://soundcloud.com/birdfeeder/jurassic-park-theme-1000-slower'), 'Warning message matched');
    }
}
