<?php

use Kodekit\Library;

// Register component with bootstrapper
Library\ObjectManager::getInstance()->getObject('object.bootstrapper')->registerComponent(
    __DIR__ . '/src'
);
