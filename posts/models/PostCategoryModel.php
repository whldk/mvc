<?php
namespace posts\models;

use vendor\helpers\ArrayHelper;
use common\models\CategoryModel;

/**
 * 最后一层分类level=-1?
 */
class PostCategoryModel extends CategoryModel
{
	const NAME = 'post_category';
	
	protected static $fields = [
			'id' => null,
			'pid' => '',
			'level' => -1,
			'link' => '',
			'name' => null,
			'order' => 0,   //自动排序
            'sort' => 0,    //手动排序
            'template' => null,
            'icon' => '',
			'status' => self::STATUS_ACTIVE,
			'section' => null,
			'section_nav' => 0,	//是否是导航栏
            'description' => null,
	];
	
	protected static $filters = [
			'before' => [
					'b' => ['section_nav'],
					's' => ['pid', 'name', 'section', 'description'],
					'i' => ['level', 'status', 'sort'],
                    'img' => ['icon'],
					'ignore' => ['order'],
			],
	];
	
	protected static $validates = [];
	
	public static function validates()
	{
		if (!self::$validates) {
			self::$validates = parent::validates();
			self::$validates = [
					'string' =>  [
							'name' => ['min' => 0, 'max' => 50, 'truncate' => false],
							'section' => ['min' => 0, 'max' => 20, 'truncate' => false],
                            'description' => ['min' => 0, 'max' => 100, 'truncate' => false],
					],
					'url' => [
							//'link'
					]
			] + self::$validates;
			self::orderValidates(self::$validates);
		}
		return self::$validates;
	}
	
	protected static $constraints = [
			'id' => [
					[
							'model' => self::class,
							'targets' => ['id' => 'pid'],
							'!when' => ['level' => -1],
					],
					[
							'model' => PostModel::class,
							'targets' => ['id' => 'cid'],
							'when' => ['level' => -1, 'link' => null],
					],
			]
	];
	
	public static function get_by_section($section, $limit = null, $status = self::STATUS_ACTIVE)
	{
		$where = ['section' => $section];
		$status === null ?: $where['status'] = $status;
		
		$res = self::_select($where, self::getFields(), $limit, [['order', 'ASC']]);
		
		return $res;
	}
	
	public static function get_section_nav($limit = null, $status = self::STATUS_ACTIVE, $all = false)
	{
		$where = ['section_nav' => 1];
		//$status === null ?: $where['status'] = $status;
		
		$fields = self::getFields();
		$order = [['sort', 'ASC']];
		
		$res = self::_select($where, $fields, $limit, $order);
		
		if ($all && $res) {
			foreach ($res as &$nav_item) {
				$where = ['pid' => $nav_item['id']];
				//$status === null ?: $where['status'] = $status;
				$nav_item['sub_categories'] = self::get_by_pid($where, $fields, $order, true);
			}
		}
		
		return $res;
	}
	
	/**
	 * 获取某一分类的所有父类,含自身
	 * @param string $id
	 * @param array $crumbs 面包屑路径
	 */
	public static function set_bread_crumbs($id, &$crumbs)
	{
		$id = (string)$id;
		$slice = self::_get($id, ['id', 'pid', 'level', 'name', 'link', 'status', 'description']);
		
		if ($slice) {
			array_unshift($crumbs, $slice[0]);
			if ($slice[0]['pid']) {
				return self::set_bread_crumbs($slice[0]['pid'], $crumbs);
			}
		}
		$crumbs = ArrayHelper::index($crumbs, 'id');
		return;
	}
	
	/**
	 * 获取某一顶级下面的分类树
	 * 并且，延长面包屑到底层
	 */
	public static function get_tree($pid, $status = null, $order = [], &$breadCrumbs = [])
	{
		$fields = ['id', 'pid', 'level', 'name', 'link', 'status', 'description'];
		$res = parent::getByPid($pid, $status, $order, true, $fields, $breadCrumbs);
		return $res;
	}
}