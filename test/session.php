<?php

require_once('../../simpletest/unit_tester.php');
require_once('../../simpletest/reporter.php');
require_once('../kirby.php');

class SessionTest extends UnitTestCase {

  function setUp()
  {
    s::start();
    s::set('key', 'value');
  }

  function tearDown()
  {
    s::destroy();
  }

  function testSessionGet()
  {
    $this->assertEqual(s::get('key'), 'value');
  }

  function testSessionRemove()
  {
    s::remove('key');
    $this->assertNull(s::get('key'));
  }

}


$test = new SessionTest();
$test->run(new HtmlReporter());


