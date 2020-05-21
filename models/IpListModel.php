<?php
namespace models;

use vendor\base\ValidateModel;
use vendor\base\IpListInterface;

class IpListModel extends ValidateModel implements IpListInterface
{
	const NAME = 'ip_list';
	
	const IPV4_LEN = 32;
	
	protected static $sets = [
			'id' => ['from', 'to'],
	];
	
	protected static $fields = [
			'from' => null,
			'to' => null,
			'byte1' => null,
			'byte2' => null,
			'byte3' => null,
			'byte4' => null,
			'mask_len' => 32,
			'note' => '',
			'updated_at' => null,
	];
	
	protected static $filters = [
			'before' => [
					'i' => ['byte1', 'byte2', 'byte3', 'byte4', 'mask_len'],
					's' => ['note'],
					'ts' =>  ['mt' => 'updated_at'],
			],
			'after' => [
					'map' => [
							'from' => [
									'callback' => [self::class, 'getFrom'],
									'args' => ['byte1', 'byte2', 'byte3', 'byte4', 'mask_len'],
							],
							'to' => [
									'callback' => [self::class, 'getTo'],
									'args' => ['from', 'mask_len'],
							],
					],
			],
	];
	
	protected static $validates = [
			'require' => ['byte1', 'byte2', 'byte3', 'byte4'],
			'readonly' => ['from', 'to'],
			'number' => [
					'byte1' => ['min' => 0, 'max' => 255],
					'byte2' => ['min' => 0, 'max' => 255],
					'byte3' => ['min' => 0, 'max' => 255],
					'byte4' => ['min' => 0, 'max' => 255],
					'mask_len' => ['min' => 0, 'max' => 32],
			],
			'string' => [
					'note' => ['min' => 0, 'max' => 255],
			],
	];
	
	public static function getFrom($byte1, $byte2, $byte3, $byte4, $mask_len)
	{
		$bytes = [$byte1, $byte2, $byte3, $byte4];
		$bytes_len = intval($mask_len / 8);
		$bits_len = $mask_len % 8;
		
		$from = 0;
		$i = 0;
		if ($bytes_len) {
			for (; $i < $bytes_len; $i++) {
				$from += $bytes[$i] << 24 - 8 * $i;
			}
		}
		
		if ($bits_len) {
			$from += $bytes[$i] >> (8 - $bits_len) << ((8 - $bits_len) + (24 - 8 * $i));
		}
		
		return $from;
	}
	
	public static function getTo($from, $mask_len)
	{
		return $mask_len == 32 ? $from : $from + ((1 << (32 - $mask_len)) - 1);
	}
	
	/**
	 * @param array|null $vals
	 */
	public function _set($from, $to, $vals)
	{
		$id = compact(self::$sets['id']);
		$res = $this->internal_set($id, $vals);
		return $res;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \vendor\base\IpListInterface::black()
	 */
	public function black($ip = null)
	{
		$where = [];
		
		if ($ip) {
			$ipLong = ip2long($ip);
			$where = [['<=', 'from', $ipLong], ['>=', 'to', $ipLong]];
		}
		
		$list = self::_select_one($where, ['from', 'to']);
		
		return !!$list;
	}
	
	public function white($ip = null)
	{
		return [];
	}
}