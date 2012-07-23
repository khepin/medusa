<?php
namespace Khepin\Medusa;

use Symfony\Component\Finder\Finder;

class Compiler
{
    public function compile($pharFile = 'medusa.phar')
    {
        if (file_exists($pharFile)) {
            unlink($pharFile);
        }

        $phar = new \Phar($pharFile, 0, 'medusa.phar');
        $phar->setSignatureAlgorithm(\Phar::SHA1);

        $phar->startBuffering();

        $finders = array();

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->notName('Compiler.php')
            ->in(__DIR__.'/../../')
        ;
        $finders[] = $finder;

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->in([
                __DIR__.'/../../../vendor/symfony/symfony/src/Symfony/Component/Console',
                __DIR__.'/../../../vendor/symfony/symfony/src/Symfony/Component/EventDispatcher',
                __DIR__.'/../../../vendor/symfony/symfony/src/Symfony/Component/Process',
            ])
        ;
        $finders[] = $finder;

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('autoload.php')
            ->in(__DIR__.'/../../../vendor')
        ;
        $finders[] = $finder;

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->in(__DIR__.'/../../../vendor/composer')
            ->depth(0)
        ;
        $finders[] = $finder;

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->in(__DIR__.'/../../../vendor/guzzle')
        ;
        $finders[] = $finder;
        
        foreach ($finders as $finder) {
            foreach ($finder as $file) {
                $this->addFile($phar, $file);
            }
        }

        $this->addBin($phar);

        // Stubs
        $phar->setStub($this->getStub());

        $phar->stopBuffering();

        $phar->compressFiles(\Phar::GZ);

        unset($phar);
    }

    private function addFile($phar, $file, $strip = true)
    {
        $path = str_replace(dirname(dirname(dirname(__DIR__))).DIRECTORY_SEPARATOR, '', $file->getRealPath());

        $content = file_get_contents($file);
        if ($strip) {
            $content = $this->stripWhitespace($content);
        } elseif ('LICENSE' === basename($file)) {
            $content = "\n".$content."\n";
        }

        $phar->addFromString($path, $content);
    }

    private function addBin($phar)
    {
        $content = file_get_contents(__DIR__.'/../../../bin/medusa');
        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
        $phar->addFromString('bin/medusa', $content);
    }

    private function stripWhitespace($source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $output = '';
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $output .= $token;
            } elseif (in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) {
                $output .= str_repeat("\n", substr_count($token[1], "\n"));
            } elseif (T_WHITESPACE === $token[0]) {
                // reduce wide spaces
                $whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);
                // normalize newlines to \n
                $whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                // trim leading spaces
                $whitespace = preg_replace('{\n +}', "\n", $whitespace);
                $output .= $whitespace;
            } else {
                $output .= $token[1];
            }
        }

        return $output;
    }

    private function getStub()
    {
        return <<<'EOF'
#!/usr/bin/env php
<?php
Phar::mapPhar('medusa.phar');

require 'phar://medusa.phar/bin/medusa';

__HALT_COMPILER();
EOF;
    }
}
