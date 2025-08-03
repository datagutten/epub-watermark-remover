<?Php
class remove_epub_watermark
{
	public $dir_input;
	public $dir_output;
	public $isbn;
	public $opf;
	public $toc;
	public $safe_files=array('cover.jpg','gyldendallogo.gif','mimetype','license','template.xpgt');
    public $safe_extensions = array('otf', 'xml', 'css', 'js', 'gif', 'jpg', 'jpeg', 'png');

	function __construct($file_or_dir)
	{
		$file_or_dir=realpath($file_or_dir);
		$pathinfo=pathinfo($file_or_dir);
		if(!file_exists($file_or_dir))
			die("$file_or_dir does not exist\n");
		if(is_file($file_or_dir))
		{
			if($pathinfo['extension']=='epub')
			{
				$this->dir_input=$pathinfo['dirname'].'/'.$pathinfo['filename']; //epub should be unpacked the a dir with the same name as the file
				if(!file_exists($this->dir_input)) //Do not unpack again
				{
					if(PHP_OS==='WINNT')
						shell_exec(sprintf('"%s\\7-zip\\7za.exe" x -r -o"%s" "%s"',dirname(__FILE__),$this->dir_input,$file_or_dir));
					else
						shell_exec(sprintf('unzip -d "%s" "%s" 2>&1',$this->dir_input,$file_or_dir));

					if(!file_exists($this->dir_input)) //Check if unpacking was successful
						die("Unzip failed\n");
				}
			}
			else
				die("Invalid file: $file_or_dir\n");
				//$this->dir_input=dirname($file_or_dir);
		}
		else
			$this->dir_input=$file_or_dir;

		$this->dir_output=$this->dir_input.'_cleaned'; //Output dir should be the samed as input with _cleaned added

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
			elseif(array_search($extension,$this->safe_extensions)!==false || array_search($file,$this->safe_files)!==false) //Safe files can be copied directly to output folder
				copy($dir_from.'/'.$file,$dir_to.'/'.$file);
			else
				echo "Not copying $file ($extension)\n";
		}
		if(isset($pages))
			return $pages;
	}
	function delete_dir($path)
	{
		if (PHP_OS === 'WINNT')
			exec(sprintf('rd /s /q "%s"',$path));
		else
			exec(sprintf('rm -rf "%s"',$path));
	}
}