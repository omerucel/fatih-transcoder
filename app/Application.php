<?php

class Application
{
    private $basePath = "";
    private $configuration;
    private $_instances;

    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
    }

    public function getBasePath()
    {
        return $this->basePath;
    }

    public function loadConfiguration($path)
    {
        $this->configuration = Symfony\Component\Yaml\Yaml::parse($path);
    }

    /**
     * @return Twig_Environment
     */
    public function getTwigEnvironment()
    {
        if (!isset($this->_instances['twig']))
        {
            $this->_instances['twig'] = new Twig_Environment(
                new Twig_Loader_Filesystem($this->getRealPath($this->configuration['views']['path'])));
            $function = new Twig_SimpleFunction('show_filesize', function($bytes, $precision=2){
                $units = array('B', 'KB', 'MB', 'GB', 'TB');

                $bytes = max($bytes, 0);
                $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
                $pow = min($pow, count($units) - 1);

                $bytes /= pow(1024, $pow);

                return round($bytes, $precision) . ' ' . $units[$pow];
            });
            $this->_instances['twig']->addFunction($function);
        }

        return $this->_instances['twig'];
    }

    /**
     * @return PDO
     */
    public function getDbConnection()
    {
        if (!isset($this->_instances['db']))
        {
            $this->_instances['db'] = new PDO($this->configuration['db']['dsn']
                , $this->configuration['db']['user'], $this->configuration['db']['pass']);
        }

        return $this->_instances['db'];
    }

    private function getRealPath($path)
    {
        return realpath(str_replace('$basePath', $this->basePath, $path));
    }

    public function getConfig($key)
    {
        return $this->configuration[$key];
    }
}