<?php

require_once('PHPUnit/Autoload.php');
require_once(dirname(__FILE__) . '/../kirby.php');
 
class str_tests extends PHPUnit_Framework_TestCase {

  var $sample = 'Super Äwesøme String';

  public function test_html() {
    $this->assertEquals('Super &Auml;wes&oslash;me String', str::html($this->sample));
  }

  public function test_unhtml() {
    $this->assertEquals($this->sample, str::unhtml('Super &Auml;wes&oslash;me String'));
  }

  public function test_xml() {
    $this->assertEquals('Super &#196;wes&#248;me String', str::xml($this->sample));
  }

  public function test_unxml() {
    $this->assertEquals($this->sample, str::unxml('Super &#196;wes&#248;me String'));
  }

  public function test_parse() {
    // no test yet
  }

  public function test_encode() {
    // no test yet
  }

  public function test_email() {
    // no test yet
  }

  public function test_link() {
    
    // without text
    $this->assertEquals('<a href="http://getkirby.com">http://getkirby.com</a>', str::link('http://getkirby.com'));
    
    // with text
    $this->assertEquals('<a href="http://getkirby.com">Kirby</a>', str::link('http://getkirby.com', 'Kirby'));

  }

  public function test_short() {
    
    // too long
    $this->assertEquals('Supe…', str::short($this->sample, 5));
    
    // not too long
    $this->assertEquals($this->sample, str::short($this->sample, 100));

    // zero chars
    $this->assertEquals($this->sample, str::short($this->sample, 0));

    // with different ellipsis character
    $this->assertEquals('Su---', str::short($this->sample, 5, '---'));

  }

  public function test_shorturl() {
    
    $sample = 'http://getkirby.com';
    $long   = 'http://getkirby.com/docs/example/1/2/3';
      
    // without shortening
    $this->assertEquals('getkirby.com', str::shorturl($sample, false));

    // zero chars
    $this->assertEquals('getkirby.com', str::shorturl($sample, 0));
    
    // with shortening
    $this->assertEquals('getk…', str::shorturl($sample, 5));

    // with different ellipsis character
    $this->assertEquals('ge---', str::shorturl($sample, 5, false, '---'));

    // only keeping the base
    $this->assertEquals('getkirby.com', str::shorturl($long, false, true));

  }

  public function test_excerpt() {
    // no test yet
  }

  public function test_cutout() {
    // no test yet
  }

  public function test_apostrophe() {
    // no test yet
  }

  public function test_plural() {
    
    // 0
    $this->assertEquals('zero', str::plural(0, 'many', 'one', 'zero'));

    // 1
    $this->assertEquals('one', str::plural(1, 'many', 'one', 'zero'));

    // 2
    $this->assertEquals('many', str::plural(2, 'many', 'one', 'zero'));

    // 0 without zero
    $this->assertEquals('many', str::plural(0, 'many', 'one'));
    
  }

  public function test_substr() {

    $this->assertEquals('Super', str::substr($this->sample, 0, 5));

    $this->assertEquals(' Äwes', str::substr($this->sample, 5, 5));

    $this->assertEquals(' Äwesøme String', str::substr($this->sample, 5));

    $this->assertEquals('tring', str::substr($this->sample, -5));

  }

  public function test_lower() {
    $this->assertEquals('super äwesøme string', str::lower($this->sample));
  }

  public function test_upper() {
    $this->assertEquals('SUPER ÄWESØME STRING', str::upper($this->sample));
  }
  
  public function test_length() {
    $this->assertEquals(20, str::length($this->sample));
  }

  public function test_contains() {

    $this->assertTrue(str::contains($this->sample, 'Äwesøme'));
    $this->assertTrue(str::contains($this->sample, 'äwesøme'));

    // don't ignore upper/lowercase
    $this->assertFalse(str::contains($this->sample, 'äwesøme', false));

    // check for something which isn't there    
    $this->assertFalse(str::contains($this->sample, 'Peter'));

  }

  public function test_match() {
    // no test yet
  }

  public function test_random() {
    // no test yet
  }

  public function test_urlify() {
    // no test yet
  }

  public function test_split() {

    $array = array('Super', 'Äwesøme', 'String');
    $this->assertEquals($array, str::split($this->sample, ' '));

    $array = array('Äwesøme', 'String');
    $this->assertEquals($array, str::split($this->sample, ' ', 6));

    $array = array('design', 'super', 'fun', 'great', 'nice/super');
    $this->assertEquals($array, str::split('design, super,, fun, great,,, nice/super'));

  }
  
  public function test_trim() {
    // no test yet
  }

  public function test_sanitize() {
    // no test yet
  }

  public function test_ucwords() {

    $string = str::lower($this->sample);
    $this->assertEquals($this->sample, str::ucwords($string));
    
  }

  public function test_ucfirst() {

    $string = str::lower($this->sample);

    $this->assertEquals('Super äwesøme string', str::ucfirst($string));
    
  }

  public function test_utf8() {

    $this->assertEquals($this->sample, str::utf8($this->sample));
    
  }

  public function test_stripslashes() {
    // no test yet
  }
     
}

?>