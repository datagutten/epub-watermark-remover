<?Php
class remove_epub_watermark
{
	public $dir_input;
	public $dir_output;
	public $isbn;
	public $opf;
	public $toc;
	public $safe_files=array('cover.jpg','gyldendallogo.gif','mimetype','license','template.xpgt');
	public $safe_extensions=array('otf','xml','css','js');

	function __construct($file_or_dir)
	{
		if(!file_exists($file_or_dir))
			die("$file_or_dir does not exist\n");
		if(is_file($file_or_dir))
		{
			if(pathinfo($file_or_dir,PATHINFO_EXTENSION)=='epub')
			{
				$this->dir_input=substr($file_or_dir,0,-5);
				if(!file_exists($this->dir_input))
					shell_exec("unzip \"$file_or_dir\" -d \"{$this->dir_input}\" 2>&1");
			}
			else
				die("Invalid file: $file_or_dir\n");
				//$this->dir_input=dirname($file_or_dir);
		}
		else
			$this->dir_input=$file_or_dir;

		$this->dir_output=$this->dir_input.'/../'.basename($this->dir_input).'_cleaned'; //Output dir should be on the same level as input
		
		if(!file_exists($this->dir_output))
			mkdir($this->dir_output);
	}
	function remove_elements($domElemsToRemove)
	{
		//http://php.net/manual/en/domnode.removechild.php#90292
		foreach($domElemsToRemove as $domElement)
		{ 
			if(!is_object($domElement->parentNode))
				print_r($domElement);
			$domElement->parentNode->removeChild($domElement); 
		}
	}
	function loadfiles($dir_from=false,$dir_to=false)
	{
		if($dir_from===false)
			$dir_from=$this->dir_input;
		if($dir_to===false)
			$dir_to=$this->dir_output;
		foreach(scandir($dir_from) as $file)
		{
			$extension=pathinfo($file,PATHINFO_EXTENSION);
			//echo $file."\n";
			if($extension=='opf')
				$this->opf=$dir_from.'/'.$file; //Save opf filename and path
			elseif($file=='toc.ncx')
				$this->toc=$dir_from.'/'.$file;
			elseif($extension=='xhtml' && $file!='rights.xhtml') //Add file to page list
				$pages[]=$dir_from.'/'.$file;
			elseif(is_dir($dir_from.'/'.$file) && $file!='.' && $file!='..') //Recurse into subdir
			{
				if(!file_exists($dir_to.'/'.$file))
					mkdir($dir_to.'/'.$file);
				$pages_temp=$this->loadfiles($dir_from.'/'.$file,$dir_to.'/'.$file);
				if(!empty($pages) && !empty($pages_temp))
					$pages=array_merge($pages,$pages_temp);
				elseif(!isset($pages))
					$pages=$pages_temp;
			}
			elseif(array_search($extension,$this->safe_extensions)!==false || array_search($file,$this->safe_files)!==false) //Copy file
				copy($dir_from.'/'.$file,$dir_to.'/'.$file);
		}
		if(isset($pages))
			return $pages;
	}
	function delete_dir($dir)
	{
		if (PHP_OS === 'Windows')
			exec("rd /s /q {$path}");
		else
			exec("rm -rf {$path}");
	}
}