<?php

namespace datagutten\EpubWatermarkRemover;

use DOMDocument;
use DOMXPath;
use RuntimeException;

class RewriteOPF
{
    public DOMDocument $dom;
    private DOMXPath $xpath;

    function __construct($opf_file)
    {
        $this->dom = new DOMDocument;
        $this->dom->formatOutput = true;
        $this->dom->load($opf_file);
        $this->xpath = new DOMXPath($this->dom);
        $this->xpath->registerNameSpace('dc', 'http://purl.org/dc/elements/1.1/');
        $this->xpath->registerNameSpace('opf', 'http://www.idpf.org/2007/opf"');
    }

    /**
     * Remove unknown identifiers and normalize ISBN
     * @return string ISBN
     */
    function strip_identifiers(): string
    {
        $identifiers = $this->xpath->query('//dc:identifier');
        foreach ($identifiers as $identifier)
        {
            $id = $identifier->getAttribute('id');
            if (stripos($id, 'isbn') !== false || stripos($identifier->nodeValue, 'isbn') !== false)
            {
                $identifier->setAttribute('id', 'ISBN');
                $identifier->nodeValue = preg_replace('/\D*(\d+)/', '$1', $identifier->nodeValue);
                $isbn = $identifier->nodeValue; //Get ISBN and save it for use in TOC
            }
            else
            {
                $identifier->parentNode->removeChild($identifier);
                printf("Remove identifier %s with value \"%s\"\n", $id, $identifier->nodeValue);
            }
        }
        if (empty($isbn))
            throw new RuntimeException('ISBN not found in OPF');

        return $isbn;
    }

    function strip_rights(): void
    {
        $tags = $this->xpath->query('//dc:rights');
        foreach ($tags as $tag)
        {
            $tag->parentNode->removeChild($tag);
            printf("Remove dc:rights with value \"%s\"\n", $tag->nodeValue);
        }
    }
}