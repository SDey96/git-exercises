<?php

namespace GitExercises;

defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

ini_set('display_errors', 'Off');
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/data/logs/error.log");

require __DIR__ . '/vendor/autoload.php';

srand(microtime(true));

$app = new Application();

$app->group('/api', function () use ($app) {
    $app->get('/latest', 'GitExercises\\controllers\\HomeController:latest');
    $app->get('/commiter/:id', 'GitExercises\\controllers\\CommiterController:get');
    $app->get('/exercise', 'GitExercises\\controllers\\ExerciseController:list');
    $app->get('/exercise/:id', 'GitExercises\\controllers\\ExerciseController:readme');
});

$app->run();