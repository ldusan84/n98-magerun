<?php

namespace N98\Magento\Command;

use Symfony\Component\Yaml\Yaml;
use N98\Util\Filesystem;

class ConfigurationLoader
{
    /**
     * @var array
     */
    protected $_configArray;

    /**
     * @var string
     */
    protected $_customConfigFilename = 'n98-magerun.yaml';

    /**
     * "All configs will be merged in the following order: buildin -> system -> user -> current directory -> project"
     * @see https://github.com/netz98/n98-magerun/wiki/Config
     */
    public function __construct()
    {
        $config = Yaml::parse(__DIR__ . '/../../../../config.yaml');

        // Check if there is a user config file. ~/.n98-magerun.yaml
        $homeDirectory = getenv('HOME');
        $personalConfigFile = $homeDirectory . DIRECTORY_SEPARATOR . '.' . $this->_customConfigFilename;

        if ($homeDirectory && file_exists($personalConfigFile)) {
            $personalConfig = Yaml::parse($personalConfigFile);
            $config = $this->mergeArrays($config, $personalConfig);
        }

        $fs = new Filesystem();
        $folder = $fs->getPwd();
        $dirConfigFile = $folder . DIRECTORY_SEPARATOR . $this->_customConfigFilename;
        if (file_exists($dirConfigFile)) {
            $dirConfig = Yaml::parse($dirConfigFile);
            $config = $this->mergeArrays($config, $dirConfig);
        }

        $this->_configArray = $config;
    }

    /**
     * @param $magentoRootFolder
     */
    public function addProjectConfig($magentoRootFolder)
    {
        // MAGENTO_ROOT/app/etc/n98-magerun.yaml
        $projectConfigFile = $magentoRootFolder . DIRECTORY_SEPARATOR . 'app/etc/' . $this->_customConfigFilename;
        if (file_exists($projectConfigFile)) {
            $projectConfig = Yaml::parse($projectConfigFile);
            foreach($projectConfig['autoloaders'] as &$value) {
                $value = str_replace('%root%', $magentoRootFolder, $value);
            }
            $this->_configArray = $this->mergeArrays($this->_configArray, $projectConfig);
        }
    }

    /**
     * Merge two arrays together.
     *
     * If an integer key exists in both arrays, the value from the second array
     * will be appended the the first array. If both values are arrays, they
     * are merged together, else the value of the second array overwrites the
     * one of the first array.
     *
     * @see http://packages.zendframework.com/docs/latest/manual/en/index.html#zend-stdlib
     * @param  array $a
     * @param  array $b
     * @return array
     */
    public function mergeArrays(array $a, array $b)
    {
        foreach ($b as $key => $value) {
            if (array_key_exists($key, $a)) {
                if (is_int($key)) {
                    $a[] = $value;
                } elseif (is_array($value) && is_array($a[$key])) {
                    $a[$key] = $this->mergeArrays($a[$key], $value);
                } else {
                    $a[$key] = $value;
                }
            } else {
                $a[$key] = $value;
            }
        }

        return $a;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->_configArray;
    }

}
