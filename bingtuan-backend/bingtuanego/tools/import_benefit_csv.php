<?php
define("ROOT_PATH",  realpath(dirname(__FILE__) . '/../'));
define("APP_PATH",  realpath(dirname(__FILE__) . '/'));
$app = new Yaf_Application(ROOT_PATH . "/conf/adm.ini");
$conf = Yaf_Application::app()->getConfig();

// print_r($conf->get('db'));
$db = Db::getInstance($conf->get('db'));
// print_r($db);
if(!isset($argv[1])){
  echo("请输入文件路径\n");
  exit(-1);
}else{
  $file = $argv[1];
  if(!file_exists($file)){
    echo("文件".$file."不存在\n");
    exit(-1);
  }
  echo("开始处理文件:".$file."\n");
  // $handle = fopen($file,'rb');
  $contents = file($file);
  $attrs = ['province', 'city', 'warehouse', 'disp', 'logo', 'type','func', 'min_val','begin_at','end_at'];
  foreach($contents as $line){
    print_r($line);
    // break;
    $line_arr = explode(',',trim($line));
    $benefit_body = [];
    $index = 0;
    foreach($attrs as $value){
      if(in_array($value,['begin_at','end_at'])){
        $line_arr[$index] = str_replace('.','-',$line_arr[$index]);
        if(2 == count(explode('-',$line_arr[$index]))){
          $line_arr[$index] = getdate()['year'].'-'.$line_arr[$index];
        }
      }
      $benefit_body[$value] = $line_arr[$index];
      $index++;
    }
    $benefit_body['benefits'] = [];
    $benefit_body['benefits']['type'] = $line_arr[$index++];
    if('满减' == $benefit_body['benefits']['type']){
      $benefit_body['benefits']['type'] = 'money';
      $benefit_body['benefits']['val'] = $line_arr[$index++] * $line_arr[$index++];
    }
    if('赠品' == $benefit_body['benefits']['type']){
      $benefit_body['benefits']['type'] = 'gifts';
      $benefit_body['benefits']['val'] = [];
      array_push($benefit_body['benefits']['val'],[
        'name' => $line_arr[$index++],
        'count' => $line_arr[$index++]
      ]);
    }
    $benefit_body['list'] = [];
    while($index < count($line_arr)){
      if('' == $line_arr[$index + 1]){break;}
      array_push($benefit_body['list'],[
        'key' => $line_arr[$index++],
        'limit' => $line_arr[$index++],
        'unit' => $line_arr[$index++]
      ]);
    }
    // print_r($benefit_body);
    // print_r(json_decode(json_encode($benefit_body),true));
    // break;
    $data = [
      'province' => $benefit_body['province'],
      'city' => $benefit_body['city'],
      'warehouse' => $benefit_body['warehouse'],
      'type' => $benefit_body['type'],
      'disp' => $benefit_body['disp'],
      'logo' => $benefit_body['logo'],
      'func' => $benefit_body['func'],
      'min_val' => $benefit_body['min_val'],
      'begin_at' => $benefit_body['begin_at'],
      'end_at' => $benefit_body['end_at'],
      'benefit_info' => json_encode($benefit_body)
    ];
    // print_r($data);
    $db->insert('benefits',$data);
  }
}