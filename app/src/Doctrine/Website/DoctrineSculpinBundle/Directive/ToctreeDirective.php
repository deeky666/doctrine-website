<?php

namespace Doctrine\Website\DoctrineSculpinBundle\Directive;

use Gregwar\RST\Directive;
use Gregwar\RST\Environment;
use Gregwar\RST\Parser;

class ToctreeDirective extends Directive
{
    public function getName()
    {
        return 'toctree';
    }

    public function process(Parser $parser, $node, $variable, $data, array $options)
    {
        $environment = $parser->getEnvironment();
        $kernel = $parser->getKernel();
        $files = array();

        // This sucks. I don't even think it works 100% right.
        foreach (explode("\n", $node->getValue()) as $file) {
            $file = trim($file);

            if (isset($options['glob']) && strpos($file, '*') !== false) {
                $currentDirPath = realpath(rtrim($environment->absoluteRelativePath(''), '/'));
                $rootPath = rtrim(str_replace($environment->getDirName(), '', $currentDirPath), '/');

                $findPath = $rootPath.'/'.$file;

                $find = $this->recursiveGlob($findPath);

                foreach ($find as $f) {
                    if (is_dir($f)) {
                        continue;
                    }

                    $f = str_replace($rootPath.'/', '', $f);
                    $f = str_replace('.rst', '', $f);

                    $f = $this->getReferencedFile($environment, $f);

                    $environment->addDependency($f);
                    $files[] = $f;
                }

            } elseif ($file) {
                $file = $this->getReferencedFile($environment, $file);

                $environment->addDependency($file);
                $files[] = $file;
            }
        }

        $document = $parser->getDocument();
        $document->addNode($kernel->build('Nodes\TocNode', $files, $environment, $options));
    }

    public function wantCode()
    {
        return true;
    }

    private function recursiveGlob(string $path)
    {
        $allFiles = [];

        $files =  glob($path);

        $allFiles = array_merge($allFiles, $files);

        foreach ($files as $file) {
            if (is_dir($file)) {
                $dirPath = $file.'/*';

                $dirFiles = $this->recursiveGlob($dirPath);

                $allFiles = array_merge($allFiles, $dirFiles);
            }
        }

        return $allFiles;
    }

    private function getReferencedFile(Environment $environment, string $file)
    {
        $url = $environment->getUrl();

        $e = explode('/', $url);

        if (count($e) > 1) {
            unset($e[count($e) - 1]);
            $folderPath = implode('/', $e).'/';

            if (strpos($file, $folderPath) !== false) {
                $file = str_replace($folderPath, '', $file);
            } else {
                $file = str_repeat('../', count($e)).$file;
            }
        }

        return $file;
    }
}
