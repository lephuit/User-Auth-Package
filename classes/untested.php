

/**
 * Below has not been tested 
 */
 

	/**
	 * Starts the reset password process.  Generates the necessary password
	 * reset hash and returns the new user array.  Password reset confirm
	 * still needs called.
	 *
	 * @param   string  Login Column value
	 * @param   string  User's new password
	 * @return  bool|array
	 */
	public static function reset_password($login_column_value, $password)
	{
		// make sure a user id is set
		if (empty($login_column_value) or empty($password))
		{
			return false;
		}

		// check if user exists
		$user = static::user($login_column_value);

		// create a hash for reset_password link
		$hash = \Str::random('alnum', 24);

		// set update values
		$update = array(
			'password_reset_hash' => $hash,
			'temp_password' => $password,
			'remember_me' => '',
		);

		// if database was updated return confirmation data
		if ($user->update($update))
		{
			$update = array(
				'email' => $user->get('email'),
				'password_reset_hash' => $hash,
				'link' => base64_encode($login_column_value).'/'.$update['password_reset_hash']
			);

			return $update;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Confirms a password reset code against the database.
	 *
	 * @param   string  Login Column value
	 * @param   string  Reset password code
	 * @throws  SentryAuthException
	 * @return  bool
	 */
	public static function reset_password_confirm($login_column_value, $code, $decode = true)
	{
		if ($decode)
		{
			$login_column_value = base64_decode($login_column_value);
		}

		// make sure vars have values
		if (empty($login_column_value) or empty($code))
		{
			return false;
		}

		// if user is validated
		if ($user = static::validate_user($login_column_value, $code, 'password_reset_hash'))
		{
			// update pass to temp pass, reset temp pass and hash
			$user->update(array(
				'password' => $user->get('temp_password'),
				'password_reset_hash' => '',
				'temp_password' => '',
				'remember_me' => '',
			), false);

			return true;
		}

		return false;
	}