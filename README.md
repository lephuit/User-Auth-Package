## user auth package for FuelPHP framework

**User authentication package for [FuelPHP](http://fuelphp.com) framework.**    


#### Features
* Authentication
* Groups / Roles
* Remember Me
* Password Reset
* User Metadata
* Caching
* Update User Settings   


*any questions, suggestions to improve this* kevin.sakhuja@gmail.com   
*twitter* [@kevbook](https://twitter.com/kevbook)

***

#### Guide

1. Load the auth package, edit 'config.php' of your app
	
		'packages'  => array('auth')


2. Creates a new account with email, password, group and other information such as location etc. 

		Auth::create($email,$password, $group='User');

	Note: Groups are defined in the package config.php

		'groups' => array(
			1	=> 'User',
			50	=> 'Moderator',
			100	=> 'Administrator',
			)


3.	Checks if the user is logged, confirms the role and returns data about the user. The data is pulled from the **cache.** 

		$user_meta = Auth::User();   


4. Login user with email and password. If login in not valid, then returns **AuthException $e** 

		try{
			if(Auth::login($email, $password){
				// login success, do something
			}
		}
		catch (AuthException $e)
		{
			// incorrect login
		}


5. Logs out the user
		
		Auth:logout();


6. Updates user information

		Auth:update($user_meta = array());



7. There are other methods available such as:
		
		Auth::change_password($old_password, $new_password)
		
		Auth::forgot_password($email)



***

### MIT License
Copyright © 2012 Kevin Sakhuja

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.