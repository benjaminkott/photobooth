<?php

namespace Photobooth\Service;

use Photobooth\Utility\PathUtility;
use Symfony\Component\Translation\Loader\JsonFileLoader;
use Symfony\Component\Translation\Translator;

class LanguageService
{
    private string $locale;
    private ConfigurationService $configuration;
    private Translator $translator;

    public function __construct()
    {
        $this->configuration = ConfigurationService::getInstance();
        $this->locale = $this->configuration->getByPath('ui/language');

        $translator = new Translator($this->locale);
        $translator->setFallbackLocales(['en']);
        $translator->addLoader('json', new JsonFileLoader());

        $path = PathUtility::getAbsolutePath($this->configuration->getByPath('ui/folders_lang'));
        if (PathUtility::isAbsolutePath($path)) {
            foreach (new \DirectoryIterator($path) as $file) {
                if(!$file->isFile() || strtolower($file->getExtension() !== 'json')) {
                    continue;
                }
                $translator->addResource('json', $path . '/' . $file->getFilename(), $file->getBasename('.' . $file->getExtension()), 'photobooth');
            }
        }

        $this->translator = $translator;
    }

    public function translate(string $id): string
    {
        return $this->translator->trans($id, [], 'photobooth');
    }

    public function all(): array
    {
        return $this->translator->getCatalogue($this->locale)->all('photobooth');
    }

    public static function getInstance(): self
    {
        if (!isset($GLOBALS[self::class])) {
            throw new \Exception(self::class . ' instance does not exist in $GLOBALS.');
        }

        return $GLOBALS[self::class];
    }
}
