<?php
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
echo "Rewrite OPF\n";
require 'rewrite_opf.php';
echo "Rewrite TOC\n";
require 'rewrite_toc.php';

$output_epub=$remove_epub_watermark->dir_output.'.epub';
echo "Creating new epub\n";
if(PHP_OS=='WINNT')
	shell_exec($cmd=sprintf('cd "%2$s" && %1$s\\7-zip\\7za.exe a -r -tzip "%2$s.epub" 2>&1',dirname(__FILE__),str_replace('/','\\',$remove_epub_watermark->dir_output)));
else
	shell_exec($cmd=sprintf('cd "%1$s" && zip -r "%1$s.epub" * 2>&1',$remove_epub_watermark->dir_output));
if(!file_exists($remove_epub_watermark->dir_output.'.epub'))
	echo "epub creation failed\n";
else
	echo sprintf("%s.epub was successfully created\n",$remove_epub_watermark->dir_output);
