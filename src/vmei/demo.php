<?php

// 爬虫 - Vmei商城
$Vmei = new Vmei();

$Vmei->getFirstCategory(1);  // 获取一级和二级分类
$Vmei->resizeName();         // 重新整理产品的名字
$Vmei->fullWellTheDetail();  // 循环数据库并填充产品详情信息
$Vmei->repProImg();          // 替换产品主图为本地域名图片
$Vmei->repProDetails();      // 替换产品详情内页图为本地域名图片



