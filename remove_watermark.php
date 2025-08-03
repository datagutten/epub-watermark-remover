<?php

use datagutten\EpubWatermarkRemover\EPUB;
use datagutten\EpubWatermarkRemover\RewriteOPF;

require_once __DIR__ . '/vendor/autoload.php';

if(!isset($remove_epub_watermark))
{
	require 'class_remove_epub_watermark.php';
	$remove_epub_watermark=new remove_epub_watermark($argv[1]);
}

$dom=new DOMDocument;
$dom->formatOutput=true;

foreach($remove_epub_watermark->loadfiles() as $file)
{
	echo $file."\n";	
	//$file=basename($argv[1]);
	
	$input_string=file_get_contents($file);
	$head=substr($input_string,0,strpos($input_string,'<head>'));
	if(strpos($input_string,'<?xml version="1.0" encoding="utf-8"?>')===false)
		$input_string='<?xml version="1.0" encoding="utf-8"?>'."\n".$input_string;
	$dom->loadHTML($input_string);
	
	foreach($dom->getElementsByTagName('meta') as $meta_tag) //Loop through meta tags
	{
		foreach($meta_tag->attributes as $attribute)
		{
			//print_r($attribute);
			if($attribute->value=='DC.identifier' || $attribute->value=='DC.rights' || $attribute->value=='Adept.expected.resource') //Add identifying attributes to queue for removal
				$remove[]=$meta_tag;
		}
	}

	$remove_epub_watermark->remove_elements($remove);
	unset($remove);

	$output_string=$dom->saveXML($dom);
	$output_string=$head.substr($output_string,strpos($output_string,'<head>'));
	
	$outfile=str_replace($remove_epub_watermark->dir_input,$remove_epub_watermark->dir_output,$file);
	file_put_contents($outfile,trim($output_string));
}
//die();
//unset($dom);
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
