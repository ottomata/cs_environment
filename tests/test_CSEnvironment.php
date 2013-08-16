<?php defined('SYSPATH') OR die('No direct access allowed.');

class test_CSEnvironment extends PHPUnit_Framework_TestCase
{    	
	// since all tests must run in a TEST environment, 
	// these tests can really only test that CSEnvironment
	// methods return properly.
	
	
	public $env = CSEnvironment::TEST;
	public  $env_name = 'test';

	public function test_get() 
	{
		$this->assertTrue($this->env & CSEnvironment::get() ? true : false);
		$this->assertEquals($this->env, CSEnvironment::get($this->env_name));
		$this->assertEquals(CSEnvironment::DEVELOPMENT, CSEnvironment::get('development'));
	}
	
	
	public function test_get_name()
	{
		$this->assertEquals($this->env_name, CSEnvironment::get_name($env));
		$this->assertEquals('development', CSEnvironment::get_name(CSEnvironment::DEVELOPMENT));
	}
	
	public function test_is()
	{
		$this->assertTrue(CSEnvironment::is($this->env));
		$this->assertTrue(CSEnvironment::is($this->env | CSEnvironment::DEVELOPMENT));
		$this->assertFalse(CSEnvironment::is(CSEnvironment::PRODUCTION | CSEnvironment::BETA));
		
		$this->assertTrue(CSEnvironment::is(CSEnvironment::ALPHA | CSEnvironment::BETA, CSEnvironment::ALPHA));
		$this->assertFalse(CSEnvironment::is(CSEnvironment::ALPHA | CSEnvironment::BETA, CSEnvironment::PRODUCTION));
		
	}

	public function test_current()
	{
		$this->assertTrue($this->env & CSEnvironment::current() ? true : false);
	}
}	
