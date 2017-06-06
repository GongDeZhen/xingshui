<?php
class Service_Benefit extends Service
{
    # 获取匹配的优惠数据
    function getBenefits($goods = []){
        $goods_info = $goods['goods'];
        $uid = $goods['uid'];
        $mock = false;
        if($mock){
            return [
                'list' => [
                    [
                        'name' => '蒙牛5.1促销',
                        'offcut' => 200,
                        'type' => 0,
                        'related_good_ids' => [
                            [
                                'id' => 1,
                                'name' => '七彩炫',
                                'img' => 'https://ss2.bdstatic.com/70cFvnSh_Q1YnxGkpoWK1HF6hhy/it/u=3694220368,900661724&fm=23&gp=0.jpg',
                                'standard' => '10支'
                            ],
                            [
                                'id' => 2,
                                'name' => '七彩炫',
                                'img' => 'https://ss2.bdstatic.com/70cFvnSh_Q1YnxGkpoWK1HF6hhy/it/u=3694220368,900661724&fm=23&gp=0.jpg',
                                'standard' => '10支'
                            ],
                            [
                                'id' => 3,
                                'name' => '七彩炫',
                                'img' => 'https://ss2.bdstatic.com/70cFvnSh_Q1YnxGkpoWK1HF6hhy/it/u=3694220368,900661724&fm=23&gp=0.jpg',
                                'standard' => '10支'
                            ],
                        ]
                    ],
                    [
                        'name' => '蒙牛6.1促销',
                        'offcut' => 100,
                        'type' => 0,
                        'related_good_ids' => [
                            [
                                'id' => 1,
                                'name' => '七彩炫',
                                'img' => 'https://ss2.bdstatic.com/70cFvnSh_Q1YnxGkpoWK1HF6hhy/it/u=3694220368,900661724&fm=23&gp=0.jpg',
                                'standard' => '10支'
                            ],
                            [
                                'id' => 2,
                                'name' => '七彩炫',
                                'img' => 'https://ss2.bdstatic.com/70cFvnSh_Q1YnxGkpoWK1HF6hhy/it/u=3694220368,900661724&fm=23&gp=0.jpg',
                                'standard' => '10支'
                            ],
                            [
                                'id' => 3,
                                'name' => '七彩炫',
                                'img' => 'https://ss2.bdstatic.com/70cFvnSh_Q1YnxGkpoWK1HF6hhy/it/u=3694220368,900661724&fm=23&gp=0.jpg',
                                'standard' => '10支'
                            ],
                        ]
                    ],
                    [
                        'name' => '伊利5.1促销',
                        'type' => 1,
                        'gifts' => [
                            [
                                'id' => '101',
                                'name' => '糯米糍',
                                'img' => 'https://ss2.bdstatic.com/70cFvnSh_Q1YnxGkpoWK1HF6hhy/it/u=3694220368,900661724&fm=23&gp=0.jpg',
                                'standard' => '10支',
                                'count' => 2,
                                'price' => 100
                            ]
                        ],
                        'related_good_ids' => [
                            [
                                'id' => 1,
                                'name' => '七彩炫',
                                'img' => 'https://ss2.bdstatic.com/70cFvnSh_Q1YnxGkpoWK1HF6hhy/it/u=3694220368,900661724&fm=23&gp=0.jpg',
                                'standard' => '10支'
                            ],
                            [
                                'id' => 2,
                                'name' => '七彩炫',
                                'img' => 'https://ss2.bdstatic.com/70cFvnSh_Q1YnxGkpoWK1HF6hhy/it/u=3694220368,900661724&fm=23&gp=0.jpg',
                                'standard' => '10支'
                            ],
                            [
                                'id' => 3,
                                'name' => '七彩炫',
                                'img' => 'https://ss2.bdstatic.com/70cFvnSh_Q1YnxGkpoWK1HF6hhy/it/u=3694220368,900661724&fm=23&gp=0.jpg',
                                'standard' => '10支'
                            ],
                        ]
                    ],
                ],
            ];
        }
        $all_benefits = $this -> allBenefits();
        // Util:dump($all_benefits);
        // Util::dump($goods_info);
        $user_info = Service::getInstance('user')->getCompanyInfo($uid);
        // Util:dump($user_info);
        $order_dict = [
            'all' => [],
            'brand' => [],
            'class' => [],
            'brand-class' => [],
            'good' => [],
            'sku' => [],
        ];
        $good_service = Service::getInstance('goods');
        foreach($goods_info as $good){
            $good_info = $good_service -> goodsInfoSimple($good['id']);
            // Util::dump($good_info);
            foreach(array_keys($order_dict) as $key){
                if(!isset($order_dict[$key][$good_info[$key]])){
                    $order_dict[$key][$good_info[$key]] = [
                        'goods' => []
                    ];
                }
                array_push($order_dict[$key][$good_info[$key]]['goods'], $good + $good_info);
            }
        }
        // Util::dump($order_dict);
        $matched_benefits = [];
        Util::write_log('进行满减满赠匹配, good_info: '.json_encode($good_info,JSON_UNESCAPED_UNICODE).'; user_info: '.json_encode($user_info,JSON_UNESCAPED_UNICODE));
        foreach($all_benefits as $benefit){
            if($ret = $this->benefitMatch($benefit, $order_dict[$benefit['type']],$user_info)){
                // var_dump($ret);
                array_push($matched_benefits,$ret);
            }
        }
        return $matched_benefits;
    }
    # 判断order_info中是否有key和规则的key匹配
    function order_info_has_key($order_info, $key, $benefit_type){
        $return_order_key = [];
        if('good' == $benefit_type || 'sku' == $benefit_type){
            foreach(array_keys($order_info) as $order_key){
                if(preg_match('/'.implode(".*",explode("-",$key)).'/', $order_key) > 0){
                    array_push($return_order_key, $order_key);
                }
            }
        }else{
            if(isset($order_info[$key])){
                array_push($return_order_key, $key);
            }
        }
        return $return_order_key;
    }

    # 校验一组满减满赠规则是否和订单相匹配
    function benefitMatch($benefit, $order_info, $user_info){
        $matched = false;
        // Util::write_log('尝试匹配规则,benefit: '.json_encode($benefit,JSON_UNESCAPED_UNICODE).'; order_info:'.json_encode($order_info, JSON_UNESCAPED_UNICODE));
        # 规则指定了仓库,则仓库和用户不匹配直接pass
        if('' != $benefit['warehouse']){
            if($benefit['warehouse'] != $user_info['warehouse']){
                return null;
            }
        }
        # 规则指定了city,则city和用户不匹配直接pass
        if('' != $benefit['city']){
            if($benefit['city'] != $user_info['city']){
                return null;
            }
        }
        # 规则指定了省,则省和用户不匹配直接pass
        if('' != $benefit['province']){
            if($benefit['province'] != $user_info['province']){
                return null;
            }
        }
        // Util::dump($benefit);
        if(strtotime("now") < strtotime($benefit['begin_at'] . ' 00:00:00') || strtotime("now") > strtotime($benefit['end_at'] . ' 23:59:59')){return null;} # 不在活动期间返回null
        $matched_goods = [];
        $limit = $benefit['min_val'];
        $matched_count = 0;
        switch($benefit['func']){
            case 'count': # 总数达标
                $count = 0;
                # 遍历该规则的条件
                foreach($benefit['list'] as $one_rule){
                    # 如果规则key在订单信息中存在,则视情况计数
                    // if(isset($order_info[$one_rule['key']])){
                    $order_keys = $this->order_info_has_key($order_info, $one_rule['key'], $benefit['type']);
                    if(count($order_keys) > 0){
                        foreach($order_keys as $order_key){
                            # 该维度下符合当前rule的limit的符合条件
                            foreach($order_info[$order_key]['goods'] as $good){
                                $single_price = $good['price'];
                                if($one_rule['limit'] <= $single_price && $one_rule['unit'] == $good['unit']){
                                    $count += $good['count'];
                                    array_push($matched_goods, $good);
                                }
                            }
                        }
                    }
                }
                if($limit <= $count){
                    $matched_count = floor($count / $limit);
                    $matched = true;

                }else{
                    $matched = false; 
                }
                break;
                // $order_info[]
            case 'amount':
                $amount = 0;
                # 遍历该规则的条件
                foreach($benefit['list'] as $one_rule){
                    $order_keys = $this->order_info_has_key($order_info, $one_rule['key'], $benefit['type']);
                    if(count($order_keys) > 0){
                        foreach($order_keys as $order_key){
                            # 该维度下符合当前rule的limit的符合条件
                            foreach($order_info[$order_key]['goods'] as $good){
                                $single_price = $good['price'];                            
                                // if($one_rule['limit'] <= $single_price && $one_rule['unit'] == $good['unit']){
                                if($one_rule['limit'] <= $single_price){ # 金额匹配不需要规格相同
                                $amount += $good['price'] * $good['count'];
                                array_push($matched_goods, $good);
                                }
                            }
                        }
                    }
                }
                if($limit <= $amount){
                    $matched_count = floor($amount / $limit);
                    $matched = true; 
                }else{
                    $matched = false; 
                }
                break;
            case 'per_count':
                # 遍历该规则的条件
                foreach($benefit['list'] as $one_rule){
                    $count = 0;
                    $order_keys = $this->order_info_has_key($order_info, $one_rule['key'], $benefit['type']);
                    if(count($order_keys) > 0){
                        foreach($order_keys as $order_key){
                            # 该维度下符合当前rule的limit的符合条件
                            foreach($order_info[$order_key]['goods'] as $good){
                                $single_price = $good['price'];                            
                                if($one_rule['limit'] <= $single_price && $one_rule['unit'] == $good['unit']){
                                $count += $good['count'];
                                array_push($matched_goods, $good);
                                }
                            }
                            if($limit <= $count){
                                $matched_count = floor($count / $limit);
                                $matched = true; 
                            }else{
                                $matched = false;
                                # 有一个条件不符合,这个订单就和这个优惠不match
                                break;
                            }
                        }
                    }
                }
                break;
            case 'per_amount':
                # 遍历该规则的条件
                foreach($benefit['list'] as $one_rule){
                    $amount = 0;
                    $order_keys = $this->order_info_has_key($order_info, $one_rule['key'], $benefit['type']);
                    if(count($order_keys) > 0){
                        foreach($order_keys as $order_key){
                            # 该维度下符合当前rule的limit的符合条件
                            foreach($order_info[$order_key]['goods'] as $good){
                                $single_price = $good['price'];                            
                                if($one_rule['limit'] <= $single_price && $one_rule['unit'] == $good['unit']){
                                $amount += $good['price'];
                                array_push($matched_goods, $good);
                                }
                            }
                            if($limit <= $amount){
                                $matched_count = floor($amount / $limit);
                                $matched = true; 
                            }else{
                                $matched = false;
                                # 有一个条件不符合,这个订单就和这个优惠不match
                                break;
                            }
                        }
                    }
                }
                break;
        }
        if(!$matched){return null;}
        $ret = [
                'name' => $benefit['disp'],
                'logo' => $benefit['logo'],
                'related_good_ids' => $matched_goods,
        ];
        if('money' == $benefit['benefits']['type']){
            $ret['type'] = 0;
            $ret['offcut'] = $benefit['benefits']['val'];
        }
        if('gifts' == $benefit['benefits']['type']){
            $ret['type'] = 1;
            $ret['gifts'] = [];
            foreach($benefit['benefits']['val'] as $gift){
                $gift['count'] = $gift['count'] * $matched_count;
                array_push($ret['gifts'], $gift);
            }
        }
        Util::write_log('匹配满减满赠规则成功,benefit: '.json_encode($benefit,JSON_UNESCAPED_UNICODE).'; order_info: '.json_encode($order_info,JSON_UNESCAPED_UNICODE).'; user_info: '.json_encode($user_info,JSON_UNESCAPED_UNICODE));
        return $ret;
    }

    # 获取所有优惠规则, $type为优惠类型,包括all,brand,class,good,sku
    function allBenefits($type = 'all'){
        $mock = false;
        if($mock){
            return [
                [
                    'province' => '北京',
                    'city' => '怀柔',
                    'warehouse' => '北京市怀柔区(程宫)-1库-10018',
                    'type' => 'brand',
                    'disp' => '冰团好礼送',
                    'logo' => 'https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1496062375&di=ebededc00513bf2550ee6b2341187257&imgtype=jpg&er=1&src=http%3A%2F%2Fimg1.juimg.com%2F150628%2F330456-15062Q04G188.jpg',
                    'func' => 'count', # 总数
                    'begin_at' => '2017-05-01',
                    'end_at' => '2017-06-01',
                    'list' => [
                        [
                            'key' => '蒙牛',
                            'limit' => 1, # 单价区间
                            'unit' => '箱'
                        ],
                        [
                            'key' => '伊利',
                            'limit' => 1, # 单价区间
                            'unit' => '箱'
                        ]
                    ],
                    'min_val' => 5,
                    'benefits' => [
                        'type' => 'money',
                        'val' => '100'
                    ]
                ],
                [
                    'province' => '北京',
                    'type' => 'class',
                    'disp' => '区域大礼包',
                    'logo' => 'https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1496062375&di=ebededc00513bf2550ee6b2341187257&imgtype=jpg&er=1&src=http%3A%2F%2Fimg1.juimg.com%2F150628%2F330456-15062Q04G188.jpg',
                    'func' => 'amount', # 总数
                    'begin_at' => '2017-05-01',
                    'end_at' => '2017-06-01',
                    'list' => [
                        [
                            'key' => '蒙牛-冰淇淋',
                            'limit' => 1, # 价格区间
                            'unit' => '箱'
                        ],
                        [
                            'key' => '伊利-冰淇淋',
                            'limit' => 1, # 价格区间
                            'unit' => '箱'
                        ]
                    ],
                    'min_val' => 100,
                    'benefits' => [
                        'type' => 'gifts',
                        'val' => [
                            [
                                'id' => 87944,
                                'name' => '一统世加鲜奶冰棒',
                                'goods_thumb' => '/img/log/undefined.jpg',
                                'count' => 1,
                                'standard' => '70g×40支',
                                'unit' => '箱',
                                'city_price' => '32.50'
                            ]
                        ]
                    ]
                ],
                [
                    'type' => 'good',
                    'disp' => '区域大礼包',
                    'logo' => 'https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1496062375&di=ebededc00513bf2550ee6b2341187257&imgtype=jpg&er=1&src=http%3A%2F%2Fimg1.juimg.com%2F150628%2F330456-15062Q04G188.jpg',
                    'func' => 'per_count', # 总数
                    'begin_at' => '2017-05-01',
                    'end_at' => '2017-06-01',
                    'list' => [
                        [
                            'key' => '蒙牛冰+-棒冰',
                            'limit' => 1, # 价格区间
                            'unit' => '箱'
                        ],
                        [
                            'key' => '蒙牛-雪糕',
                            'limit' => 1, # 价格区间
                            'unit' => '箱'
                        ]
                    ],
                    'min_val' => 1,
                    'benefits' => [
                        'type' => 'gifts',
                        'val' => [
                            [
                                'id' => 87945,
                                'name' => '二统世加鲜奶冰棒',
                                'goods_thumb' => '/img/log/undefined.jpg',
                                'count' => 1,
                                'standard' => '70g×40支',
                                'unit' => '箱',
                                'city_price' => '32.50'
                            ]
                        ]
                    ]
                ],
                [
                    'type' => 'class',
                    'disp' => '区域大礼包',
                    'logo' => 'https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1496062375&di=ebededc00513bf2550ee6b2341187257&imgtype=jpg&er=1&src=http%3A%2F%2Fimg1.juimg.com%2F150628%2F330456-15062Q04G188.jpg',
                    'func' => 'per_amount', # 总数
                    'begin_at' => '2017-05-01',
                    'end_at' => '2017-06-01',
                    'list' => [
                        [
                            'key' => '蒙牛-速冻类',
                            'limit' => 1, # 价格区间
                            'unit' => '箱'
                        ],
                        [
                            'key' => '伊利-冰淇淋',
                            'limit' => 1, # 价格区间
                            'unit' => '箱'
                        ]
                    ],
                    'min_val' => 10,
                    'benefits' => [
                        'type' => 'money',
                        'val' => 10
                    ]
                ],
                [
                    'type' => 'all',
                    'disp' => '区域大礼包all',
                    'logo' => 'https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1496062375&di=ebededc00513bf2550ee6b2341187257&imgtype=jpg&er=1&src=http%3A%2F%2Fimg1.juimg.com%2F150628%2F330456-15062Q04G188.jpg',
                    'func' => 'amount', # 总数
                    'begin_at' => '2017-05-01',
                    'end_at' => '2017-06-01',
                    'list' => [
                        [
                            'key' => 'item',
                            'limit' => 1, # 价格区间
                            'unit' => '箱'
                        ],
                    ],
                    'min_val' => 10,
                    'benefits' => [
                        'type' => 'money',
                        'val' => 10
                    ]
                ]
            ];
        }
        $res = [];
        $good_service = Service::getInstance('goods');
        $all_benefits = $this->db->fetchAll('select benefit_info from benefits');
        foreach($all_benefits as $benefit){
            $benefit_info = json_decode($benefit['benefit_info'],true);
            // Util::dump($benefit_info);
            if('gifts' == $benefit_info['benefits']['type']){
                $gifts = $benefit_info['benefits']['val'];
                $benefit_info['benefits']['val'] = [];
                foreach($gifts as $gift){
                    $good_id = $this->db->fetchOne('select id from goods where goods_name ="'.$gift['name'].'" order by id desc limit 1');
                    $tmp_res = [];
                    if($good_id > 0){
                        $good_info = $good_service -> goodsInfoSimple($good_id);
                        $tmp_res['id'] = $good_id;
                        $tmp_res['goods_thumb'] = $good_info['goods_thumb'];
                        $tmp_res['standard'] = $good_info['standard'];
                        $tmp_res['unit'] = $good_info['unit'];
                        $tmp_res['city_price'] = $good_info['city_price'];
                        $tmp_res['name'] = $gift['name'];
                        $tmp_res['count'] = $gift['count'];
                    }else{
                        $tmp_res['id'] = '';
                        $tmp_res['goods_thumb'] =  "/img/log/undefined.jpg";
                        $tmp_res['standard'] =  '';
                        $tmp_res['unit'] =  '';
                        $tmp_res['city_price'] =  '';
                        $tmp_res['name'] = $gift['name'];
                        $tmp_res['count'] = $gift['count'];
                    }
                    array_push($benefit_info['benefits']['val'],$tmp_res);
                    // Util::dump($benefit_info);
                }
            }
            array_push($res, $benefit_info);
        }
        return $res;
    }

    function benefitListPageSql($where, $flag = 0){
        $sql_select = '';
        if(0 == $flag){
            $sql_select = "select province, city,warehouse, type, disp, logo, func, min_val, begin_at, end_at, benefit_info";
        }else{
            $sql_select = "select count(1) num";
        }
        $sql = $sql_select."
             from benefits
             order by id desc";
        return $sql;
    }
    # 获取视图显示的优惠规则列表
    function benefitList($pag,$pags,$where){
        $sql = $this->benefitListPageSql($where).
            " limit ".$pag.",".$pags;
        $benefit_list = $this->db->fetchAll($sql);
        return $benefit_list;
    }

    /**
     * 买家订单列表分页
     *
     * $sql str 查询表
     * $perpage int 分页几条数据
     * $page int 当前第几页
     * $url 连接
     */
    public function benefitListPags($sql,$perpage,$page,$url){
//         echo '<h3 style="color:red;">'.$sql.'</h3>';
        //$goodsList = $this->db->fetchAll($sql);
        $benefitsnum = $this->db->fetchROW($sql);

        //var_dump($goodsnum);exit;
        $total =ceil($benefitsnum['num']/$perpage);
        return Util::buildPagebar($total,1,$page,$url);
    }
}