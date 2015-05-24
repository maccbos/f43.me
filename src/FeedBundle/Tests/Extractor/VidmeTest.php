<?php

namespace Api43\FeedBundle\Tests\Extractor;

use Api43\FeedBundle\Extractor\Vidme;
use Guzzle\Http\Exception\RequestException;

class VidmeTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('https://vid.me/WaJr', true),
            array('http://vid.me/e/WaJr', true),
            array('https://vid.me', false),
            array('https://goog.co', false),
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $guzzle = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('Guzzle\Http\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($request));

        $request->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $vidme = new Vidme($guzzle);
        $this->assertEquals($expected, $vidme->match($url));
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testContent()
    {
        $guzzle = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder('Guzzle\Http\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($request));

        $request->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $response->expects($this->any())
            ->method('json')
            ->will($this->onConsecutiveCalls(
                $this->returnValue(array('video' => array('title' => 'my title', 'thumbnail_url' => 'http://0.0.0.0/img.jpg', 'embed_url' => 'http://0.0.0.0/embed'))),
                $this->returnValue(''),
                $this->throwException(new RequestException())
            ));

        $vidme = new Vidme($guzzle);

        // first test fail because we didn't match an url, so VidmeId isn't defined
        $this->assertEmpty($vidme->getContent());

        $vidme->match('https://vid.me/WaJr');

        // consecutive calls
        $this->assertEquals('<div><h2>my title</h2><p><img src="http://0.0.0.0/img.jpg"></p><iframe src="http://0.0.0.0/embed"></iframe></div>', $vidme->getContent());
        // this one will got an empty array
        $this->assertEmpty($vidme->getContent());
        // this one will catch an exception
        $this->assertEmpty($vidme->getContent());
    }
}