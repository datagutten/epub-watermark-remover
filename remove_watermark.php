<?php

use datagutten\EpubWatermarkRemover\EPUB;
use datagutten\EpubWatermarkRemover\Page;
use datagutten\EpubWatermarkRemover\RewriteOPF;

require_once __DIR__ . '/vendor/autoload.php';
$remove_epub_watermark=new remove_epub_watermark($argv[1]);

$dom=new DOMDocument;
$dom->formatOutput=true;

foreach($remove_epub_watermark->loadfiles() as $file)
{
    $page = new Page($file);
    $dom = $page->dom;
    $page->strip_meta();
    $output_string = $page->strip_strings($remove_epub_watermark->config['bad_string_regex']);

	$outfile=str_replace($remove_epub_watermark->dir_input,$remove_epub_watermark->dir_output,$file);
	file_put_contents($outfile,trim($output_string));
}

$opf = new RewriteOPF($remove_epub_watermark->opf, $remove_epub_watermark->dir_output);
$opf->rewrite();
$remove_epub_watermark->isbn = $opf->findISBN();

echo "Rewrite TOC\n";
require 'rewrite_toc.php';

$output_epub=$remove_epub_watermark->dir_output.'.epub';
echo "Creating new epub\n";

$epub_file = EPUB::build_epub($remove_epub_watermark->dir_output);
if(!file_exists($epub_file))
	echo "epub creation failed\n";
else
	echo sprintf("%s.epub was successfully created\n",$remove_epub_watermark->dir_output);
