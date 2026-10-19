<?php

use datagutten\epub\EPUBUtils;
use datagutten\EpubWatermarkRemover\Page;
use datagutten\EpubWatermarkRemover\RewriteOPF;
use datagutten\EpubWatermarkRemover\RewriteTOC;
use datagutten\tools\files\files;

require_once __DIR__ . '/vendor/autoload.php';
try
{
    $remove_epub_watermark = new remove_epub_watermark($argv[1]);
}
catch (FileNotFoundException $e)
{
    die($e);
}

foreach ($remove_epub_watermark->loadfiles() as $file) //Loop through the pages
{
    $page = new Page($file);
    $dom = $page->dom;
    $page->strip_meta();
    $output_string = $page->strip_strings($remove_epub_watermark->config['bad_string_regex'] ?? []);

    $output_file = $remove_epub_watermark->output_file($file);
    file_put_contents($output_file, trim($output_string));
}

foreach (files::get_files($remove_epub_watermark->dir_output, ['xhtml']) as $file)
{
    $page = new Page($file);
    $page->check_links();
}

$opf = new RewriteOPF($remove_epub_watermark->opf, $remove_epub_watermark->dir_output);
$opf->rewrite($remove_epub_watermark->output_file($remove_epub_watermark->opf));

$toc = new RewriteTOC($remove_epub_watermark->toc, $opf->content_folder);
$toc->rewrite($remove_epub_watermark->output_file($remove_epub_watermark->toc), $opf->isbn);

$output_epub = $remove_epub_watermark->dir_output . '.epub';
echo "Creating new epub\n";

$epub_file = EPUBUtils::buildEPUB($remove_epub_watermark->dir_output);
if (!file_exists($epub_file))
    echo "epub creation failed\n";
else
{
    $remove_epub_watermark->delete_dir($remove_epub_watermark->dir_output);
    if (is_file($argv[1]))
        $remove_epub_watermark->delete_dir($remove_epub_watermark->dir_input);
    echo sprintf("%s.epub was successfully created\n", $remove_epub_watermark->dir_output);
}
