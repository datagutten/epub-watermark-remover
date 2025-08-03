<?Php

use datagutten\EpubWatermarkRemover\EPUB;
use datagutten\EpubWatermarkRemover\RewriteTOC;
use datagutten\tools\files\files;

if(!isset($remove_epub_watermark))
{
	require 'class_remove_epub_watermark.php';
	$remove_epub_watermark=new remove_epub_watermark($argv[1]);
	$remove_epub_watermark->loadfiles();
}

$toc = new RewriteTOC($remove_epub_watermark->toc, $remove_epub_watermark->dir_output);
$dom = $toc->dom;

$meta_tags=$dom->getElementsByTagName('meta');

//Set uid to ISBN
if($meta_tags->item(0)->getAttribute('name')=='dtb:uid')
	$meta_tags->item(0)->setAttribute('content',$remove_epub_watermark->isbn);
else
	echo "UID not found\n";

$toc->rewriteNavMap();
$outfile=str_replace($remove_epub_watermark->dir_input,$remove_epub_watermark->dir_output,$remove_epub_watermark->toc);
$toc->dom->save($outfile);

EPUB::check_links(files::path_join($remove_epub_watermark->dir_output, 'OEBPS', 'toc.xhtml'));