<?php

namespace Photobooth\Service;

use Photobooth\Helper;
use Photobooth\Utility\ArrayUtility;

class ConfigurationService
{
    protected string $defaultConfigurationFile = __DIR__ . '/../../../config/config.inc.php';
    protected string $localConfigurationFile = __DIR__ . '/../../../config/my.config.inc.php';

    protected ?array $defaultConfiguration = null;
    protected ?array $localConfiguration = null;

    public function __construct()
    {
        if (file_exists($this->localConfigurationFile) && !is_writable($this->localConfigurationFile)) {
            throw new \RuntimeException('Can not write ' . $this->localConfigurationFile . '.', 1695056723);
        } elseif (!file_exists($this->localConfigurationFile) && !is_writable(dirname($this->localConfigurationFile))) {
            throw new \RuntimeException('Can not create ' . $this->localConfigurationFile . '. Config folder is not writable.', 1695056724);
        }
        if (!file_exists($this->localConfigurationFile)) {
            $this->writeConfiguration([]);
        }
    }

    public function getDefaultConfiguration(): array
    {
        if ($this->defaultConfiguration === null) {
            $config = [];
            require $this->defaultConfigurationFile;
            $this->defaultConfiguration = $config;
        }

        return $this->defaultConfiguration;
    }

    public function getLocalConfiguration(): array
    {
        if ($this->localConfiguration === null) {
            $config = [];
            require $this->localConfigurationFile;
            $this->localConfiguration = $config;
        }

        return $this->localConfiguration;
    }

    public function getConfiguration(): array
    {
        return ArrayUtility::mergeRecursive($this->getDefaultConfiguration(), $this->getLocalConfiguration());
    }

    public function setByPath($path, $value): self
    {
        $this->localConfiguration = ArrayUtility::setValueByPath($this->getLocalConfiguration(), $path, $value);

        return $this;
    }

    public function getByPath(string $path): mixed
    {
        $configuration = $this->getConfiguration();
        return ArrayUtility::getValueByPath($configuration, $path);
    }

    public function updateConfiguration(array $newConfiguration): self
    {
        $this->localConfiguration = $newConfiguration;
        return $this;
    }

    public function writeConfiguration(): self
    {
        $filename = $this->localConfigurationFile;
        $configuration = ArrayUtility::diffRecursive(
            $this->getLocalConfiguration(),
            $this->getDefaultConfiguration()
        );
        $content = "<?php\n\$config = " . var_export($configuration, true) . ';';

        if (file_put_contents($filename, $content)) {
            Helper::clearCache($filename);
        }

        return $this;
    }

    public static function getInstance(): self
    {
        if (!isset($GLOBALS[self::class])) {
            throw new \Exception(self::class . ' instance does not exist in $GLOBALS.');
        }

        return $GLOBALS[self::class];
    }
}
