<h1 align="center"> EasyTaoKe </h1>

<p align="center"> 致力于最简单易用，最全的PHP淘客SDK.</p>


## Composer安装

```shell
$ composer require onecoder/easytaoke -vvv
```

## 用法

```php

$config = [
    'key'             => '',  //美团联盟key
    'secret'          => '',  //美团联盟secret
    'callback_secret' => '',  //美团联盟callback_secret
];

//实例化美团联盟应用
$meiTuanUnion = \OneCoder\EasyTaoKe\Factory::meiTuanUnion($config);
$startTime = time() - 24 * 60 * 60;
$endTime   = time();

//获取美团联盟订单
$data = $meiTuanUnion->getOrderList($startTime, $endTime);

//回调验证,返回true验证通过
$callBackResult = $meiTuanUnion->validateCallback($params);
```

## 贡献

欢迎广大PHPer一起加入淘客CPS SDK开发，致力于最完善易用的开源 SDK， 一起来PR

## 开源协议

MIT
