<?php

namespace datagutten\EpubWatermarkRemover;

use datagutten\epub\OPF;
use datagutten\tools\files\files;

class RewriteOPF extends OPF
{
    public string $isbn;

    function __construct($file, $epub_root)
    {
        parent::__construct($file, $epub_root);
        $this->dom->formatOutput = true;
        $this->isbn = $this->findISBN();
        if (dirname($file) == str_replace('_cleaned', '', $epub_root))
            $this->content_folder = $epub_root;
        else
            $this->content_folder = files::path_join($this->output_folder, basename(dirname($file)));
    }

    /**
     * Remove all identifiers and add normalized ISBN
     * @return string ISBN
     */
    function strip_identifiers(): string
    {
        $metadata = $this->xpath->query('/opf:package/opf:metadata')->item(0);
        $identifiers = $this->xpath->query('//dc:identifier');

        foreach ($identifiers as $identifier)
        {
            $id = $identifier->getAttribute('id');
            $metadata->removeChild($identifier);
            printf("Remove identifier %s with value \"%s\"\n", $id, $identifier->nodeValue);

        }
        $identifier = $this->dom->createElement('dc:identifier', $this->isbn);
        $identifier->setAttribute('id', 'ISBN');
        $identifier->setAttribute('opf:scheme', 'ISBN');
        $metadata->appendChild($identifier);

        return $this->isbn;
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

    public function rewrite($output_file): string
    {
        //Remove all other ids than ISBN and save it for use in TOC
        $this->strip_identifiers();
        $this->strip_rights();
        //Files that have not been copied into output directory by previous steps should be removed from the opf
        $this->strip_missing();
        $this->setPrimaryKey('ISBN');

        $this->dom->save($output_file);
        return $output_file;
    }
}