<?php

namespace datagutten\EpubWatermarkRemover;

use datagutten\tools\files\files;
use DOMDocument;
use Symfony\Component\Process\Process;

class EPUB extends \datagutten\epub\EPUB
{
    public static function link_to_file(string $link, string $book_path): string
    {
        return files::path_join($book_path, 'OEBPS', preg_replace('/(.+\.\w+)(?:#.+)?/', '$1', $link));
    }

    public static function check_links(string $page_file)
    {
        $dom = new DOMDocument();
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;
        $dom->load($page_file);
        $folder = dirname(dirname($page_file));
        /** @var \DOMElement $link */
        foreach ($dom->getElementsByTagName('a') as $link) {
            $href = $link->getAttribute('href');
            $file = self::link_to_file($href, $folder);
            if(!file_exists($file)) {
                if($link->parentNode->childNodes->length==1)
                    $link->parentNode->parentNode->removeChild($link->parentNode);
                else
                    $link->parentNode->removeChild($link);
            }
        }
        $dom->save($page_file);
    }
}