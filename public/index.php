<?php

$basePath = realpath(__DIR__ . '/../');

// Loader ayarları.
$loader = require '../vendor/autoload.php';
$loader->add('Controller', $basePath . '/app/');
$loader->add('Model', $basePath . '/app/');

// Site ayarları
include_once($basePath . '/app/Application.php');
$application = new Application();
$application->setBasePath($basePath);
$application->loadConfiguration($basePath . '/conf/development.yaml');

// Base Controller yapılandırılıyor.
Controller\Base::setApplication($application);

// Router Ayarları
ToroHook::add('404', function() use ($application){
    echo $application->getTwigEnvironment()->render('404.twig');
});

Toro::serve(array(
    '/' => 'Controller\Homepage',
    '/register' => 'Controller\Register',
    '/login' => 'Controller\Login',
    '/logout' => 'Controller\Logout',
    '/account' => 'Controller\Account',
    '/file-manager' => 'Controller\FileManager',
    '/upload' => 'Controller\Upload',
    '/stage-files' => 'Controller\StageFiles',
    '/production-files' => 'Controller\ProductionFiles',
    '/stage-file/([0-9\,]+)' => 'Controller\StageFile',
    '/production-file/([0-9\,]+)' => 'Controller\ProductionFile',
    '/transcode' => 'Controller\Transcode',
    '/history' => 'Controller\History',
    '/jobs' => 'Controller\Jobs',
    '/job/:number' => 'Controller\Job'
));