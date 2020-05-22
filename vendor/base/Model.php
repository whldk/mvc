<?php
namespace vendor\base;

use vendor\hashids\Hashids;
use vendor\helpers\ArrayHelper;
use vendor\exceptions\RollbackException;
use vendor\exceptions\DbErrorException;
use vendor\exceptions\UnknownException;
use vendor\exceptions\UserErrorException;
use vendor\exceptions\InvalidConfigException;
use vendor\exceptions\InvalidParamException;

/**
 * @method \vendor\db\Db getDb()
 * @property \vendor\db\Db $db
 */

class Model
{
	use ErrorTrait, AppTrait;
	
	const ERR_EMPTY = 1;
	const ERR_VALID = 2;
	const ERR_READONLY = 3;
	const ERR_REPEAT = 4;
	const ERR_LEN = 5;
	
	const NAME = '';
	
	const PAGESIZE = 10;
	
	const ROWS_LIMIT = 1000;
	
	/**
	 * 例如
	 * [
	 * 		'auto_inc' => '_id',
	 *		'hash_id' => 'id',
	 *		'id' => ['id', 'case_id'],
	 * ]
	 * @var array
	 */
	protected static $sets = [];
	
	/**
	 * 建议为null的必填，不为null的，意味着默认
	 * [
			'exam_id' => null,
			'paper_id' => null,
			
			'user_id' => null,
			'class_id' => '',
			'grade' => null,
			
			'question_count' => 0,
			
			'error_base' => 0,
			'error_rate' => 0,
		];
	 * @var array
	 */
	protected static $fields = [];
	
	protected static $searchFilter = [];
	
	protected static $priorOrder = [];
	protected static $defaultOrder = [];
	protected static $orderFilter = [];
	
	protected static $matchFilter = [];
	protected static $mustMatchFilter = [];
	
	protected static $rangeFilter = [];

    /**
     * Model constructor.
     * @throws InvalidConfigException
     */
	public function __construct()
	{
		//hash id 需要依赖auto_inc生成
		if (!isset(static::$sets['auto_inc']) && isset(static::$sets['hash_id'])) {
			throw new InvalidConfigException();
		}
		//Model必须配置主key
		if (!isset(static::$sets['id'])) {
			throw new InvalidConfigException();
		}
	}
	
	public static function sets()
	{
		return static::$sets;
	}
	
	public static function view()
	{
		return [];
	}
	
	public static function fields($asKeys = true)
	{
		return $asKeys ? static::$fields : array_keys(static::$fields);
	}
	
	public static function listFields()
	{
		return array_keys(static::$fields);
	}
	
	public static function getFields()
	{
		return array_keys(static::$fields);
	}
	
	public static function aliasFields($alias = null, $specify = [], $fields = null, $tbl = null)
	{
		$tbl !== null ?: $tbl = static::NAME;
		$specify = (array)$specify;
		
		if (!$fields) {
			$fields = static::fields(false);
		} else {
			$fields = isset($fields[0]) ? $fields : array_keys($fields);
		}
		
		if (empty($specify)) {
			if ($alias === null) {
				foreach ($fields as $i => $k) {
					$fields[$i] = "{$tbl}.{$k}";
				}
			} else {
				foreach ($fields as $i => $k) {
					$fields[$i] = "{$tbl}.{$k} as {$alias}_{$k}";
				}
			}
		} else {
			if ($alias === null) {
				foreach ($fields as $i => $k) {
					if (in_array($k, $specify)) {
						$fields[$i] = "{$tbl}.{$k}";
					}
				}
			} else {
				foreach ($fields as $i => $k) {
					if (in_array($k, $specify)) {
						$fields[$i] = "{$tbl}.{$k} as {$alias}_{$k}";
					}
				}
			}
		}
	
		return $fields;
	}


    /**
     * @param $thisModel
     * @param $thatModel
     * @param string $thatAlias
     * @param null $thatFields
     * @param null $middleModel
     * @return array
     */
	public static function joinListFields($thisModel, $thatModel, $thatAlias = '', $thatFields = null, $middleModel = null)
	{
		$thisRawFields = array_keys($thisModel::fields());
		$thatRawFields = array_keys($thatModel::fields());
		//歧义字段
		$ambiguousFields = array_intersect($thisRawFields, $thatRawFields);
		
		//处理本表字段(初步)
		$thisFields = $thisModel::listFields();
		//他表字段（初步）
		$thatFields = isset($thatFields) ? (array)$thatFields : $thatModel::listFields();
		//重叠字段
		$dupFields = array_intersect($thisFields, $thatFields);
		
		//本表字段去歧义
		!$ambiguousFields ?: $thisFields = $thisModel::aliasFields(null, $ambiguousFields, $thisFields);
		//它表字段取别名、去歧义
		$thatAlias = (string)$thatAlias;
		$thatAlias ?: $thatAlias = $thatModel::NAME;
		!$dupFields ?: $thatFields = $thatModel::aliasFields($thatAlias, $dupFields, $thatFields);
		$ambiguousFields = array_diff($ambiguousFields, $dupFields);
		!$ambiguousFields ?: $thatFields = $thatModel::aliasFields(null, $ambiguousFields, $thatFields);
		
		$middleListFields = [];
		if ($middleModel) {
			/* @var \vendor\base\Model $middleModel */
			$middleRawFields = array_keys($middleModel::fields());
			$leftConflictFields = array_intersect($thisRawFields, $middleRawFields);
			$rightConflictFields = array_intersect($thatRawFields, $middleRawFields);
			
			!$leftConflictFields ?: $thisFields = self::aliasFields(null, $leftConflictFields, $thisFields, $thisModel::NAME);
			!$rightConflictFields ?: $thatFields = self::aliasFields(null, $rightConflictFields, $thatFields, $thatModel::NAME);
			
			$middleListFields = array_diff($middleModel::listFields(), $leftConflictFields, $rightConflictFields);
		}
		
		return array_merge($thisFields, $thatFields, $middleListFields);
	}
	
	private static $hashids;
	
	final public static function hashids()
	{
		if (!isset(self::$hashids[static::NAME])) {
			self::$hashids[static::NAME] = new Hashids(md5(static::NAME));
		}
		return self::$hashids[static::NAME];
	}
	
	public static function getUploadDir()
	{
		return static::NAME . '/' . date('Y/m');
	}
	
	/**
	 * @param array $fields 字段会在成功上传之后用相对url值填充
	 * @return boolean
	 */
	public function upload(&$files, $mimes = null, $sizes = [], $md5Fields = [], $saveNames = [], $keepFileInfo = false, $trash = true)
	{
		$upload = new UploadManager(static::getUploadDir());
		$res = $upload->upload($files, $mimes, $sizes, $md5Fields, $saveNames, $keepFileInfo, $trash);
		if ($res === false) {
			$this->errors += $upload->errors();
		}
		$files = $upload->getUrls();
		return $res;
	}
	
	public static function search(&$search, $filter = [])
	{
		$filter ?: $filter = static::$searchFilter;
		if (!$search || !$filter) {
			$search = [];
		} else {
			if (isset($filter[0])) {
				$filter = array_combine($filter, $filter);
			}
			$tmp = (array)$search;
			$search = [];
			foreach ($tmp as $fd => $v) {
				if ($v && key_exists($fd, $filter)) {
					$field = $filter[$fd];
					$end = strlen($field) - 1;
					$prefix = $field[0];
					$suffix = $field[$end];
					if ($prefix === '%' && $suffix !== '%') {
						$side = 'left';
					} elseif ($prefix !== '%' && $suffix === '%') {
						$side = 'right';
					} else {
						$side = 'both';
					}
					$field = trim($field, '%');
					$search[] = ['like', $field, $v, $side];
				}
			}
		}
		
		return;
	}
	
	public static function order(&$order, $filter = [])
	{
		$filter ?: $filter = static::$orderFilter;
		if (!$order || !$filter) {
			$order = static::$defaultOrder;
		} else {
			if (isset($filter[0])) {
				$filter = array_combine($filter, $filter);
			}
			$tmp = (array)$order;
			$order = [];
			foreach ($tmp as $fd => $direction) {
				if (!key_exists($fd, $filter)) {
					continue;
				}
				$direction = strtoupper($direction);
				$direction === 'ASC' || $direction === 'DESC' ?: $direction = 'ASC';
				$order[] = [$filter[$fd], $direction];
			}
			$order ?: $order = static::$defaultOrder;
		}
		
		if (static::$priorOrder) {
			$order = array_merge(static::$priorOrder, $order);
		}
		
		return;
	}
	
	public static function page($query, &$page, &$size, &$page_info, $fields = '*')
	{
		$total = $query->count($fields, 'count', false)->result();
		$total = $total ? (int)$total[0]['count'] : 0;
		
		$offset = 0;
		$page_info = self::pageStruct($total, $size, $page, $offset);
		
		if ($offset >= $total) {
			$page_info['_list'] = [];
		}
		
		return $offset;
	}
	
	protected static function pageStruct($total, &$size, &$page, &$offset = 0)
	{
		$size = (int)$size;
		$page = (int)$page;
		
		$size >= 1 && $size <= static::ROWS_LIMIT ?: $size =  static::PAGESIZE;
		$page >= 1 ?: $page = 1;
		
		$offset = $size * ($page - 1);
		
		return [
				'pagesize' => $size,
				'page' => $page,
				'total_page' => ceil($total/$size),
				'total' => $total,
		];
	}
	
	/**
	 * 事务内调用，并处理错误
	 * @param array|string $callback
	 * @param array $params
	 * @throws RollbackException
	 * @throws DbErrorException
	 * @throws UnknownException
	 * @throws UserErrorException
	 * @throws \Exception
	 */
	public function callInTransaction($callback, $params = [], $lock = false)
	{
		$res = null;
		$db = static::getDb();
		
		$db->begin_transaction([static::class, 'throwDbException']);
		try {
			if ($lock) {
				$db->lock();
				$locked = true;
			}
			
			$res = call_user_func_array($callback, $params);
			if ($res === null) {
				throw new UnknownException();
			}
			
			if ($lock) {
				$db->unlock();
				$locked = false;
			}
			
			$db->commit();			//commit
		} catch (RollbackException $e) {
			//回滚异常：手动深层回滚
			$db->rollback();		//rollback
		} catch (DbErrorException $e) {
			//db异常：因db错误回滚
			$db->rollback(false);	//rollback
			throw $e;
		} catch (UnknownException $e) {
			//未知异常：因未知原因抛异常回滚
			$db->rollback(false);		//rollback
			//检查是否有错误
			if (self::$last_error) {
				$this->mergeLastError();
			}
			if ($db->inTransaction()) {
				if ($this->errors) {
					throw new UserErrorException($this->errors);
				} else {
					throw $e;
				}
			} else {
				$res = null;
			}
		} catch (UserErrorException $e) {
			//因用户原因抛异常
			$db->rollback(false);		//rollback
			$this->mergeLastError();
			if ($db->inTransaction()) {
				throw $e;
			} else {
				$res = null;
			}
		} catch (\Exception $e) {
			$db->rollback(false);		//rollback
			throw $e;
		} finally {
			if (isset($locked) && $locked) {
				$db->unlock();
			}
		}
		
		return $res;
	}
	
	/**
	 * @see Model::callInTransaction
	 * 静态版
	 * @throws RollbackException
	 * @throws DbErrorException
	 * @throws UnknownException
	 * @throws UserErrorException
	 * @throws \Exception
	 */
	public static function execInTransaction($callback, $params = [], $lock = false)
	{
		$res = null;
		$db = static::getDb();
		
		$db->begin_transaction([static::class, 'throwDbException']);
		try {
			if ($lock) {
				$db->lock();
				$locked = true;
			}
			
			$res = call_user_func_array($callback, $params);
			if ($res === null) {
				throw new UnknownException();
			}
			
			if ($lock) {
				$db->unlock();
				$locked = false;
			}
			
			$db->commit();			//commit
		} catch (RollbackException $e) {
			//回滚异常：手动深层回滚
			$db->rollback();		//rollback
		} catch (DbErrorException $e) {
			//db异常：因db错误回滚
			$db->rollback(false);	//rollback
			throw $e;
		} catch (UnknownException $e) {
			//未知异常：因未知原因抛异常回滚
			$db->rollback(false);		//rollback
			if ($db->inTransaction()) {
				if (self::$last_error) {
					throw new UserErrorException(self::$last_error);
				} else {
					throw $e;
				}
			} else {
				$res = null;
			}
		} catch (UserErrorException $e) {
			//因用户原因抛异常
			$db->rollback(false);		//rollback
			if ($db->inTransaction()) {
				throw $e;
			} else {
				$res = null;
			}
		} catch (\Exception $e) {
			$db->rollback(false);		//rollback
			throw $e;
		} finally {
			if (isset($locked) && $locked) {
				$db->unlock();
			}
		}
		
		return $res;
	}
	
	public static function throwDbException()
	{
		throw new DbErrorException();
	}

    /**
     * 用于查询
     * @param $where
     * @param array $fields
     * @param int $num
     * @param array $orderby
     * @param array $groupby
     * @return array|int|null
     * @throws DbErrorException
     */
	final public static function _select($where, $fields = [], int $num = 0, array $orderby = [], array $groupby = [])
	{
		$fields ?: $fields = static::getFields() ?: '*';
		$num = (int)$num;
		//视图条件
		$view = static::view();
		
		$query = static::getDb()->select($fields)->from(static::NAME);
		
		if ($where) {
			$query->where($where);
		}
		
		if ($view) {
			$query->and_where($view);
		}
		
		if ($num > 0) {
			$query->limit($num);
		}
		
		if ($orderby) {
			$query->orderby($orderby);
		}
		
		if ($groupby) {
			$query->groupby($groupby);
		}
		
		$res = $query->result();
		
		if ($res === null) {
			self::throwDbException();
		}
		
		return $res;
	}
	

    /**
     * 查询更新
     * @param $where
     * @param array $fields
     * @param int $num
     * @param array $orderby
     * @param array $groupby
     * @return array|int|null
     * @throws DbErrorException
     */
	final public static function _select_for_update($where, $fields = [], int $num = 0, array $orderby = [], array $groupby = [])
	{
		$fields ?: $fields = static::getFields() ?: '*';
		$num = (int)$num;
		//视图条件
		$view = static::view();
		
		$query = static::getDb()->select_for_update($fields)->from(static::NAME);
		
		if ($where) {
			$query->where($where);
		}
		
		if ($view) {
			$query->and_where($view);
		}
		
		if ($num > 0) {
			$query->limit($num);
		}
		
		if ($orderby) {
			$query->orderby($orderby);
		}
		
		if ($groupby) {
			$query->groupby($groupby);
		}
		
		$res = $query->result();
		
		if ($res === null) {
			self::throwDbException();
		}
		
		return $res;
	}

    /**
     * 更新行
     * @param $where
     * @param string $col
     * @param string $indexby
     * @return array
     * @throws DbErrorException
     */
	final public static function _select_col($where, string $col, string $indexby = '')
	{
		if ($indexby) {
			$res = self::_select($where, [$col, $indexby]);
			return array_column($res, $col, $indexby);
		} else {
			$res = self::_select($where, [$col]);
			return array_column($res, $col);
		}
	}
	
	/**
	 * 不存在的话返货false
	 * @return mixed
	 */
	final public static function _select_field($where, string $col)
	{
		$res = self::_select_one($where, [$col]);
		return $res ? $res[$col] : false;
	}

    /**
     * 查询一条数据
     * @param $where
     * @param array $fields
     * @param array $orderby
     * @return array|mixed
     * @throws DbErrorException
     */
	final public static function _select_one($where, $fields = [], $orderby = [])
	{
		$res = self::_select($where, $fields, 1, $orderby);
		return $res ? $res[0] : [];
	}

    /**
     * 批量查询
     * @param array $inserts
     * @param array $fields
     * @param int $chunkSize
     * @param bool|null $ignore
     * @return int|mixed|null
     * @throws DbErrorException
     * @throws RollbackException
     * @throws UnknownException
     * @throws UserErrorException
     */
	final public static function _batch_insert(array $inserts, array $fields, int $chunkSize = 100, ?bool $ignore = null)
	{
		$inserts = (array)$inserts;
		if (!$inserts) {
			return 0;
		}
		
		$res = self::execInTransaction(function () use ($inserts, $fields, $chunkSize, $ignore) {
			$db = static::getDb();
			$inserts = array_chunk($inserts, $chunkSize);
			if (isset(static::$sets['auto_inc'])) {
				$ignore = true;
			}
			
			$res = 0;
			foreach ($inserts as $batch) {
				$tmp = $db->insert($batch, $fields, $ignore)->table(static::NAME)->result();
				$res += $tmp;
			}
			
			return $res;
		});
		
		return $res;
	}

    /**
     *  插入数据
     * @param $fields
     * @param null $dku
     * @param bool $dku_raw
     * @param null $ignore
     * @return array|int|null
     * @throws DbErrorException
     * @throws InvalidParamException
     * @throws UnknownException
     */
	final public static function _insert($fields, $dku = null, $dku_raw = false, $ignore = null)
	{
		if (isset(static::$sets['auto_inc']) && !$dku) {
			$ignore = true;
		}
		
		//视图条件
		if (($view = static::view())) {
			foreach ($view as $k => $v) {
				if (is_array($v)) {
					if (key_exists($k, $fields) && in_array($fields[$k], $v)) {
						continue;
					}
					throw new UnknownException();
				} else {
					$fields[$k] = $v;
				}
			}
		}
		
		$query = static::getDb()->insert($fields, null, $ignore)->table(static::NAME);
		
		if ($ignore === null) {
			$dku = $dku ?: static::okuFields($fields);
			if ($dku) {
				$query->on_duplicate_key_update($dku, $dku_raw);
			}
		}
		
		$res = $query->result();
		
		if ($res === null) {
			self::throwDbException();
		}
		
		return $res;
	}
	
	/**
	 * @return number
	 */
	final public static function _update($where, $vals, $limit = 0, $raw = false)
	{
		if (!$where || !$vals) {
			return 0;
		}
		
		$limit = (int)$limit;
		//视图条件
		$view = static::view();
		
		$query = static::getDb()->update(static::NAME)
			->set($vals, $raw)->where($where);
		
		if ($view) {
			$query->and_where($view);
		}
		
		if ($limit > 0) {
			$query->limit($limit);
		}
		
		$res = $query->result();
		
		if ($res === null) {
			self::throwDbException();
		}
		
		return $res;
	}
	
	final public static function _delete($where)
	{
		if (!$where) {
			return 0;
		}
		//视图条件
		$view = static::view();
		
		$query = static::getDb()->delete(static::NAME)
			->where($where);
		
		if ($view) {
			$query->and_where($view);
		}
		
		$res = $query->result();
		
		if ($res === null) {
			self::throwDbException();
		}
		
		return $res;
	}
	
	/**
	 * @return int
	 */
	public static function _count($where, $fields = '*')
	{
		//视图条件
		$view = static::view();
		
		$query = static::getDb()->count($fields)
			->table(static::NAME)
			->and_filter_where($where);
	
		if ($view) {
			$query->and_where($view);
		}
			
		$res = $query->result();
		
		if ($res === null) {
			self::throwDbException();
		}
		
		return $res ? (int)$res[0]['count'] : 0;
	}
	
	/**
	 * 增减字段
	 * @param mixed $where
	 * @param string $field
	 * @param number $num 负数为减
	 * @return boolean
	 */
	final public static function _incr($where, string $field, int $num)
	{
		if (!key_exists($field, static::$fields)) {
			throw new InvalidParamException(__METHOD__, 'field');
		}
		$val = "`{$field}`";
		$val .= $num > 0 ? ' + ' : ' - ';
		$val .= abs($num);
		
		$res = self::_update($where, [$field => $val], 0, true);
		
		return !!$res;
	}
	
	public static function general_list($matches, $ranges, $search, $order, $size, $page, $mustMatches = [])
	{
		if (!static::NAME) {
			return ['_list' => []];
		}
		
		$matchCondition = static::getMatchCondition($matches);
		$rangeCondition = static::getRangeCondition($ranges);
		static::search($search);
		
		$query = static::joinListQuery()
			->and_filter_where($matchCondition)
			->and_filter_where($rangeCondition)
			->and_filter_where($search);
		
		if ($mustMatches) {
			$mustMatchCondition = static::getMustMatchCondition($mustMatches);
			$query->and_where($mustMatchCondition);
		}
			
		$res = [];
		
		$offset = $page == 0 ? 0 : static::page($query, $page, $size, $res);  
		
		if (isset($res['_list'])) {
			return $res;
		}
		
		static::order($order);
		if ($order) {
			$query->orderby($order);
		}
		
		$res['_list'] = $query->limit($offset, $size)->result();
		
		return $res;
	}
	
	public static function one($idVals, $fields = null)
	{
		$idVals = static::primaryKeyVals($idVals);
		return $idVals ? self::_select_one($idVals, $fields ?: static::getFields()) : [];
	}
	
	public static function primaryKeys($asElements = true)
	{
		$primaryKeys = static::$sets['id'] ?? [];
		return $asElements ? $primaryKeys : array_fill_keys($primaryKeys, null);
	}
	
	public static function primaryKeyVals($vals)
	{
		if (!isset(static::$sets['id'])) {
			return [];
		}
		
		$idKeys = static::$sets['id'];
		
		if (count($idKeys) === 1 && is_scalar($vals)) {
			$id = array_fill_keys($idKeys, $vals);
		} elseif (is_array($vals)) {
			$id = array_intersect_key($vals, array_fill_keys($idKeys, null));
			if (count($id) !== count($idKeys)) {
				return [];
			}
		} else {
			return [];
		}
		
		foreach ($id as $v) {
			if (!is_scalar($v)) {
				throw new InvalidParamException(__METHOD__, 'vals');
			}
		}
		
		return $id;
	}
	
	protected static function okuFields(array $fields)
	{
		return array_diff_key($fields, self::primaryKeys(false));
	}
	
	protected static $join = [
// 			'model' => '',
// 			'on' => ['aa_id' => 'id'],
// 			'middle' => [
// 				'model' => '',
// 				'left_on' => ['xx_id' => 'id'],
// 				'right_on' => ['yy_id' => 'id'],
// 			],
// 			'alias' => '',	//非必填	
// 			'fields' => [],	//非必填
	];
	
	protected static function joinListQuery()
	{
		//视图条件
		$view = static::view();
		
		if (!($join = static::$join)) {
			$query = static::getDb()->select(static::listFields())->from(static::NAME);
			if ($view) {
				$query->and_where($view);
			}
			return $query;
		}
		
		if (!isset($join['model']) || !$join['model']) {
			throw new InvalidConfigException();
		}
		
		/* @var \vendor\base\Model $this_model */
		$this_model = static::class;
		/* @var \vendor\base\Model $that_model */
		$that_model = $join['model'];
		$this_tbl = static::NAME;
		$that_tbl = $that_model::NAME;
		
		if (isset($join['middle'], $join['middle']['model'], $join['middle']['left_on'], $join['middle']['right_on']) 
			&& $join['middle']['model'] && $join['middle']['left_on'] && $join['middle']['right_on']) {
			//有中间表
			/* @var \vendor\base\Model $middleModel */
			$middleModel = $join['middle']['model'];
			$middle_tbl = $middleModel::NAME;
			
			$leftOn = [];
			foreach ($join['middle']['left_on'] as $this_id => $middle_id) {
				$leftOn = [$this_tbl . '.' . $this_id => $middle_tbl . '.' . $middle_id];
			}
			
			$rightOn = [];
			foreach ($join['middle']['right_on'] as $middle_id => $that_id) {
				$rightOn = [$middle_tbl . '.' . $middle_id => $that_tbl . '.' . $that_id];
			}
			
			$joinFields = static::joinListFields(
					$this_model, $that_model,
					isset($join['alias']) ? $join['alias'] : null,
					isset($join['fields']) ? $join['fields'] : null,
					$middleModel
				);
			$query = static::getDb()->select($joinFields)->from($this_tbl)->join($middle_tbl, $leftOn)
				->join($that_tbl, $rightOn);
		} elseif (isset($join['on']) && $join['on']) {
			//无中间表
			$on = [];
			foreach ($join['on'] as $this_id => $that_id) {
				$on = [$this_tbl . '.' . $this_id => $that_tbl . '.' . $that_id];
			}
			$joinFields = static::joinListFields(
					$this_model, $that_model,
					isset($join['alias']) ? $join['alias'] : null,
					isset($join['fields']) ? $join['fields'] : null
				);
			$query = static::getDb()->select($joinFields)->from($this_tbl)->join($that_tbl, $on);
		} else {
			throw new InvalidConfigException();
		}
		
		//视图条件
		if ($view) {
			$this_tbl = static::NAME;
			$tmp = $view;
			$view = [];
			foreach ($tmp as $k => $v) {
				$view[$this_tbl . '.' . $k] = $v;
			}
			$query->and_where($view);
		}
		
		//分组条件
		if (isset($join['group']) && $join['group']) {
			$query->groupby($join['group']);
		}
		
		return $query;
	}
	
	public static function getMatchCondition($matches, $filter = [])
	{
		$filter ?: $filter = static::$matchFilter;
		if (!$filter) {
			return [];
		}
		
		if (isset($filter[0])) {
			$filter = array_combine($filter, $filter);
		}
		
		$condition = [];
		
		foreach ($matches as $k => $match) {
			if (!key_exists($k, $filter) || $match === null || $match === '') {
				continue;
			}
			$condition[$filter[$k]] = $match;
		}
		
		return $condition;
	}
	
	public static function getMustMatchCondition($matches, $filter = [])
	{
		$filter ?: $filter = static::$mustMatchFilter;
		if (!$filter) {
			return [];
		}
		
		if (isset($filter[0])) {
			$filter = array_combine($filter, $filter);
		}
		
		$condition = [];
		
		foreach ($matches as $k => $match) {
			if (!key_exists($k, $filter)) {
				continue;
			}
			$condition[$filter[$k]] = $match;
		}
		
		return $condition;
	}
	
	/**
	 * and 查询
	 * @param $ranges array
	 */
	public static function getRangeCondition($ranges, $filter = [])
	{
		$filter ?: $filter = static::$rangeFilter;
		if (!$filter) {
			return [];
		}
		
		if (isset($filter[0])) {
			$filter = array_combine($filter, $filter);
		}
		
		$condition = [];
		
		foreach ($ranges as $k => $range) {
			if (!key_exists($k, $filter)) {
				continue;
			}
			if (isset($range['min'])) {
				$condition[] = ['>=', $filter[$k], (int)$range['min']];
			}
			if (isset($range['max'])) {
				$condition[] = ['<=', $filter[$k], (int)$range['max']];
			}
		}
		
		return $condition;
	}
	
	/**
	 * @tutorial 仅支持单字段key关联
	 * @var array
	 */
	protected static $hasOne = [
// 	    'xxxModel' => ['key' => 'xxx', 'rel_key' => 'yyy', 'view' => [], 'equals' => []],
// 	    'yyyModel' => ['middle' => 'zzzModel', 'middle_fields' => []],
	];
	
	/**
	 * @tutorial 获取一对一映射的一行记录
	 * @param array $row
	 * @param string $model
	 * @param array $fields        []表示仅需要关联的相关key字段，null使用默认字段
	 */
	public static function hasOne($row, $model, $fields = null)
	{
	    $res = static::hasOnes([$row], $model, $fields, false, false);
	    return $res ? current($res) : [];
	}
	
	/**
	 * @tutorial 获取一对一映射的多行数据
	 * @param array $rows
	 * @param string $model
	 * @param array|null $fields   []表示仅需要关联的相关key字段，null使用默认字段
	 * @param boolean $merge       是否直接合并数据，还是返回关联的数据
	 * @param boolean $index       是否用关联key做索引，仅在$merge为false的时候生效
	 * @throws InvalidConfigException
	 * @return array
	 */
	public static function hasOnes($rows, $model, $fields = null, $merge = false, $index = true)
	{
	    $res = $merge ? $rows : [];
	    
	    if (!$rows || !isset(static::$hasOne[$model])) {
	        return $res;
	    }
	    
	    /* @var Model $model */
	    $map = static::$hasOne[$model];
	    
	    if (isset($map['middle'])) {
	        //间接关联
	        if (!in_array(self::class, class_parents($map['middle']))) {
	            throw new InvalidConfigException();
	        }
	        /* @var Model $middleModel */
	        $middleModel = $map['middle'];
	        //@todo 优化: 在中间步骤就已经无数据关联的时候，停止后续关联
	        $middleRows = static::hasOnes($rows, $middleModel, $map['middle_fields'] ?? [], $merge, $index);
			$res = $middleModel::hasOnes($middleRows, $model, $fields, true, false);
	    } elseif (isset($map['key'], $map['rel_key'])) {
	       //直接关联
	        $key = $map['key'];
	        $keyVals = array_unique(array_column($rows, $key));
	        if (!$keyVals) {
	            return $res;
	        }
	        $rel_key = $map['rel_key'];
	        if ($fields !== null) {
	        	$fields = (array)$fields;
	            $fields ?: $fields = array_merge($model::primaryKeys(), $map['fields'] ?? []);
	            in_array($rel_key, $fields) ?: $fields[] = $rel_key;
	        }
	        if (ArrayHelper::not_empty('equals', $map)) {
	        	$row = current($rows);
	        	foreach ($map['equals'] as $k => $v) {
	        		if (key_exists($k, $row)) {
	        			$map['view'][$v] = $row[$k];
	        		} else {
	        			throw new InvalidParamException(__METHOD__, 'rows');
	        		}
	        	}
	        }
	        $thatRows = $model::_select([$rel_key => $keyVals] + ($map['view'] ?? []), $fields);
	        if ($merge) {
	        	ArrayHelper::merge($res, $key, $thatRows, $rel_key, false, $map['default'] ?? []);
	        } else {
	            $res = $index ? array_column($thatRows, null, $rel_key) : $thatRows;
	        }
	    } else {
	        //配置错误
	        throw new InvalidConfigException();
	    }
	    
	    return $res;
	}
	
	protected static $hasMulti = [
// 	    'xxxModel' => ['key' => 'xxx', 'rel_key' => 'yyy', 'view' => [], 'equals' => []],
// 	    'yyyModel' => ['middle' => 'zzzModel', 'key' => 'xxx', 'rel_key' => 'yyy', 'middle_fields' => []],
	];
	
	/**
	 * @tutorial 获取一对一映射的一行记录
	 * @param array $row
	 * @param string $model
	 * @param array $fields        []表示仅需要关联的相关key字段，null使用默认字段
	 */
	public static function hasMulti($row, $model, $fields = null)
	{
	    $res = static::hasMultis([$row], $model, $fields, false, false);
	    return $res;
	}
	
	/**
	 * @tutorial 获取一对多映射的多行数据
	 * @param array $rows
	 * @param string $model
	 * @param array $fields        []表示仅需要关联的相关key字段，null使用默认字段
	 * @param boolean $merge       是否直接附加数据，还是返回关联的数据
	 * @param boolean $index       是否用关联key做聚合索引，仅在$merge为false的时候生效
	 * @throws InvalidConfigException
	 * @return array
	 */
	public static function hasMultis($rows, $model, $fields = null, $merge = false, $index = true)
	{
	    $res = $merge ? $rows : [];
	    
	    if (!$rows || !isset(static::$hasMulti[$model])) {
	        return $res;
	    }
	    
	    /* @var Model $model */
	    $map = static::$hasMulti[$model];
	    if (!isset($map['key'], $map['rel_key'])) {
	        throw new InvalidConfigException();
	    }
	    $key = $map['key'];
	    $rel_key = $map['rel_key'];
	    
	    
	    //间接关联
	    if (isset($map['middle'])) {
	        if (!in_array(self::class, class_parents($map['middle']))) {
	            throw new InvalidConfigException();
	        }
	        /* @var Model $middleModel */
	        $middleModel = $map['middle'];
	        //@todo 优化: 在中间步骤就已经无数据关联的时候，停止后续关联
	        if (isset(static::$hasMulti[$middleModel])) {
	        	$middleRows = static::hasMultis($rows, $middleModel, $map['middle_fields'] ?? [], false, false);
	            $thatRows = $middleModel::hasOnes($middleRows, $model, $fields, true, false);
	        } elseif (isset(static::$hasOne[$middleModel])) {
	        	$middleRows = static::hasOnes($rows, $middleModel, $map['middle_fields'] ?? [], false, false);
	            $thatRows = $middleModel::hasMultis($middleRows, $model, $fields, true, false);
	        }
	        if ($merge) {
	        	ArrayHelper::attach($res, $key, $thatRows, $rel_key, $map['attach'] ?? $model::NAME);
	        } else {
	            $res = $index ? ArrayHelper::assemble($thatRows, $rel_key) : $thatRows;
	        }
	    } else {
	        //直接关联
	        $keyVals = array_unique(array_column($rows, $key));
	        if (!$keyVals) {
	            return $res;
	        }
	        if ($fields !== null) {
	        	$fields = (array)$fields;
	        	//@todo 关于fields字段配置，暂且添加这一处，其他地方可以类似配置
	        	$fields ?: $fields = array_merge($model::primaryKeys(), $map['fields'] ?? []);
	            in_array($rel_key, $fields) ?: $fields[] = $rel_key;
	        }
	        if (ArrayHelper::not_empty('equals', $map)) {
	        	$row = current($rows);
	        	foreach ($map['equals'] as $k => $v) {
	        		if (key_exists($k, $row)) {
	        			$map['view'][$v] = $row[$k];
	        		} else {
	        			throw new InvalidParamException(__METHOD__, 'rows');
	        		}
	        	}
	        }
	        $thatRows = $model::_select([$rel_key => $keyVals] + ($map['view'] ?? []), $fields);
	        if ($merge) {
	        	ArrayHelper::attach($res, $key, $thatRows, $rel_key, $map['attach'] ?? $model::NAME);
	        } else {
	            $res = $index ? ArrayHelper::assemble($thatRows, $rel_key) : $thatRows;
	        }
	    }
	    
	    return $res;
	}
}