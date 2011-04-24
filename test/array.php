<?php

require_once('../kirby.php');

class ArrayTest extends UnitTestCase {

  function setUp()
  {
    $this->arr = array(
      'cat' => 'miao',
      'dog' => 'wuff',
      'bird' => 'tweet'
    );
  }

  function testArrayGet()
  {
    $this->assertEqual(a::get($this->arr, 'cat'), 'miao');
    $this->assertEqual(a::get($this->arr, 'elephant', 'shut up'), 'shut up');
  }

  function testArrayRemove()
  {
    $this->assertEqual(a::remove($this->arr, 'cat'), array('dog'=>'wuff', 'bird'=>'tweet'));
  }

  function testArrayShow()
  {
    $this->assertEqual(a::show($this->arr, false), '<pre>Array
(
    [cat] =&gt; miao
    [dog] =&gt; wuff
    [bird] =&gt; tweet
)
</pre>');
  }

  function testArrayJson()
  {
    $this->assertEqual(a::json($this->arr) , '{"cat":"miao","dog":"wuff","bird":"tweet"}');
  }

  function testArrayXML()
  {
    $this->assertEqual(a::xml($this->arr), 
    '<?xml version="1.0" encoding="utf-8"?>
<root>
  <cat>miao</cat>
  <dog>wuff</dog>
  <bird>tweet</bird>
</root>
' );
  }

}
