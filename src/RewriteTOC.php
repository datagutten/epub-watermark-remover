<?php

namespace datagutten\EpubWatermarkRemover;

use datagutten\tools\files\files;
use DOMDocument;
use DOMXPath;
use FileNotFoundException;

class RewriteTOC
{
    public DOMDocument $dom;
    private DOMXPath $xpath;
    private string $output_folder;

    function __construct(string $toc_file, string $output_folder)
    {
        $this->dom = new DOMDocument;
        $this->dom->formatOutput = true;
        //$this->dom->preserveWhiteSpace = false;
        $this->dom->load($toc_file);
        $this->xpath = new DOMXPath($this->dom);
        $this->xpath->registerNamespace('ncx', 'http://www.daisy.org/z3986/2005/ncx/');
        $this->output_folder = $output_folder;
    }

    public function rewriteNavMap(): void
    {
        $files = [];
        $playOrder = 0;
        $navMap = $this->xpath->query('//ncx:navMap')->item(0);
        foreach ($this->xpath->query('.//ncx:navPoint', $navMap) as $key => $navPoint)
        //foreach ($this->dom->getElementsByTagName('navPoint') as $key=>$navPoint)
        {
            $id = $navPoint->getAttribute('id');
            if ($id == 'rights')
            {
                $navMap->removeChild($navPoint);
                continue;
            }
            $content = $this->xpath->query('.//ncx:content', $navPoint)->item(0);
            $src = $content->getAttribute('src');
            try
            {
                $files[$key] = EPUB::link_to_file($src, $this->content_folder);
            } catch (FileNotFoundException)
            {
                printf("Remove navPoint for removed file \"%s\"\n", $src);
                $navMap->removeChild($navPoint);
                continue;
            }

            if (preg_match('/navpoint(\d+)/', $id, $matches))
                $playOrder = intval($matches[1]);
            else
                $playOrder++;

            /*                if ($key > 1 && $files[$key] == $files[$key - 1]) //If the content is the same as the previous element do not increment the playOrder
                                $playOrder--;*/

            $navPoint->setAttribute('playOrder', $playOrder); //Set new playOrder
            //$navPoint->setAttribute('check', 'check');
            //$playOrder++;
        }
    }
}