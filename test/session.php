<?php

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




