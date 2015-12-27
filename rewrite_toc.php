<?Php
if(!isset($remove_epub_watermark))
{
	require 'class_remove_epub_watermark.php';
	$remove_epub_watermark=new remove_epub_watermark($argv[1]);
}
//$dom=new DOMDocument;
//$dom->formatOutput=true;

$input_string=file_get_contents($remove_epub_watermark->toc); //Load toc.ncx
$head=substr($input_string,0,strpos($input_string,'<head>'));

$dom->loadXML($input_string);

$meta_tags=$dom->getElementsByTagName('meta');

//Set uid to ISBN
if($meta_tags->item(0)->getAttribute('name')=='dtb:uid')
	$meta_tags->item(0)->setAttribute('content',$remove_epub_watermark->isbn);
else
	echo "UID not found\n";

$playOrder=1;
foreach($dom->getElementsByTagName('navPoint') as $key=>$navPoint) //Rewrite play order
{
	//$text=$navPoint->getElementsByTagName('text')->item(0)->nodeValue;
	$id=$navPoint->attributes->item(0)->value;
	if($id=='rights') //Remove rights page
	{
		$remove[]=$navPoint;
		continue;
	}
	/*foreach($navPoint->attributes as $attribute)
	{
		//print_r($attribute);
		
	}*/
	
	$content_src[$key]=$navPoint->getElementsByTagName('content')->item(0)->attributes->item(0)->value;

	if($key>1 && $content_src[$key]==$content_src[$key-1]) //If the content is the same as the previous element do not increment the playOrder
		$playOrder--;

	$navPoint->setAttribute('playOrder',$playOrder); //Set new playOrder

	$playOrder++;
}

if(isset($remove))
	$remove_epub_watermark->remove_elements($remove);
else
	echo "No elements removed from TOC\n";
unset($remove);
$output_string=$dom->saveXML($dom);
$output_string=$head.substr($output_string,strpos($output_string,'<head>')); //Replace header

$outfile=str_replace($remove_epub_watermark->dir_input,$remove_epub_watermark->dir_output,$remove_epub_watermark->toc);
file_put_contents($outfile,trim($output_string));

unset($dom);
?>