<?php
namespace vendor\base;

use \HTMLPurifier_Config;
use vendor\exceptions\InvalidConfigException;
use vendor\helpers\ArrayHelper;
use vendor\helpers\ImgHelper;
use vendor\helpers\FileHelper;
use vendor\exceptions\ServerErrorException;

class ModelFilter
{
	protected $model = null;
	protected $not_nulls = [];
	protected $before_filters = null;
	protected $after_filters = null;
	
	protected $is_before = true;
	
	public function __construct(ValidateModel $model)
	{
		$this->model = $model;
		$this->not_nulls = $model->not_nulls();
		$filters = $this->model->filters();
		!isset($filters['before']) ?: $this->before_filters = $filters['before'] ;
		!isset($filters['after']) ?: $this->after_filters = $filters['after'] ;
	}
	
	public function before_filter(&$snapshot, &$vals)
	{
		$vals = array_intersect_key($vals, $snapshot);		//过滤vals中非法fields
		$vals = ArrayHelper::diff_assoc($vals, $snapshot);	//过滤$vals中的等值

		$this->is_before = true;
		if ($this->before_filters) {
			$this->internal_filter($this->before_filters, $snapshot, $vals);
		}
		$vals = ArrayHelper::diff_assoc($vals, $snapshot);	//过滤$vals中的等值
	}
	
	public function after_filter(&$snapshot, &$vals)
	{
		$this->is_before = false;
		if ($this->after_filters) {
			$this->internal_filter($this->after_filters, $snapshot, $vals);
		}
		$vals = ArrayHelper::diff_assoc($vals, $snapshot);
	}
	
	/**
	 * 允许为null
	 */
	protected function internal_filter($filters, &$snapshot, &$vals)
	{
		foreach ($filters as $type => $filter) {
			if (!$filter) {
				continue;
			}
			switch ($type) {
				case 'b' :
				case 'i' :
				case 'f' :
				case 's' :
					$this->typeVal($type, $filter, $vals);
					break;
				case 'html' :
					$this->html($filter, $vals);
					break;
				case 'ts' :
					$this->timestamp($filter, $snapshot, $vals);
					break;
				case 'us' :
					$this->userstamp($filter, $snapshot, $vals);
					break;
				case 'img' :
					$this->img($filter, $snapshot, $vals);
					break;
				case 'file' :
					$this->file($filter, $snapshot, $vals);
					break;
				case 'map' :
					$this->map($filter, $snapshot, $vals);
					break;
				case 'ignore' :
					$this->ignore($filter, $snapshot, $vals);
					break;
				case 'json' :
					$this->json($filter, $snapshot, $vals);
					break;
			}
		}
	}
	
	protected function timestamp($filter, $snapshot, &$vals)
	{
		$now = time();
		if (isset($filter['mt'])) {
			$vals[$filter['mt']] = $now;
		}
		if (isset($filter['ct'])) {
			if (isset($snapshot[$filter['ct']]) && $snapshot[$filter['ct']]) {
				unset($vals[$filter['ct']]);
			} else {
				$vals[$filter['ct']] = $now;
			}
		}
	}
	
	protected function userstamp($filter, $snapshot, &$vals)
	{
		$model = $this->model;
		$identity = $model::getUser()->getIdentity();
		if ($identity && !$identity instanceof IdentityInterface) {
			throw new ServerErrorException();
		}
		$user_id = $identity ? $identity['id'] : '';
		
		if (isset($filter['mu'])) {
			$vals[$filter['mu']] = $user_id;
		}
		if (isset($filter['cu'])) {
			if (isset($snapshot[$filter['cu']]) && $snapshot[$filter['cu']]) {
				unset($vals[$filter['cu']]);
			} else {
				$vals[$filter['cu']] = $user_id;
			}
		}
	}
	
	protected function json($filter, &$snapshot, &$vals)
	{
		if ($this->is_before) {
			foreach ($filter as $fd) {
				!isset($snapshot[$fd]) || is_array($snapshot[$fd]) ?: $snapshot[$fd] = (array)json_decode($snapshot[$fd], true);
				!isset($vals[$fd]) || is_array($vals[$fd]) ?: $vals[$fd] = (array)json_decode($vals[$fd], true);
			}
		} else {
			foreach ($filter as $fd) {
				!isset($snapshot[$fd]) || is_string($snapshot[$fd]) ?: $snapshot[$fd] = (string)json_encode($snapshot[$fd], JSON_UNESCAPED_UNICODE);
				!isset($vals[$fd]) || is_string($vals[$fd]) ?: $vals[$fd] = (string)json_encode($vals[$fd], JSON_UNESCAPED_UNICODE);
			}
		}
	}
	
	/**
	 * 跟ModelValidator::repeat逻辑有点像
	 * @param array $filter
	 * @param array $vals
	 * @throws InvalidConfigException
	 */
	protected function ignore($filter, $snapshot, &$vals)
	{
		//规范化
		if (is_string($filter[0])) {
			$filters = [$filter];
		} elseif (is_array($filter[0])) {
			$filters = isset($filter['when']) || isset($filter['!when']) ? [$filter] : $filter;
		} else {
			throw new InvalidConfigException();
		}

		foreach ($filters as $filter) {
			if (is_array($filter[0])) {
				if (Helpers::when($filter, $vals + $snapshot) === false) {
					continue;
				}
				$filter = $filter[0];
			}
			$vals = array_diff_key($vals, array_fill_keys($filter, null));
		}
	}
	
	/**
	 * 回调过滤
	 * @param array $filters
	 * @param array $snapshot 用于联合vals获callback参数
	 * @param array $vals
	 */
	protected function map($filters, $snapshot, &$vals)
	{
		foreach ($filters as $fd => $filter) {
			//检查字段
			if (!key_exists($fd, $vals)) {
				continue;
			}
			//设置字段参数
			$filter['args'] = $filter['args'] ?? [];
			$filter['args'][] = $fd;
			//开始执行
			$filterResult = null;
			if (!Helpers::callback($filterResult, $filter, $vals + $snapshot, $this->model)) {
				continue;
			}
			//处理结果
			if (isset($filter['results'])) {
				if (!is_array($filterResult)) {
					throw new InvalidConfigException();
				}
				if (isset($filterResult[0])) {
					if (count($filter['results']) > count($filterResult)) {
						throw new InvalidConfigException();
					}
					foreach ($filter['results'] as $rfd) {
						$vals[$rfd] = array_shift($filterResult);
					}
					!$filterResult ?: $vals[$fd] = array_shift($filterResult);
				} else {
					foreach ($filter['results'] as $rfd) {
						!key_exists($rfd, $filterResult) ?: $vals[$rfd] = $filterResult[$rfd];
					}
					!key_exists($fd, $filterResult) ?: $vals[$fd] = $filterResult[$fd];
				}
			} else {
				$vals[$fd] = $filterResult;
			}
		}
		
		return true;
	}
	
	/**
	 * @param array $filter
	 * @param array $snapshot 用于获取旧url，并且进行回收
	 * @param array $vals
	 */
	protected function img($filter, $snapshot, &$vals)
	{
		if (is_array($filter[0]) && isset($filter['resample']) && $filter['resample']) {
			$filter['mime'] = ['image/jpeg', 'image/png'];
			$res = $this->file($filter, $snapshot, $vals, true);
			$files = array_intersect_key(Upload::getFiles(), Upload::getUrls(), (array)$filter['resample']);
			foreach ($files as $key => $file) {
				$resampleConfig = $filter['resample'][$key];
				if (!isset($resampleConfig['to'])) {
					continue;
				}
				$resampleConfig['zoom'] = $resampleConfig['zoom'] ?? 1;
				$resampleConfig['x'] = (int)($resampleConfig['x'] ?? 0);
				$resampleConfig['y'] = (int)($resampleConfig['y'] ?? 0);
				$resampleConfig['w'] = (int)($resampleConfig['w'] ?? 0);
				$resampleConfig['h'] = (int)($resampleConfig['h'] ?? 0);
				
				$samples = ImgHelper::resample(
						$file['path'],
						$file['type'],
						$resampleConfig['zoom'],
						$resampleConfig['x'],
						$resampleConfig['y'],
						$resampleConfig['w'],
						$resampleConfig['h'],
						$resampleConfig['to']
						);
				if ($samples) {
					//裁剪成功
					if ($snapshot[$key]) {
						//删除旧文件
						$oldFiles = Upload::urlToFile($snapshot[$key]);
						$oldFiles = ImgHelper::sampledFilePattern($oldFiles, true);
						if ($oldFiles) {
							FileHelper::unlink($oldFiles, true);
						}
					}
					FileHelper::symlink(reset($samples), $file['path']);
				} else {
					//裁剪失败，清空字段
					$vals[$key] = '';
				}
			}
		} else {
			$res = $this->file($filter, $snapshot, $vals, false);
		}
		
		return $res;
	}
	
	/**
	 * @param array $filter
	 * @param array $snapshot 用于获取旧url，并且进行回收
	 * @param array $vals
	 */
	protected function file($filter, $snapshot, &$vals, $info = false)
	{
		$mime = 'image';	//默认是图片
		$size = [];
		$md5 = [];
		$save_name = [];
		
		if (is_array($filter[0])) {
			$filter_fds = $filter[0];
			if (isset($filter['mime'])) {
				$mime = $filter['mime'];
			}
			if (isset($filter['size'])) {
				$size = $filter['size'];
			}
			if (isset($filter['md5'])) {
				$md5 = $filter['md5'];
			}
			if (isset($filter['save_name'])) {
				$save_name = $filter['save_name'];
			}
			if (isset($filter['info'])) {
				$info === true ?: $info = boolval($filter['info']);
			}
		} else {
			$filter_fds = $filter;
		}
		
		$res = null;
		
		$new_files = array_intersect_key($vals, array_fill_keys($filter_fds, null));
		$old_files = array_intersect_key($snapshot, $new_files);
		if ($old_files) {
			//更新并删除旧文件
			$res = $this->model->upload($old_files, $mime, $size, $md5, $save_name, $info, true);
		}
		$vals = array_diff_key($vals, $new_files) + $old_files;
		
		return $res;
	}
	
	protected function html($filter, &$vals)
	{
		$config = HTMLPurifier_Config::createDefault();
		if (is_array($filter[0])) {
			$filter_fds = $filter[0];
			if (isset($filter['config'])) {
				$def = $config->getHTMLDefinition(true);
				if (isset($filter['config']['elements'])) {
					foreach ($filter['config']['elements'] as $element) {
						$def->addElement($element[0], $element[1], $element[2], $element[3], $element[4]);
					}
				}
				if (isset($filter['config']['attrs'])) {
					foreach ($filter['config']['attrs'] as $attr) {
						$def->addAttribute($attr[0], $attr[1], $attr[2]);
					}
				}
			}
		} else {
			$filter_fds = $filter;
		}
		foreach ($filter_fds as $fd) {
			if (!key_exists($fd, $vals) || $vals[$fd] === null && !in_array($fd, $this->not_nulls)) {
				return;
			}
			$vals[$fd] = (string)$vals[$fd];
			!$vals[$fd] ?: $vals[$fd] = $this->model->htmlpurifier->purify($vals[$fd], $config);
		}
	}
	
	protected function typeVal($type, $filter, &$vals)
	{
		switch ($type) {
			case 'b' :
				foreach ($filter as $fd) {
					if (!key_exists($fd, $vals) || $vals[$fd] === null && !in_array($fd, $this->not_nulls)) {
						return;
					}
					$vals[$fd] = $vals[$fd] ? 1 : 0;
				}
				break;
			case 'i' :
				foreach ($filter as $fd) {
					if (!key_exists($fd, $vals) || $vals[$fd] === null && !in_array($fd, $this->not_nulls)) {
						return;
					}
					$vals[$fd] = intval($vals[$fd]);
				}
				break;
			case 'f' :
				foreach ($filter as $fd) {
					if (!key_exists($fd, $vals) || $vals[$fd] === null && !in_array($fd, $this->not_nulls)) {
						return;
					}
					$vals[$fd] = floatval($vals[$fd]);
				}
				break;
			case 's' :
				foreach ($filter as $fd) {
					if (!key_exists($fd, $vals) || $vals[$fd] === null && !in_array($fd, $this->not_nulls)) {
						return;
					}
					$vals[$fd] = htmlspecialchars(trim(strval($vals[$fd])), ENT_QUOTES | ENT_HTML401);
				}
				break;
		}
	}
	
}