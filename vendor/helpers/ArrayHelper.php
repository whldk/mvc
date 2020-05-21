<?php
namespace vendor\helpers;

class ArrayHelper
{
    /**
     * @param array $array       循环的数组
     * @param array $add_array   要加入的数组
     * @param string $where_key   根据什么字段判断
     * @param string $del_key     删除指定的key
     * @param array $default     默认key
     */
    public static function array_set_col(&$array, $add_array, $where_key, $del_key = null, $default = [])
    {
        foreach ($array as $key => &$val) {
            $where_val = $val[$where_key];
            if (!$where_val || !isset($add_array[$where_val])) {
                $val += $default;
                continue;
            }
            $info = $add_array[$where_val];
            if (is_array($del_key)) {
                foreach ($del_key as $item) {
                    unset($info[$item]);
                }
            } else {
                unset($info[$del_key]);
            }
            $val += $info;
        }
    }

	public static function attach(&$array, $key, $target_array, $target_key, $attach_index, $multi = true, $default = [])
	{
		$array = (array)$array;
		$key = (string)$key;
		$target_array = (array)$target_array;
		$target_key = (string)$target_key;
		$attach_index = (string)$attach_index;
		
		if (!$array) {
			return;
		}
		
		$target_array = $multi ? self::assemble($target_array, $target_key, false) : self::index($target_array, $target_key);
		foreach ($array as &$v) {
			if (key_exists($key, $v) && isset($target_array[$v[$key]])) {
				$v[$attach_index] = $target_array[$v[$key]];
			} else {
				$v[$attach_index] = $default;
			}
		}
	}
	
	/**
	 * @param array $array
	 * @param string $key
	 * @param array $target_array
	 * @param string $target_key
	 * @param boolean $rewrite
	 * @param boolean|null $remove_key true删除原key，false删除目标key，null均保留
	 */
	public static function merge(&$array, $key, $target_array, $target_key, $rewrite = false, $default = [])
	{
		$array = (array)$array;
		$key = (string)$key;
		$target_array = (array)$target_array;
		$target_key = (string)$target_key;
		
		if (!$array || !$key || !$target_key) {
			return;
		}
		
		$target_array = array_column($target_array, null, $target_key);
		foreach ($array as &$v) {
			if (key_exists($key, $v)) {
				$keyVal = $v[$key];
				$target_v = $target_array[$keyVal] ?? $default;
				if ($rewrite) {
					$v = $target_v + $v;
				} else {
					$v += $target_v;
				}
			}
		}
	}
	
	public static function keys($array, $toStr = true)
	{
		$keys = array_keys($array);
		if ($toStr) {
			$keys = array_map('strval', $keys);
		}
		return $keys;
	}
	
	public static function rewrite(&$array1, $array2)
	{
		$rewrite = array_intersect_key($array2, $array1);
		foreach ($rewrite as $k => $v) {
			$array1[$k] = $v;
		}
	}
	
	public static function index($rows, $col)
	{
		return array_column($rows, null, $col);
	}
	
	public static function assemble($rows, $col, $keepKey = false)
	{
		$res = [];
		foreach ($rows as $k => $row) {
			if ($keepKey) {
				$res[$row[$col]][$k] = $row;
			} else {
				$res[$row[$col]][] = $row;
			}
		}
		return $res;
	}
	
	public static function assemble_col($rows, $keyCol, $valCol)
	{
		$res = [];
		foreach ($rows as $row) {
			if (isset($row[$keyCol], $row[$valCol])) {
				$res[$row[$keyCol]][] = $row[$valCol];
			}
		}
		return $res;
	}
	
	public static function diff_assoc($array1, $array2)
	{
		foreach ($array1 as $k => $v) {
			if (key_exists($k, $array2) && $array2[$k] === $v) {
				unset($array1[$k]);
			}
		}
		return $array1;
	}
	
	public static function switch_keys($array, $keyMap, $rtl = true)
	{
		foreach ($keyMap as $l => $r) {
			if ($rtl) {
				$old = $r;
				$new = $l;
			} else {
				$old = $l;
				$new = $r;
			}
			if (key_exists($old, $array)) {
				$array[$new] = $array[$old];
				unset($array[$old]);
			}
		}
		return $array;
	}
	
	public static function not_empty($key, $array)
	{
		return isset($array[$key]) && !empty($array[$key]);
	}
	
	public static function solid_value($key, $array, $default = null)
	{
		return self::not_empty($key, $array) ? $array[$key] : $default;
	}
	
	public static function pick($array, $keys)
	{
		return array_intersect_key($array, array_fill_keys($keys, null));
	}
}