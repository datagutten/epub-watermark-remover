<?Php

use datagutten\EpubWatermarkRemover\EPUB;
use datagutten\tools\files\files;
use Symfony\Component\Filesystem;
use Symfony\Component\Yaml\Yaml;


class remove_epub_watermark
{
    public string $dir_input;
    public string $dir_output;
    public array $config;
    /**
     * @var string OPF file path
     */
    public string $opf;
    /**
     * @var string OPF file path
     */
    public string $toc;
    public EPUB $epub;
    public array $safe_files = ['mimetype', 'license', 'template.xpgt'];

	function __construct($file_or_dir)
	{
        if (!file_exists($file_or_dir))
            throw new FileNotFoundException($file_or_dir);
        $this->config = Yaml::parseFile(__DIR__.'/config.yml');
		$file_or_dir=realpath($file_or_dir);
        $pathinfo = pathinfo($file_or_dir);
        $this->dir_input = files::path_join($pathinfo['dirname'], $pathinfo['filename'] . '_watermark');
        $this->dir_output = $this->dir_input . '_cleaned'; //Output dir should be the samed as input with _cleaned added
        $this->epub = new EPUB($file_or_dir, $this->dir_input);

		if(!file_exists($this->dir_output))
			mkdir($this->dir_output);
	}
	function loadfiles($dir_from=false,$dir_to=false)
	{
		if($dir_from===false)
			$dir_from=$this->dir_input;
		if($dir_to===false)
			$dir_to=$this->dir_output;

        $pages = [];
		foreach(scandir($dir_from) as $file)
		{
			$extension=pathinfo($file,PATHINFO_EXTENSION);
			//echo $file."\n";
            if (in_array($file, $this->config['bad_files']) || in_array(basename($file), $this->config['bad_files']))
                printf("Bad file %s\n", $file);
            elseif ($extension == 'opf')
				$this->opf=$dir_from.'/'.$file; //Save opf filename and path
			elseif($file=='toc.ncx')
				$this->toc=$dir_from.'/'.$file;
			elseif(($extension=='xhtml' || $extension=='html') && $file!='rights.xhtml') //Add file to page list
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
			elseif(in_array($extension, $this->config['safe_extensions'] ?? []) || in_array($file, $this->safe_files)) //Safe files can be copied directly to output folder
				copy($dir_from.'/'.$file,$dir_to.'/'.$file);
			elseif(!in_array($file, ['.', '..']))
				echo "Not copying $file ($extension)\n";
		}

        return $pages;
	}
	function delete_dir($path): void
    {
        $filesystem = new Filesystem\Filesystem();
        $filesystem->remove($path);
	}

    /**
     * Get output file with correct directory separator
     * @param string $input_file Input file
     * @return string Output file
     */
    public function output_file(string $input_file): string
    {
        $relative = Filesystem\Path::makeRelative($input_file, $this->dir_input);
        $absolute = Filesystem\Path::makeAbsolute($relative, $this->dir_output);
        return str_replace('/', DIRECTORY_SEPARATOR, $absolute);
    }
}