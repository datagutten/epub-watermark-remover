<?Php

use datagutten\EpubWatermarkRemover\RewriteOPF;

require_once __DIR__ . '/vendor/autoload.php';
if(!isset($remove_epub_watermark))
{
	require 'class_remove_epub_watermark.php';
	$remove_epub_watermark=new remove_epub_watermark($argv[1]);
	$remove_epub_watermark->loadfiles();
}
$opf = new RewriteOPF($remove_epub_watermark->opf);
$dom = $opf->dom;

//$input_string=file_get_contents($remove_epub_watermark->opf); //Load opf file
//$input_string = preg_replace("/>\s+</", "><", $input_string); //Remove whitespace between tags
$data_dir=str_replace($remove_epub_watermark->dir_input,$remove_epub_watermark->dir_output,dirname($remove_epub_watermark->opf)); //Get the data directory on the outpath (used for checking if files exist)

//Remove all other ids than ISBN and save it for use in TOC
$remove_epub_watermark->isbn = $opf->strip_identifiers();
$opf->strip_rights();

//Files that have not been copied into output directory by previous steps should be removed from the opf
foreach($dom->getElementsByTagName('item') as $item) //Remove items for non-existing files
{
	$file=$item->attributes->getNamedItem('href')->value;
	if(!file_exists($data_dir.'/'.$file) && substr($file,-3,3)!='ncx')
	{
		$remove[]=$item;
		echo "Missing $file, removing from opf\n";
	}
	else
	{
		$id=$item->attributes->getNamedItem('id')->value;
		$valid_files[$id]=$file; //Save existing ids
	}
}
foreach($dom->getElementsByTagName('itemref') as $itemref) //Remove itemref for non-existing items
{
	$id=$itemref->attributes->getNamedItem('idref')->value;
	if(!isset($valid_files[$id]))
		$remove[]=$itemref;
}
foreach($dom->getElementsByTagName('reference') as $reference)
{
	$href=$reference->attributes->getNamedItem('href')->value;
	if(array_search($href,$valid_files)===false)
		$remove[]=$reference;
}

if(isset($remove))
	$remove_epub_watermark->remove_elements($remove);
else
	echo "No elements removed from OPF\n";
unset($remove);
$output_string=$dom->saveXML();
$output_string=preg_replace('/(\<package.+unique-identifier=").+?(".+)/','$1ISBN$2',$output_string);

$outfile=str_replace($remove_epub_watermark->dir_input,$remove_epub_watermark->dir_output,$remove_epub_watermark->opf);
file_put_contents($outfile,trim($output_string));
