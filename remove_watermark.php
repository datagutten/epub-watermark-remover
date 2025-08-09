<?php

use datagutten\EpubWatermarkRemover\EPUB;
use datagutten\EpubWatermarkRemover\Page;
use datagutten\EpubWatermarkRemover\RewriteOPF;
use datagutten\EpubWatermarkRemover\RewriteTOC;
use datagutten\tools\files\files;

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
$toc = new RewriteTOC($remove_epub_watermark->toc, $opf->content_folder);
$toc->setUID($opf->isbn);

$toc->rewriteNavMap();
$outfile=str_replace($remove_epub_watermark->dir_input,$remove_epub_watermark->dir_output,$remove_epub_watermark->toc);
$toc->dom->save($outfile);

try
{
    $remove_epub_watermark->epub->check_links(files::path_join($remove_epub_watermark->dir_output, 'OEBPS', 'toc.xhtml'));
} catch (FileNotFoundException $e)
{
}

$output_epub=$remove_epub_watermark->dir_output.'.epub';
echo "Creating new epub\n";

$epub_file = EPUBUtils::buildEPUB($remove_epub_watermark->dir_output);
if(!file_exists($epub_file))
	echo "epub creation failed\n";
else
{
	$remove_epub_watermark->delete_dir($remove_epub_watermark->dir_output);
	if(is_file($argv[1]))
	    $remove_epub_watermark->delete_dir($remove_epub_watermark->dir_input);
	echo sprintf("%s.epub was successfully created\n",$remove_epub_watermark->dir_output);
}
