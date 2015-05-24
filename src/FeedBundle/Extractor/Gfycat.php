<?php

namespace Api43\FeedBundle\Extractor;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\RequestException;

class Gfycat extends AbstractExtractor
{
    protected $guzzle;
    protected $gfycatId = null;

    /**
     * @param Client $guzzle
     */
    public function __construct(Client $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    /**
     * {@inheritdoc}
     */
    public function match($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);

        if (false === $host || false === $path) {
            return false;
        }

        if (0 !== strpos($host, 'gfycat.com')) {
            return false;
        }

        // match gfycat id
        preg_match('/([a-zA-Z]+)/i', $path, $matches);

        if (!isset($matches[1])) {
            return false;
        }

        $this->gfycatId = $matches[1];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->gfycatId) {
            return '';
        }

        try {
            $data = $this->guzzle
                ->get('http://gfycat.com/cajax/get/'.$this->gfycatId)
                ->send()
                ->json();
        } catch (RequestException $e) {
            trigger_error('Gfycat extract failed for "'.$this->gfycatId.'": '.$e->getMessage());

            return '';
        }

        if (!is_array($data) || empty($data) || !isset($data['gifUrl'])) {
            return '';
        }

        return '<div><p><img src="'.$data['gifUrl'].'"></p></div>';
    }
}