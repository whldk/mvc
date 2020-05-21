<?php
namespace vendor\exceptions;

class AuthException extends HttpException
{
	public function __construct($identity = false, $message = null, $code = null, $previous = null)
	{
		$statusCode = $identity === false || $identity ? 403 : 401;
		parent::__construct($statusCode, [], $message, $code, $previous);
	}
}