<?php

// NOTE:
// to run these tests, download simpletest (http://www.simpletest.org/)
// and place it outside (but at the same level) of the kirby directory.
//
require_once('../../simpletest/unit_tester.php');
require_once('../../simpletest/reporter.php');
require_once('session.php');
require_once('string.php');
require_once('array.php');

$test = new SessionTest();
$test->run(new HtmlReporter());
$test = new StringTest();
$test->run(new HtmlReporter());
$test = new ArrayTest();
$test->run(new HtmlReporter());
