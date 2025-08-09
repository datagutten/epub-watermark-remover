<?php

namespace datagutten\EpubWatermarkRemover;

use DOMDocument;
use FileNotFoundException;

class Page
{
    public DOMDocument $dom;
    public string $file;
    public bool $changed = false;

    function __construct(string $page_file)
    {
        if (!file_exists($page_file))
            throw new FileNotFoundException($page_file);
        $this->dom = new DOMDocument();
        $this->dom->formatOutput = true;
        //$dom->preserveWhiteSpace = false;
        $this->dom->load($page_file);
        $this->file = $page_file;
    }

    public function strip_meta(): void
    {
        $bad_meta = ['DC.identifier', 'DC.rights', 'Adept.expected.resource'];
        $meta = $this->dom->getElementsByTagName('meta');
        if ($meta->length == 0)
        {
            printf("No meta tags in %s\n", $this->file);
            return;
        }

        foreach ($meta as $meta_tag) //Loop through meta tags
        {
            foreach ($meta_tag->attributes as $attribute)
            {
                if (in_array($attribute->value, $bad_meta))
                {
                    printf("Removing meta tag with value %s from %s\n", $attribute->value, $this->file);
                    $meta_tag->parentNode->removeChild($meta_tag);
                }
            }
        }
        $this->changed = true;
    }

    public function strip_strings(array $bad_string_regex): string
    {
        $count = 0;
        $count_temp = 0;
        $page_string = $this->dom->saveXML($this->dom);
        foreach ($bad_string_regex as $regex)
        {
            $page_string = preg_replace($regex, '', $page_string, count: $count_temp);
            $count += $count_temp;
        }

        if ($count > 0)
            printf("Removed %d bad strings from %s\n", $count, $this->file);
        return $page_string;
    }
}