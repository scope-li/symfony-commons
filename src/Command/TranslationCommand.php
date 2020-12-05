<?php

namespace Scopeli\SymfonyCommons\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class TranslationCommand extends Command
{
    const MESSAGES = [
        'translations' => 'messages.%s.yaml',
        'prefix' => 'trans',
    ];

    const VALIDATORS = [
        'translations' => 'validators.%s.yaml',
        'prefix' => 'valids',
    ];

    const SOURCE_TRANSLATION = ['./src', './templates', './assets'];
    const TYPE_TRANSLATION = ['*.php', '*.twig', '*.js'];

    protected static $defaultName = 'app:translation';

    private ?SymfonyStyle $io = null;
    private string $locale;

    public function __construct(string $locale)
    {
        parent::__construct();
        $this->locale = $locale;
    }

    protected function configure(): void
    {
        $this->setDescription('Find, cleanup and sort translation.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->io->title('Messages translation');
        $this->extractTranslation(self::MESSAGES);
        $this->cleanupTranslation(self::MESSAGES);
        $this->mergeTranslation(self::MESSAGES);

        $this->io->title('Validator translation');
        $this->extractTranslation(self::VALIDATORS);
        $this->cleanupTranslation(self::VALIDATORS);
        $this->mergeTranslation(self::VALIDATORS);

        return 0;
    }

    private function extractTranslation(array $config): void
    {
        $this->io->writeln('Search translation.');

        $baseFile = sprintf($config['translations'], $this->locale);
        $baseFilePath = sprintf('./translations/%s', $baseFile);
        $yamlData = Yaml::parseFile($baseFilePath);

        $srcFinder = new Finder();
        $srcFinder->in(self::SOURCE_TRANSLATION);
        $srcFinder->files()->name(self::TYPE_TRANSLATION);

        foreach ($srcFinder as $srcFile) {
            $fileContent = (string) file_get_contents($srcFile);
            $keys = $this->extractKeys($fileContent, $config['prefix']);
            foreach ($keys as $key) {
                if (!isset($yamlData[$key])) {
                    $yamlData[$key] = sprintf('__%s', $key);
                }
            }
        }

        $yaml = Yaml::dump($yamlData);
        file_put_contents($baseFilePath, $yaml);
    }

    protected function extractKeys(string $content, string $prefix): array
    {
        $matches = [];
        $regex = sprintf('/["\\\']%s\.([[:alnum:]._]+)["\\\']/', $prefix);
        preg_match_all($regex, $content, $matches);

        $buffer = $matches[1];

        foreach ($buffer as $key => $value) {
            $buffer[$key] = sprintf('%s.%s', $prefix, $value);
        }
        
        return $buffer;
    }

    private function cleanupTranslation(array $config): void
    {
        $this->io->writeln('Cleanup translation.');

        $baseFile = sprintf($config['translations'], $this->locale);
        $baseFilePath = sprintf('./translations/%s', $baseFile);
        $yamlData = Yaml::parseFile($baseFilePath);
        $yamlDataKeys = array_keys($yamlData);

        $srcFinder = new Finder();
        $srcFinder->in(self::SOURCE_TRANSLATION);
        $srcFinder->files()->name(self::TYPE_TRANSLATION);

        foreach ($yamlDataKeys as $key) {
            $found = false;
            foreach ($srcFinder as $srcFile) {
                $fileContent = (string) file_get_contents($srcFile);
                if (!empty($key) && false !== strpos($fileContent, $key)) {
                    $found = true;
                }
            }

            if (!$found) {
                unset($yamlData[$key]);
            }
        }

        $yaml = Yaml::dump($yamlData);
        file_put_contents($baseFilePath, $yaml);
    }

    private function mergeTranslation(array $config): void
    {
        $this->io->writeln('Merge translation.');

        $baseFile = sprintf($config['translations'], $this->locale);
        $baseFilePath = sprintf('./translations/%s', $baseFile);
        $baseYamlData = Yaml::parseFile($baseFilePath);
        $baseYamlKeys = array_keys($baseYamlData);

        $filter = sprintf($config['translations'], '*');
        $transFinder = new Finder();
        $transFinder->in('./translations');
        $transFinder->files()->name($filter);

        foreach ($transFinder as $transFile) {
            $yamlData = Yaml::parseFile($transFile);

            // Add keys that new in source trasnlation.
            foreach ($baseYamlKeys as $key) {
                if (!isset($yamlData[$key])) {
                    $yamlData[$key] = sprintf('__%s', $key);
                }
            }

            // Remove keys not in the source translation.
            $yamlKeys = array_keys($yamlData);
            foreach ($yamlKeys as $key) {
                if (!isset($baseYamlData[$key])) {
                    unset($yamlData[$key]);
                }
            }

            ksort($yamlData);
            $yaml = Yaml::dump($yamlData);
            file_put_contents($transFile, $yaml);
        }
    }
}
