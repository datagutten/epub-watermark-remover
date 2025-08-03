<?php

namespace datagutten\EpubWatermarkRemover;

use Symfony\Component\Process\Process;

class EPUB
{
    /**
     * Build epub from folder
     * @param string $folder
     * @return string
     */
    public static function build_epub(string $folder): string
    {
        $epub_file = sprintf('%s.epub', $folder);
        $process = new Process(['zip', '-0', '-X', $epub_file, 'mimetype'], $folder);
        $process->mustRun();
        $process = new Process(['zip', '-rg', $epub_file, '.', '-x', 'mimetype'], $folder);
        $process->mustRun();
        return $epub_file;
    }
}