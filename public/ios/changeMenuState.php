<?php
echo 'start at'.date('Y-m-d H:i:s');

//$pdo = new PDO('mysql:host=localhost;dbname=db_mos', 'db_mos', 'I1YRfSE0bGybgKa');
$pdo = new PDO('mysql:host=192.168.146.140;dbname=db_mos', 'root', '123456');

$sql = "update menus set mweek = ? where mweek = ? AND mid > 52;";
$preObj = $pdo->prepare($sql);
$res    = $preObj->execute(array(3, 2));//下周状态改成3过度
$res    = $preObj->execute(array(2, 1));//本周状态改为2下周
$res    = $preObj->execute(array(1, 3));//过度3状态改为1本周


$sql = "select * from menus WHERE mweek = 2 AND mid > 52";//获取下周菜单
$pdoObj = $pdo->query($sql);
$res = $pdoObj->fetchAll(2);

$sql = "select * from menus WHERE mweek = 1 AND mid > 52";//获取本周菜单
$pdoObj = $pdo->query($sql);
$result = $pdoObj->fetchAll(2);

foreach ($res as $re) {             //遍历下周菜单
    foreach ($result as $item) {    //遍历本周菜单
        if ($re['tmark'] == $item['tmark'] && $re['sid']==$item['sid']){
            $sql = "update menus set fid = '{$item['fid']}' WHERE tmark = '{$re['tmark']}' AND mweek = 2 AND mid > 52";//将下周菜单改成和本周一样
            //echo $sql;
            $r = $pdo->exec($sql);
            var_dump($r);
        }
    }
}

echo 'end at'.date('Y-m-d H:i:s');