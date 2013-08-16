cs_environment
==============

# CSEnvironment class

Static methods to get, compare, and save the current working environment.
For the most part, you will ever only need to use the ```is()``` method.
The environment will be initialized automatically by checking these places (in order)
1. ```Kohana::config('config.environment')```
2. ```$_ENV['cs_environment]```
If neither of these are set, the environment will default to development.

# Common Usage:
```php

if (CSEnvironment::is(CSEnvironment::DEVELOPMENT | CSEnvironment::ALPHA))  { ... }

if (!CSEnvironment::is(CSEnvironment::PRODUCTION)) { ... }

# etc.
```
