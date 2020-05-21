<?php
namespace controllers;

use models\IpListModel;
use vendor\base\Controller;

class IpListController extends  Controller
{
    protected $access = [
         '*' => ['admin', 'school_admin']
     ];

    protected $filter = [
		'list' => [
    		'*' => [
    				'default' => [
    					'search' => [],
    					'order' => [],
    				],
    		]
    	],
        'create' => [
	            '*' => [
	                'require' => ['byte1', 'byte2', 'byte3', 'byte4'],
	            ]
        ],
        'view' => [
        		'*' => [
        				'require' => ['from', 'to']
        		],
        ],
        'delete' => [
        		'*' => [
        				'require' => ['from', 'to']
        		],
        ],
    ];

    public function actionList()
    {
        self::page(true);

        $res = IpListModel::general_list(
        		[],
        		[],
            	$this->params['search'],
            	$this->params['order'],
            	$this->pagesize,
            	$this->page
        	);

        return $this->response($res);
    }

    public function actionView()
    {
    	$res = IpListModel::one($this->params);
        return $this->response($res);
    }

    public function actionCreate()
    {
    	//初始化主key，防止key缺失错误，并防止主key有乱数据进入
    	$this->params['from'] = -1;
    	$this->params['to'] = -1;
        $model = new IpListModel();
        $res = $model->set_one($this->params);
        return $this->response($res, $model, 201);
    }

    public function actionDelete()
    {
        $model = new IpListModel();
        $res = $model->del_one($this->params);
        return $this->response($res, $model);
    }

}