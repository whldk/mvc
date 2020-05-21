<?php
namespace posts\models;

use user\models\AdminModel;
use vendor\base\ValidateModel;
use vendor\base\Validators;

/**
 * categiry的status不影响post的status
 */
class PostModel extends ValidateModel
{
	const NAME = 'post';
	
	const STATUS_UNPUB = 0;		//means unpublished
	const STATUS_PUB = 1;
	
	const LENGTH_ORDER = 5;
	const CEIL_ORDER = 10000;
	const LENGTH_ID = 10;
	const LENGTH_FINAL_ORDER = 16;
	
	public static $statuses = [
			self::STATUS_UNPUB,
			self::STATUS_PUB,
	];
	
	protected static $sets = [
			'auto_inc' => '_id',
			'hash_id' => 'id',
			'id' => ['id'],
	];
	
	protected static $fields = [
			'id' => null,
			'cid' => null,
			'link' => '',
            'pic' => '',
			'thumbnail' => null,
			'title' => '',
			'digest' => '',
			'content' => '',
            'document' => '',       //附件
			'view_count' => 0,
			'status' => self::STATUS_UNPUB,
			'pending' => 0,
			'top' => null,
			'order' => 0,
			'created_at' => null,
			'updated_at' => null,
			'author_id' => null,
			'final_order' => null,
            'size'  => null,            //文件大小字段
            'download_count' => null    //下载次数
	];
	
	protected static $filters = [
			'before' => [
					'b' => ['top', 'pending'],
					's' => ['title', 'digest'],
					'img' => ['thumbnail','pic'],
					'html' => [
					    ['content'],
                        'config' => [
                            [
                                'video',
                                'Inline',
                                'Flow',
                                [],
                                ['class' => 'Class', 'controls' => 'Enum', 'preload' => 'Bool', 'width' => 'Number', 'height' => 'Number', 'src' => 'URI']
                            ]
                        ]
                    ],
					'i' => ['status', 'order'],
					'ts' =>  ['ct' => 'created_at', 'mt' => 'updated_at'],
					'ignore' => ['view_count', 'status', 'pending', 'top', 'download_count'],
			]
	];
	
	protected static $validates = [];
	
	public static function validates()
	{
		if (!self::$validates) {
			self::$validates = [
					'require' => ['cid', 'title'],
					'readonly' => ['author_id'],
					'exist' => [
							'cid' => [
									'model' => PostCategoryModel::class,
									'targets' => ['cid' => 'id'],
									'condition' => [
// 											['=', 'link', null], 
											['level' => -1],
											'status' => PostCategoryModel::STATUS_ACTIVE
									]
							],
					],
					'number' => [
							'order' => ['min' => -9999, 'max' => +9999],
					],
					'string' =>  [
							'title' => ['min' => 1, 'max' => 100, 'truncate' => false],
							'digest' => ['min' => 1, 'max' => 200, 'truncate' => false],
							'content' => ['min' => 0, 'max' => 80000, 'truncate' => false]
					],
					'url' => [
							'link'
					],
					'range' => [
							'status' => self::$statuses	
					],
			];
			self::orderValidates(self::$validates);
		}
		return self::$validates;
	}
	
	protected static $hasOne = [
			AdminModel::class => [
					'key' => 'author_id',
					'rel_key' => 'id',
					'fields' => [
							'username',
							'realname'
					],
					'default' => [
							'username' => '',
							'realname' => '',
					]
			],
	];
	
	protected function updates_of_insert($fields, $primaryKeyVals, $_id = null)
	{
		return ['final_order' => self::generate_final_order(0, $fields['order'], $_id)];
	}
	
	protected function after_update($res, $primaryKeyVals, $snapshot, $vals)
	{
		if ($res && isset($vals['order'])) {
			$order = self::generate_final_order(0, $vals['order'], 0);
			$order = substr($order, 1, self::LENGTH_ORDER);
			
			$_id_start = 1 + self::LENGTH_ORDER + 1;
			$_id_length = self::LENGTH_ID;
			
			$res = self::_update($primaryKeyVals, [
					'order' => $vals['order'],	//再次更新，防止两次不一致
					'final_order' => "CONCAT(SUBSTR(`final_order`,1,1), '{$order}', SUBSTR(`final_order`,{$_id_start},{$_id_length}))"
			], 0, true);
		}
		return $res;
	}
	
	protected static function generate_final_order($top, $order, $_id)
	{
		$top = $top == 1 ? '1' : '0';
		$order = (int)$order;
		$_id = (int)$_id;
		
		if ($order > 0) {
			$order = self::CEIL_ORDER - $order;
			$order = '0' . str_pad($order, self::LENGTH_ORDER - 1, '0', STR_PAD_LEFT);
		} else {
			$order = - $order;
			$order = '1' . str_pad($order, self::LENGTH_ORDER - 1, '0', STR_PAD_LEFT);
		}
		
		$_id = str_pad($_id, self::LENGTH_ID, '0', STR_PAD_LEFT);
		
		return $top . $order . $_id;
	}
	
	public static function _get($id, $status = null, $pending = null, $fields = null)
	{
		$where = ['id' => $id];
		$status === null ?: $where['status'] = $status;
		$pending === null ?: $where['pending'] = $pending;
		$res = self::_select($where, $fields);
		return $res;
	}
	
	public function edit($id, $vals = [])
	{
		$res = $this->internal_set(['id' => $id], $vals);
		return $res;
	}
	
	public function del($id)
	{
		$res = $this->internal_set(['id' => $id], null);
		return $res;
	}

	public static function incrViewCount($id, $key = 'view_count')
	{
		return self::_incr(['id' => $id], $key, 1);
	}

	public function publish($id)
	{
		$res = self::_update(['id' => $id], ['status' => self::STATUS_PUB, 'pending' => 0]);
		return $res;
	}

	public function unPublish($id)
	{
		$res = self::_update(['id' => $id], ['status' => self::STATUS_UNPUB, 'pending' => 0]);
		return $res;
	}
	
	public function pend($id, $status)
	{
		$res = self::_update(['id' => $id, ['<>', 'status', $status]], ['pending' => 1]);
		return $res;
	}
	
	public function top(string $id, $cancel = false)
	{
		$length = self::LENGTH_ORDER + self::LENGTH_ID;
		if ($cancel) {
			$top = 0;
			$topStr = "'0'";
		} else {
			$top = 1;
			$topStr = "'1'";
		}
		
		$res = self::_update(['id' => $id], [
				'top' => $top,
				'final_order' => "CONCAT({$topStr}, SUBSTR(`final_order`,2,{$length}))"
		], 0, true);
		
		return $res;
	}
	
	protected function internal_validate($validates, $exec, $fields, &$vals)
	{
//		if ($exec === 'insert') {
//			$tmp = array_intersect_key($vals, ['link' => null, 'content' => null]);
//		} else {
//			$tmp = array_intersect_key($vals, ['link' => null, 'content' => null])
//				+ array_intersect_key($fields, ['link' => null, 'content' => null]);
//		}
//
//		$valid = Validators::requireValidate(['link'], $tmp, []) === true
//			|| Validators::requireValidate(['content'], $tmp, []) === true;
//		if ($valid === false) {
//			return $this->addError(['link', 'content'], self::ERR_EMPTY);
//		}
		
		return parent::internal_validate($validates, $exec, $fields, $vals);
	}
	
	public function recursive_list($cid, $status, $pend, $search = [], $order = [], $size = null, $page = 0)
	{
		if ($cid !== null) {
			$cids = PostCategoryModel::getAllLeafCids($cid);
		} else {
			$cids = $cid;
		}
		return $this->_list($cids, $status, $pend, $search, $order, $size, $page);
	}
	
	protected static $searchFilter = ['title' => 'title'];
	protected static $defaultOrder = [['final_order', 'desc']];
	protected static $orderFilter = ['order' => 'order', 'updated_at' => 'updated_at'];
	protected static $matchFilter = ['cid', 'status', 'pending'];
	
	public static function _list($cid, $status, $pending, $search = [], $order = [], $size = null, $page = 0)
	{
        $res = self::general_list(@compact(self::$matchFilter), [], $search, $order, $size, $page);
        return $res;
	}
	
	public static function get_adjacent($cid, $final_order, $status = self::STATUS_PUB)
	{
		$where = [];
		$cid === null ?: $where['cid'] = $cid;
		$status === null ?: $where['status'] = $status;
		
		$last = self::_select_one(
				$where + [['>', 'final_order', $final_order]],
				['id', 'link', 'title'],
				['final_order', 'asc']
		);
		$next = self::_select_one(
				$where + [['<', 'final_order', $final_order]],
				['id', 'link', 'title'],
				['final_order', 'desc']
		);
		
		return [$last, $next];
	}
	
	public static function listFields()
	{
		$fields = self::fields();
		unset($fields['content']);
		return array_keys($fields);
	}

	public function downLoad($url)
    {
        $res = static::getDb()->select(['title', 'document'])->from(self::NAME)->where(['LIKE', 'document', $url])->result();
        $document = @json_decode($res[0]['document'], true);
        $document = @$document['urls'];
        if ($document) {
            $file_name = array_search($url, $document);
            $file_url = $document[$file_name];
            $file_ext = explode(".", $file_url);
            $download_name = $file_name . '.' . end($file_ext);
            $file = @fopen($file_url , "r");
            if ($file) {
                $content = "";
                while (!feof($file)) {//测试文件指针是否到了文件结束的位置
                    $data = fread($file, 1024);
                    $content .= $data;
                }
                fclose($file);
                $filesize = strlen($content);
                header('Accept-Ranges: bytes');
                header('Accept-Length: ' . $filesize);
                header('Content-Transfer-Encoding: binary');
                header('Content-type: application/octet-stream');
                header('Content-Disposition: attachment; filename=' . $download_name);
                header('Content-Type: application/octet-stream; name=' . $download_name);
                echo $content;
            } else {
                echo "文件不存在";
            }
        }
    }
}