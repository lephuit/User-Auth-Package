<?php

namespace Auth;

use Config;
use Cookie;
use Cache;
use Session;
use DB;

class AuthException extends \Fuel_Exception {}

class Auth
{
	/**
	 * @var  array  contains user table data (cached)
	 */
	 protected static $user = array();

	/**
	 * @var  array  contains user_meta table data (cached)
	 */
	 protected static $user_meta = array();

	/**
	 * Prevent instantiation
	 */
	final private function __construct() {}

    /**
     * Prevent object cloning
     */
    final private function  __clone() { }

	public static function _init()
	{
		// load config
		Config::load('auth', true);
	}

	/**
	 * Attempt to log a user in.
	 *
	 * @param   string  email entered
	 * @param   string  password entered
	 * @param   bool    whether to remember the user or not
	 * @return  bool
	 * @throws  AuthException
	 */
	public static function login($email=null, $password=null, $remember = false)
	{
		// log the user out
		static::logout();

		// make sure vars have values
		if (empty($email) or empty($password))
		{
			throw new \AuthException('email or password can\'t be empty.');
		}

		// if user is validated
		if (static::validate_user($email, $password, 'password'))
		{
			$new_hash = \Str::random('alnum');

			// if remember, set the cookie with login hash
			if ($remember)
				static::remember_me($new_hash);

			// perform login routine
			return static::login_routine($new_hash);
		}
		else
		{
			throw new \AuthException('email or password is invalid.');
		}
	}


	/**
	 * Logs the current user out.  Also invalidates the RememberMe.
	 *
	 * @return  void
	 */
	public static function logout()
	{
		$id = Session::get('id');

		Cookie::delete(Config::get('auth.cookie_name'));
		Session::delete('id');
		Session::delete('hash');
		Cache::delete('user-'.$id);
		Cache::delete('user_meta-'.$id);
	}


	/**
	 * creates a new user
	 *
	 * @param   string  email entered
	 * @param   string  password entered
	 * @param   string  group of the user
	 * @param   array   user information entered
	 * @return  bool
	 * @throws  AuthException
	 */
	public static function create($email=null, $password=null, $group='User', $user_meta = array())
	{
		// log the user out
		static::logout();

		// make sure vars have values
		if (empty($email) or empty($password))
		{
			throw new \AuthException('email or password can\'t be empty.');
		}

		$same_user = DB::select('id')->from('users')
						->where('email', $email)
						->limit(1)
						->execute();

		if ($same_user->count() > 0)
		{
			throw new \AuthException('email already exists, try logging in.');
		}

		$user = array(
			'email'      => $email,
			'password'   => static::encrypt_password($password),
			'hash' 		 => \Str::random('alnum'),
			'group'      => static::get_group($group),
			'created_at' => time(),
			'updated_at' => time(),
			'ip_address' => \Input::real_ip(),
		);

		// insert new user
		list($insert_id, $rows_affected) = DB::insert('users')->set($user)->execute();

		// ToDo - gravatar pull on pic from email.
		if($user_meta)
		{
			$user_meta['user_id'] 	 = $insert_id;
			$user_meta['created_at'] = $user_meta['updated_at'] = time();

			DB::insert('users_meta')->set($user_meta)->execute();
		}

		// set session variables
		Session::set('id', $insert_id);
		Session::set('hash', $user['hash']);

		return true;
	}


	/**
	 * Checks if the user is logged, confirms the role and returns data about the user
	 *
	 * @param   role of the user
	 * @return  User array
	 * @throws  AuthException
	 */
	public static function user($role='User')
	{
		$id   = Session::get('id');
		$hash = Session::get('hash');

		if (empty($id) or empty($hash))
		{
			// check if cookie exists
			if(! static::is_remembered())
				return false;
		}

		// match session data and check role:
		if ((static::validate_user($id, $hash)) and (static::validate_group($role)))
		{
			// pull all the user meta tables for the user and cache it
			try
			{
			    static::$user_meta = Cache::get('user_meta-'.$id);
			}
			catch (\CacheNotFoundException $e)
			{
				static::$user_meta = DB::select()->from('users_meta')
										->where('user_id', $id)
										->limit(1)
										->execute();

				/* ToDo - Update ORM as appropriate
				static::$user_meta = Model_Category::find()
									->related('topics')
									->order_by('priority', 'asc')
									->order_by('topics.priority', 'asc')
									->get();
				*/
				Cache::set('user_meta-'.$id, static::$user_meta);
			}

			// appending email to array;
			$user_info = static::$user_meta[0];
			$user_info['email'] = static::$user[0]['email'];

			return $user_info;
		}
		else
		{
			static::logout();
			return false;
		}
	}


	/**
	 * Generates new random password forgot_hash, to be used for resetting a user's forgotten password,
	 * should be emailed afterwards.
	 *
	 * @param   string  $email
	 * @return  string  $encoded email/$forgot_hash link
	 */
	public static function forgot_password($email=null)
	{
		// make sure vars have values
		if (empty($email))
		{
			throw new \AuthException('email can not be empty.');
		}

		$check_user = DB::select('id')->from('users')
						->where('email', $email)
						->limit(1)
						->execute();

		if ($check_user->count() > 0)
		{
			$update['forgot_hash']	= \Str::random('alnum', 32);
			$update['updated_at'] 	= time();
			$update['ip_address'] 	= \Input::real_ip();

			if(DB::update('users')->set($update)->where('id', $check_user[0]['id'])->execute())
			{
				return base64_encode($email).'/'.$update['forgot_hash'];
			}
			else
			{
				throw new \AuthException('Internal error, please try again.');
			}
		}
		else
		{
			throw new \AuthException('email does not exist, check again.');
		}
	}


	/**
	 * Update user information
	 *
	 * @param   array to update
	 * @return  boolean
	 * @throws  AuthException
	 */
	public static function update($user_meta = array())
	{
		// make sure vars have values
		if (empty($user_meta))
		{
			throw new \AuthException('nothing to update.');
		}

		$id   = Session::get('id');
		$hash = Session::get('hash');

		// match session data
		if ((static::validate_user($id, $hash)))
		{
			// update stuff
			$user_meta['updated_at'] = time();

			if(DB::update('users_meta')->set($user_meta)->where('user_id', $id)->execute())
			{
				// update done, clearing cache
				Cache::delete('user_meta-'.$id);
				return true;
			}
			else
			{
				throw new \AuthException('Internal error, please try again.');
			}
		}
		else
		{
			static::logout();
			return false;
		}
	}


	/**
	 * Change user password
	 *
	 * @param   old_password, new_password
	 * @return  boolean
	 * @throws  AuthException
	 */
	public static function change_password($old_password, $new_password)
	{
		if (empty($old_password) or empty($new_password))
		{
			throw new \AuthException('nothing to change.');
		}

		$id   = Session::get('id');
		$hash = Session::get('hash');

		// match session data
		if ((static::validate_user($id, $hash)))
		{
			// change password
			$check_old_password = DB::select('id')->from('users')
						->where('id', $id)
						->where('password', static::encrypt_password($old_password))
						->limit(1)
						->execute();

			if ($check_old_password->count() < 1)
			{
				throw new \AuthException('current password is incorrect.');
			}
			else
			{
				// ready to update
				$update['updated_at'] = time();
				$update['password']   = static::encrypt_password($new_password);

				if(DB::update('users')->set($update)->where('id', $id)->execute())
				{
					// update done
					return true;
				}
				else
				{
					throw new \AuthException('Internal error, please try again.');
				}
			}
		}
		else
		{
			static::logout();
			return false;
		}
	}




	/**
	 * Private Functions below:
	 *
	 */
	protected static function validate_user($email_or_id=null, $password_or_hash=null, $action=null)
	{
		switch($action)
		{
			case 'password':
				$user = DB::select('id','forgot_hash')->from('users')
						->where('email', $email_or_id)
						->where('password', static::encrypt_password($password_or_hash))
						->limit(1)
						->execute();
				break;

			case 'remember_me':
				$user = DB::select('id','hash','forgot_hash')->from('users')
						->where('id', $email_or_id)
						->where('hash', $password_or_hash)
						->limit(1)
						->execute();
				break;

			default:
			case 'routine':
				try
				{
			    	$user = Cache::get('user-'.$email_or_id);
				}
				catch (\CacheNotFoundException $e)
				{
					$user = DB::select('id','email','hash','forgot_hash','group')->from('users')
								->where('id', $email_or_id)
								->limit(1)
								->execute();

					Cache::set('user-'.$email_or_id, $user);
				}

				if (($user[0]['id'] == $email_or_id) and ($user[0]['hash'] == $password_or_hash))
		    	{
		    		static::$user = $user;
		    		return true;
		    	}
		    	else
		    	{
					Cache::delete('user-'.$email_or_id);
					return false;
		    	}

				break;
		}

		if($user->count() > 0)
		{
			static::$user = $user;
			return true;
		}
		else
		{
			return false;
		}
	}


	protected static function encrypt_password($password)
	{
		$_pass = str_split($password);
		$salt=null;

		// encrypts every single letter of the password
		foreach ($_pass as $_hashpass)
			$salt .= md5($_hashpass);

		return md5($salt);
	}


	protected static function remember_me($hash)
	{
		// encode the hash string and set the cookie
		return Cookie::set(
						Config::get('auth.cookie_name'),
						base64_encode(static::$user[0]['id'].':'.$hash)
					);
	}


	protected static function login_routine($hash)
	{
		// set update array
		$update = array();
		$update['hash'] = $hash;

		// if there is a password reset hash and user logs in - remove the password reset
		if (static::$user[0]['forgot_hash'])
		{
			$update['forgot_hash'] = '';
		}

		$update['updated_at'] = time();
		$update['ip_address'] = \Input::real_ip();

		if(DB::update('users')->set($update)->where('id', static::$user[0]['id'])->execute())
		{
			// set session variables
			Session::set('id', static::$user[0]['id']);
			Session::set('hash', $hash);
			Session::instance()->rotate();

			return true;
		}
		else
		{
			throw new \AuthException('Internal error, please try again.');
		}
	}


	protected static function is_remembered()
	{
		// if cookie exists
		if ($cookie = Cookie::get(Config::get('auth.cookie_name')))
		{
			list($userid, $hash) = explode(':', base64_decode($cookie));

			if (static::$user = static::validate_user($userid, $hash, 'remember_me'))
			{
				$new_hash = \Str::random('alnum');
				static::remember_me($new_hash);

				// perform login routine
				static::login_routine($new_hash);
			}
			else
			{
				//static::logout();
				return false;
			}
		}

		return false;
	}


	protected static function validate_group($role)
	{
		return static::get_group($role) == static::$user[0]['group'] ? true : false;
	}


	protected static function get_group($role)
	{
		return array_search($role, Config::get('auth.groups'));
	}

}