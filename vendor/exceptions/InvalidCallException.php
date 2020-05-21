<?php
namespace vendor\exceptions;

/**
 * InvalidConfigException
 */
class InvalidCallException extends ServerErrorException
{
	public function __construct($message = null, $code = null, $previous = null)
	{
		$message ?: $message = 'invalid call';
		
		parent::__construct($message, $code, $previous);
	}
}