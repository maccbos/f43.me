<?php

namespace Api43\FeedBundle\Readability;

use Readability\Readability;

/**
 * This class extends the Readability one to add more fine tuning on content:
 *     - remove some unwanted attributes
 *     - convert relative path to absolute.
 */
class ReadabilityExtended extends Readability
{
    /**
     * Get article content as html.
     *
     * @return string
     */
    public function getHtmlContent()
    {
        $innerHTML = '';
        $children = $this->getContent()->childNodes;
        foreach ($children as $child) {
            $innerHTML .= $child->ownerDocument->saveXML($child);
        }

        return $innerHTML;
    }

    /**
     * Prepare the article node for display. Clean out any inline styles,
     * iframes, forms, strip extraneous <p> tags, etc.
     *
     * @see parent
     *
     * @param DOMElement $articleContent
     */
    public function prepArticle($articleContent)
    {
        $this->cleanTags($articleContent);
        $this->cleanAttrs($articleContent);
        $this->makeImgSrcAbsolute($articleContent);
        $this->makeHrefAbsolute($articleContent);
        $this->convertH1ToH2($articleContent);

        parent::prepArticle($articleContent);
    }

    /**
     * Remove some attributes on every $e and under.
     *
     * @param DOMElement $e
     */
    public function cleanAttrs($e)
    {
        if (!is_object($e)) {
            return;
        }

        $attrs = explode('|', $this->regexps['attrToRemove']);

        $elems = $e->getElementsByTagName('*');
        foreach ($elems as $elem) {
            foreach ($attrs as $attr) {
                $elem->removeAttribute($attr);
            }
        }
    }

    /**
     * Remove some "bad" tags on every $e and under.
     *
     * @param DOMElement $e
     */
    public function cleanTags($e)
    {
        if (!is_object($e)) {
            return;
        }

        $tags = explode('|', $this->regexps['tagToRemove']);

        foreach ($tags as $tag) {
            $this->clean($e, $tag);
        }
    }

    /**
     * Convert relative image path to absolute.
     *
     * @param DOMElement $e
     */
    public function makeImgSrcAbsolute($e)
    {
        if (!is_object($e)) {
            return;
        }

        $elems = $e->getElementsByTagName('img');
        foreach ($elems as $elem) {
            // hu oh img node without src, remove it.
            if (!$elem->hasAttribute('src')) {
                $elem->parentNode->removeChild($elem);
                continue;
            }

            $src = $elem->getAttribute('src');

            // handle image src that are converted by javascript (lazy load)
            if ($elem->hasAttribute('originalsrc')) {
                $src = $elem->getAttribute('originalsrc');
                $elem->removeAttribute('originalsrc');
                $elem->setAttribute('src', $src);
            }
            if ($elem->hasAttribute('data-src')) {
                $src = $elem->getAttribute('data-src');
                $elem->removeAttribute('data-src');
                $elem->setAttribute('src', $src);
            }

            if (preg_match('/^http(s?):\/\//i', $src)) {
                continue;
            }

            $src = \SimplePie_IRI::absolutize($this->url, $src);

            $elem->setAttribute('src', $src);
        }
    }

    /**
     * Convert relative url absolute.
     *
     * @param DOMElement $e
     */
    public function makeHrefAbsolute($e)
    {
        if (!is_object($e)) {
            return;
        }

        $elems = $e->getElementsByTagName('a');
        foreach ($elems as $elem) {
            $href = $elem->getAttribute('href');

            if (preg_match('/^http(s?):\/\//i', $href)) {
                continue;
            }

            $href = \SimplePie_IRI::absolutize($this->url, $href);

            $elem->setAttribute('href', $href);
        }
    }

    /**
     * Convert h1 tag to h2.
     * Since Readability removes h1.
     *
     * @param DOMElement $e
     */
    public function convertH1ToH2($e)
    {
        if (!is_object($e)) {
            return;
        }

        if ($e->getElementsByTagName('h1')->length == 1) {
            return;
        }

        while (null !== ($elem = $e->getElementsByTagName('h1')->item(0))) {
            $newNode = $elem->ownerDocument->createElement('h2');

            if ($elem->attributes->length) {
                foreach ($elem->attributes as $attribute) {
                    $newNode->setAttribute($attribute->nodeName, $attribute->nodeValue);
                }
            }

            while ($elem->firstChild) {
                $newNode->appendChild($elem->firstChild);
            }

            $elem->parentNode->replaceChild($newNode, $elem);
        }
    }
}