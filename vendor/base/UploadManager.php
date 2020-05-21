<?php
namespace vendor\base;

/**
 * use global Upload Component
 * @property \vendor\base\Upload $upload
 */
class UploadManager
{
	use AppTrait, ErrorTrait;
	
	const SUB_DIR_DEFAULT = 'default';
	const SUB_DIR_TRASH = 'trash';
	
	private $dir;
	
	public function __construct($dir = null)
	{
		$this->setDir($dir);
	}
	
	public function setDir($dir)
	{
		$this->dir = $dir === null ? self::SUB_DIR_DEFAULT : trim($dir, '\\/');
	}
	
	public function getDir()
	{
		return $this->dir;
	}
	
	/**
	 * $_FILES needed to be uploaded are specified by keys in param $fields ['key' => null/value]
	 * @param array $fields	指定key和可选的旧url[key=>old_url/null,...]
	 * @param array|string $mimes 指定验证mime或mime集合 mime/[mime,...]
	 * @param array $sizes 指定key和对应的大小限制[key=>size,...]
	 * @param array $md5Keys 指定保存的md5字段	[key,...]
	 * @param array $saveNames 指定另存文件名	[key => save_name,...]
	 * @param array $keepInfo 是否保留文件信息
	 * @param array $trash 是否删除旧文件
	 * @return boolean
	 */
	public function upload(
			$fields = [],
			$mimes = null,
			$sizes = [],
			$md5Keys = [],
			$saveNames = [],
			$keepInfo = false, 
			$trash = true
		)
	{
		/* @var $upload \vendor\base\Upload */
		$upload = $this->upload;

		if ($sizes) {
			$upload->setSizeLimits($sizes);
		}
		
		$upload->loadFiles($fields, $mimes, true);
		
		if ($saveNames) {
			$upload->setSaveNames($saveNames);
		}

		if ($md5Keys) {
			$upload->setMd5Keys($md5Keys);
		}
		
		$res = $upload->save($this->getDir(), !$md5Keys && !$keepInfo);
		
		if ($res === false) {
			$this->errors += $upload->errors();
		}
		
		if ($trash && $fields && ($old_urls = array_intersect_key($fields, $upload->getUrls()))) {
			foreach ($old_urls as $old_url) {
				$this->trash($old_url);
			}
		}
		
		return $res;
	}
	
	public function getUrls($key = null)
	{
		return $this->upload->getUrls($key);
	}
	
	public function getFiles($key = null)
	{
		return $this->upload->getFiles($key);
	}
	
	public static function trash($url, $permanent = true)
	{
		if (!$url) {
			return false;
		}
		
		$file = Upload::urlToFile($url);
		if (!is_file($file)) {
			return false;
		}
		
		$trashDir = Upload::realDir(self::SUB_DIR_TRASH);
		
		if ($permanent || is_dir($trashDir) === false && mkdir($trashDir, DIR_MODE, true) === false) {
			return unlink($file);
		} else {
			return rename($file, $trashDir . DIRECTORY_SEPARATOR . basename($file));
		}
	}
	
}