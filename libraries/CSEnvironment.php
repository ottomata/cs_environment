<?php

/**
* CSEnvironment class.
* Static methods to get, compare, and save the current working environment.
* For the most part, you will ever only need to use the is() method.
* The environment will be initialized automatically by checking these places (in order)
* 	1. Kohana::config('config.environment')
*   2. $_ENV['cs_environment]
* If neither of these are set, the environment will default to development.
* 
* Common Usage:
* if (CSEnvironment::is(CSEnvironment::DEVELOPMENT | CSEnvironment::ALPHA)) 
* 	...
* if (!CSEnvironment::is(CSEnvironment::PRODUCTION))
* 	..
* etc.
* 
*/
class CSEnvironment
{
	const DEVELOPMENT = 0x0008;   // 0000 0000 0000 1000 (8)
	const TEST        = 0x0010;   // 0000 0000 0001 0000 (16)	
	const ALPHA       = 0x0080;   // 0000 0000 1000 0000 (128)
	const BETA        = 0x0800;   // 0000 1000 0000 0000 (2048)
	const PRODUCTION  = 0x8000;   // 1000 0000 0000 0000 (32768)

	// maps environment string names to their class constants.
	public static $environments = array(
		'development' => self::DEVELOPMENT,
		'test'        => self::TEST,
		'alpha'       => self::ALPHA,
		'beta'        => self::BETA,
		'production'  => self::PRODUCTION,
	);
	
	protected static $current_environment;

	protected static $fake_environments = array();

	/**
	* Gets a CSEnvironment class constant environment from 
	* a named environment string.  This is useful from going
	* from the $_SERVER['CS_ENVIRONMENT'] variable defined
	* in apache confs to a constant.
	* @param string $environment_string.  If not specified, the current environment will be returned
	* @return integer constant environment 
	* @throws exception if $environment_string is not a valid environment name in the $environments array
	*/
	public static function get($environment_string = null) 
	{
		if (empty($environment_string))
			return self::current();
		
		$environment_string = strtolower($environment_string);
			
		if (array_key_exists($environment_string, self::$environments))
			return self::$environments[$environment_string];
		else 
			throw new Exception("'$environment_string' is not a valid CSEnvironment name.  Must be one of " . implode(', ', array_keys(self::$environments)) . '.');
	}
	
	/**
	* Returns the string name of an environment constant.
	* @param integer $environment constant.  If not specified, the self::current() will be used.
	* @return string environment name, false if the provided environment is not defined here.
	*/
	public static function get_name($environment = null) 
	{
		if (empty($environment))
			$environment = self::current();
	
		return array_search($environment, self::$environments);
	}
	
	/**
	* Use this function for comparing environments.  You can compare a specific 
	* environment to an ORed list of environments, or leave $current_environment
	* unspecified to check the self::current() environment against 
	* $conditional_environments.
	* 
	* Usage:  To check if the current environment is either ALPHA or BETA, you can do this:
	*    if (CSEnvironment::is(CSEnvironment::ALPHA | CSEnvironment::BETA))
	* 
	* @param integer  $conditional_environments  A combination of CSEnvironment constants.
	* @param integer  $current_environment  Optional.  If not specified, defaults to self::current()
	* @return boolean   true if the current environment is in conditional environments, false otherwise.
	*/
	public static function is($conditional_environments, $current_environment = null) 
	{
		// if $current_environment is not specified
		if (empty($current_environment)) 
			$current_environment = self::current();
	
		// mask $conditional_environment with $current_environment.   If not 0, then true!
		return ($conditional_environments & $current_environment) ? true : false;
	}
	
	/**
	* Looks in Kohana environment and $_ENV['cs_environment'] to see if 
	* an environment has been set.  If not, defaults to DEVELOPMENT.
	* 
	* In public/cs 'old' code, the current environment is stored in the
	* $_ENV['cs_environment'] variable.  In 'new' Kohana code, it is 
	* set in Kohana::config('config.environment') (set in applications/cs/config/config.php).
	*
	* If $fake_key is given, and if in DEVELOPMENT environment ONLY, it'll check for a key
	* in the CSConfig file (public/cs_config.yaml) for a "fake_${fake_key}_environment"
	* value (values can be production, alpha or beta) and use it instead of the real
	* current environment.
	*
	* @param string $fake_key (read the comment of the function)
	* @return integer CSEnvironment constant
	*/
	public static function current($fake_key = NULL)
	{
		if (!self::is_initialized()) 
			self::initialize();

		if ( self::$current_environment == self::DEVELOPMENT && $fake_key )
		{
			//
			// THIS PART IS EXECUTED ONLY ON DEVELOPEMENT ENVIRONMENTS
			//
			// we need this hardcoded as it's being called in config.php of kohana
			// => no IS_KOHANA or no autoloader yet
			if ( !class_exists('CSConfig') )
			{
				require_once(MODPATH . 'cscommon' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'CSConfig.php');
			}
			
			if ( !array_key_exists($fake_key, self::$fake_environments) )
			{
				$e = CSEnvironment::current();
				$name = CSConfig::get('fake_' . $fake_key . '_environment', NULL);
				$envs = array(
					'production'	=> CSEnvironment::PRODUCTION,
					'beta'			=> CSEnvironment::BETA,
					'alpha'			=> CSEnvironment::ALPHA,
				);
				if ( $name && array_key_exists($name, $envs) )
				{
					//$log_line = 'Faking "' . $name . '" environment for "' . $fake_key . '" class';
					$e = $envs[$name];
				}
				self::$fake_environments[$fake_key] = $e;
			}
			return self::$fake_environments[$fake_key];
		}

		return self::$current_environment;
	}

	
	/**
	 * Sets self::$current_environment.
	 * 
	 */
	public static function set($environment) {
		self::$current_environment = $environment;
		return self::$current_environment;
	}

	
	/**
	 * Returns true if self::$current_environment is set, false otherwise
	 * @return boolean
	 */
	public static function is_initialized()
	{
		if (isset(self::$current_environment))
			return true;
		else
			return false;
	}


	/**
	 * This function sets the self::$current_environment variable
	 * based on $_SERVER['CS_ENVIRONMENT'] or $_ENV['cs_environment']
	 * 
	 * @return integer constant current environment
	 */
	public static function initialize()
	{
		// only allow initialization once
		if (self::is_initialized())
			return self::$current_environment;
		
		// CS_ENVIRONMENT should be set by Apache confs.  If not, assume development environment.
		if (empty($_ENV['cs_environment'])) 
		{
			$env = (!empty($_SERVER['CS_ENVIRONMENT'])) ? self::get($_SERVER['CS_ENVIRONMENT']) : self::DEVELOPMENT;
		}
		else if (is_string($_ENV['cs_environment']))
		{
			// if the $_ENV variable is already set, then use the string in $_ENV['cs_environment'] 
			// to translate to the constant integer from the CSEnvironment class.
			// This usually only happens if we are running on the CLI and someone has 
			// set the the $_ENV variable there.
			$env = self::get($_ENV['cs_environment']);
		}	
		
		// default to development environment
		if (empty($env))
		{
			$env = self::DEVELOPMENT;
		}

		return self::set($env);
	}
	
}

