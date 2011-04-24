<?php

require_once('../../simpletest/unit_tester.php');
require_once('../../simpletest/reporter.php');
require_once('../kirby.php');

class StringTest extends UnitTestCase {

  function setUp()
  {
  }

  function tearDown()
  {
  }

  function testHtml()
  {
    $this->assertEqual(str::html("some <em>über crazy</em> stuff"), 'some <em>&uuml;ber crazy</em> stuff');
    $this->assertEqual(str::html('some <em>über crazy</em> stuff', false), 'some &lt;em&gt;&uuml;ber crazy&lt;/em&gt; stuff');
  }

  function testUnHtml()
  {
    $this->assertEqual(str::unhtml('some <em>crazy</em> stuff'), 'some crazy stuff');
  }

  function testXml()
  {
    $this->assertEqual(str::xml('some über crazy stuff'), 'some &#252;ber crazy stuff');
  }

  function testUnXml()
  {
    $this->assertEqual(str::unxml('some <em>&#252;ber</em> crazy stuff'), 'some &uuml;ber crazy stuff');
  }

}


$test = new StringTest();
$test->run(new HtmlReporter());


