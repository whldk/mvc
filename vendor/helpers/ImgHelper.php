<?php
namespace vendor\helpers;

use vendor\exceptions\InvalidConfigException;
use vendor\exceptions\UnknownException;
use vendor\exceptions\InvalidParamException;

class ImgHelper
{
	public static function sampledFilePattern($imgFile, $asString = false)
	{
		$dotPos = strrpos($imgFile, '.');
		if (!$dotPos) {
			return $asString ? '' : [];
		}
		$part1 = substr($imgFile, 0, $dotPos);
		$part2 = substr($imgFile, $dotPos);
		
		return $asString ? $part1 . '*' . $part2 : [$part1, $part2];
	}
	
	public static function resample(string $imgFile, string $mime, $zoom, int $x, int $y, int $w, int $h, array $toSizes)
	{
		$toImgs = [];
		try {
			$imgFile = realpath($imgFile);
			if (!file_exists($imgFile)) {
				throw new InvalidParamException(__METHOD__, 'imgFile');
			}
			
			$zoom = $zoom < 1 && $zoom > 0 ? (float)$zoom : 1;
			
			if (!$toSizes || !isset($toSizes[0])) {
				throw new InvalidConfigException();
			}
			
			if (!is_array($toSizes[0])) {
				$toSizes = [$toSizes];
			}
			
			if ($mime === 'image/jpeg') {
				$imgcreateFunc = 'imagecreatefromjpeg';
				$imgoutputFunc = 'imagejpeg';
				$quality = 90;
			} elseif ($mime === 'image/png') {
				$imgcreateFunc = 'imagecreatefrompng';
				$imgoutputFunc = 'imagepng';
				$quality = 8;
			} else {
				throw new InvalidParamException(__METHOD__, 'mime');
			}
			
			//获取图片信息并打开图片
			if (!($imgInfo = getimagesize($imgFile)) || !($imgHandler = $imgcreateFunc($imgFile))) {
				throw new UnknownException();
			}
			
			//处理缩放
			list($width, $height) = $imgInfo;
			if ($zoom != 1) {
				$zoomedWidth = $width * $zoom;
				$zoomedHeight = $height * $zoom;
				$zoomedImgHandler = imagecreatetruecolor($zoomedWidth, $zoomedHeight);
				if (!$zoomedImgHandler || !imagecopyresampled($zoomedImgHandler, $imgHandler, 0, 0, 0, 0, $zoomedWidth, $zoomedHeight, $width, $height)) {
					throw new UnknownException();
				}
				imagedestroy($imgHandler);
				$imgHandler = $zoomedImgHandler;
				
				$width = $zoomedWidth;
				$height = $zoomedHeight;
			}
			$w ?: $w = $width;
			$h ?: $h = $height;
			
			//处理裁剪
			list($part1, $part2) = self::sampledFilePattern($imgFile);
			if (!$part1 || !$part2) {
				throw new InvalidParamException(__METHOD__, 'imgFile');
			}
			foreach ($toSizes as $toSize) {
				$toSize = (array)$toSize;
				
				//检测裁剪字段是否合法
				if (!isset($toSize[0])) {
					throw new InvalidConfigException();
				}
				$toSize[0] = (int)$toSize[0];
				if ($toSize[0] === 0) {
					throw new InvalidConfigException();
				}
				$toSize[1] = isset($toSize[1]) ? (int)$toSize[1] : $toSize[0];
				if ($toSize[1] === 0) {
					throw new InvalidConfigException();
				}
				
				$newImgHandler = imagecreatetruecolor($toSize[0], $toSize[1]);
				if (!$newImgHandler) {
					throw new UnknownException();
				}
				imagecopyresampled($newImgHandler, $imgHandler, 0, 0, $x, $y, $toSize[0], $toSize[1], $w, $h);
				$newImgFile = $part1 . $toSize[0];
				$toSize[0] === $toSize[1] ?: $newImgFile .= 'X' . $toSize[1]; 
				$newImgFile .= $part2;
				$imgoutputFunc($newImgHandler, $newImgFile, $quality);
				
				$toImgs[] = $newImgFile;
			}
		} catch (\Exception $e) {
			//失败：删除生成的图片
			array_map('unlink', $toImgs);
			return false;
		} finally {
			//永远：删除原始上传图片
			unlink($imgFile);
		}
		
		return $toImgs;
	}
}