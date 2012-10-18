<?php

namespace Bob\BuildConfig;

task("default", array("test"), function() {
    
});

desc("Runs all tests.");
task("test", array("phpunit.xml", "composer.json"), function() {
    sh("phpunit");
});

fileTask("phpunit.xml", array("phpunit.dist.xml"), function($task) {
    copy($task->prerequisites[0], $task->name);
});

fileTask("composer.json", array("composer.lock"), function($task) {
    if (!file_exists("composer.phar")) {
        file_put_contents("composer.phar", file_get_contents("http://getcomposer.org/composer.phar"));
    }

    php("composer.phar install");
});
