#!/usr/bin/php
<?php

namespace GitExercises\hook;
require __DIR__ . '/../vendor/autoload.php';

use GitExercises\hook\utils\ConsoleUtils;
use GitExercises\hook\utils\GitUtils;
use GitExercises\services\CommiterService;
use GitExercises\services\GamificationService;

$branch = $argv[1];
$oldRev = $argv[2];
$newRev = $argv[3];


if (strpos($branch, 'refs/heads/') === 0) {
    $branch = substr($branch, strlen('refs/heads/'));
}

$outputSeparator = str_repeat('*', 72);
echo "(\n$outputSeparator\n";

$commiterService = new CommiterService();
$commiterEmail = GitUtils::getCommiterEmail($newRev);
$commiterId = $commiterService->getCommiterId($commiterEmail);
$gamificationService = new GamificationService($commiterId);

$command = 'GitExercises\\hook\\commands\\' . ucfirst($branch) . 'Command';
if (class_exists($command)) {
    (new $command())->execute($commiterId);
} else {
    /** @var AbstractVerification $verifier */
    $verifier = null;
    try {
        $verifier = AbstractVerification::factory($branch, $oldRev, $newRev);
    } catch (\InvalidArgumentException $e) {
        echo 'Status: ';
        echo ConsoleUtils::yellow('UNKNOWN EXERCISE');
    }
    if ($verifier) {
        $passed = true;

        echo 'Exercise: ' . $verifier->getShortInfo() . PHP_EOL;
        echo 'Status: ';
        try {
            $verifier->verify();
            echo ConsoleUtils::green('PASSED') . PHP_EOL;
            $solution = $verifier->getSolution();
            if ($solution) {
                echo PHP_EOL . ConsoleUtils::pink('The easiest solution:') . PHP_EOL . trim($solution) . PHP_EOL . PHP_EOL;
            }
        } catch (VerificationFailure $e) {
            $passed = false;
            echo ConsoleUtils::red('FAILED') . PHP_EOL;
            echo $e->getMessage();
        }
        $commiterName = GitUtils::getCommiterName($newRev);
        $commiterService->saveAttempt($commiterEmail, $commiterName, $branch, $passed);
        if ($passed) {
            $nextTask = $commiterService->suggestNextExercise($commiterId);
            if (!$nextTask) {
                echo ConsoleUtils::blue('Congratulations! You have done all exercises!') . PHP_EOL;
            } else {
                echo "Next task: $nextTask" . PHP_EOL;
                echo "In order to start, execute: ";
                echo ConsoleUtils::blue("git start $nextTask");
            }
            if ($gamificationService->isGamificationSessionActive()) {
                echo PHP_EOL, PHP_EOL;
                $gamificationService->printExerciseSummary($branch);
            }
        }
        if ($gamificationService->isGamificationSessionActive()) {
            $gamificationService->newAttempt($passed);
            echo PHP_EOL, $gamificationService->getGamificationStatus();
        }
    }
}

echo "\n$outputSeparator\n)";

echo PHP_EOL . 'Remember that you can use ' . ConsoleUtils::pink('git verify')
    . ' command to strip disturbing content.' . PHP_EOL;

exit(1);
