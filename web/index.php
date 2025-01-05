<?php
require dirname(__DIR__).'/txtlog/core/app.php';

use Txtlog\Core\App;

$app = App::getInstance();

// Start registers the class autoloader, setup page and parameters
$app->start();

// Include the application specific master page, if it exists
$app->showHeader();

// Include the requested page if it is found
if($app->getPage()) {
  require $app->getPage();
} else {
  $app->error404();
}

// Include the application specific footer, if it exists
$app->showFooter();
