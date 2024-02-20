<?php
spl_autoload_register(function ($class) {
    include __DIR__ . '/tests/TestCase.php';
});

  if (class_exists(Tests\TestCase::class, false)) {
    $hello = new \Tests\TestCase("Test");
    exit('class exists');
  } else {
    exit('class does not exists');
  }