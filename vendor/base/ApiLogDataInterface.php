<?php
namespace vendor\base;

interface ApiLogDataInterface
{
	public function _set($md5, $name, $note, $role, $user_id, $username, $success, $source_ip);
}
