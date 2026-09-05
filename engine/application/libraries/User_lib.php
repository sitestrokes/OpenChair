<?php



defined('BASEPATH') OR exit('No direct script access allowed');


class User_lib
{
	public function __construct()
	{
		
		$this->load->model('users/Usermodel');
	}

	/**
	 * __call
	 *
	 * Acts as a simple way to call model methods without loads of stupid alias'
	 *
	 * @param string $method
	 * @param array  $arguments
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function __call($method, $arguments)
	{
		// echo $method;exit;

		if (!method_exists( $this->Usermodel, $method) )
		{
			throw new Exception('Undefined method User_lib::' . $method . '() called');
		}
		return call_user_func_array( [$this->Usermodel, $method], $arguments);
	}



	/**
	 * __get
	 *
	 * Enables the use of CI super-global without having to define an extra variable.
	 *
	 * I can't remember where I first saw this, so thank you if you are the original author. -Militis
	 *
	 * @param    string $var
	 *
	 * @return    mixed
	 */
	public function __get($var)
	{
		return get_instance()->$var;
	}
}


?>