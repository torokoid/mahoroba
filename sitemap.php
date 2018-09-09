<?php
/*
======== table of content. =================================

Name: Quick Site Map
Versiton: 1.0.0
Description: This PHP script detects the file data of the WEB site, and makes the site map automatically. 
Update: 2010/10/19-
Author: Japan Electronic Industrial Arts Co.Ltd.
        http://jeia.co.jp/
Using: None
Lisence: GPL
 */
/********************************************************************
 *  設定はここから
 *******************************************************************/


// 取得するhtmlタグの指定
$getTagTitle = "title";


// 分割する文字列の指定
$splitText = "|";


// $splitTextにて分割され「見出し」として取得したいの前後の指定
// last OR firstにて指定
$getPosition = "first";


// 非表示したいディレクトリ又はファイルの指定
$notSearchFull[] = "QuickSitemap";
$notSearchFull[] = "news";
$notSearchFull[] = "update.html";
$notSearchFull[] = "common";


// 静的ファイルの出力先の指定
// 実行ファイルから見たファイルパス
$disSitemapPath = "../sitemap.html";


// サイトマップの更新期間の設定
// 1時間単位での指定
$cacheTime = "0";


// 出力する文字コード
// 指定可能文字コード : UTF-8, SJIS, EUC-JP
$charset = "UTF-8";


// 「sort.txt」ファイルへの任意リンク先記述による設定
// true => 設定する
// false => 設定しない
$sortPriority = false;


/********************************************************************
 *  設定はここまで
 *******************************************************************/



include_once( "./sitemap.class.php" );
$a = new siteMap();

// 取得するタグの文字
$a->getTagTitle = $getTagTitle;

// 分割する文字列
$a->splitText = $splitText;

// last OR first
$a->getPosition = $getPosition;

// 取得しないディレクトリ
$a->notSearchFull = $notSearchFull;

// 静的ファイルの出力先
$a->disSitemapPath = $disSitemapPath;

// 新規ファイルを作成するまでの時間 秒指定
$a->cacheTime = $cacheTime;

// 出力する文字コード
$a->charset = $charset;

// 並び替え用のファイルを優先するか
$a->sortPriority = ( isset( $sortPriority ) ) ? $sortPriority : false;

$a->process( "../" );


?>
