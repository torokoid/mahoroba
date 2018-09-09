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


/**
 * siteMap 
 * 
 * @package 
 */
class siteMap {

	// 設定
	/**
	 * getTag 
	 *
	 * 取得するタグの文字列
	 * 
	 * @var string
	 * @access public
	 */
	var $getTag = "title";

	/**
	 * getId 
	 *
	 * 取得するタグのID
	 * 
	 * @var string
	 * @access public
	 */
	var $getId = null;

	/**
	 * splitText 
	 *
	 * 分割する文字
	 * 
	 * @var string
	 * @access public
	 */
	var $splitText = "|";

	/**
	 * getPosition 
	 *
	 * 取得する文字の位置
	 * last OR first
	 * 
	 * @var string
	 * @access public
	 */
	var $getPosition = "last";

	/**
	 * notSearchFull 
	 *
	 * 指定ディレクトリ以下を検索しない
	 * 
	 * @var array
	 * @access public
	 */
	var $notSearchFull = array();

	/**
	 * notSearchExt 
	 *
	 * 検索しない拡張子
	 * 
	 * @var array
	 * @access public
	 */
	var $notSearchExt = array();

	/**
	 * searchExt 
	 *
	 * 指定拡張子を検索する
	 * 
	 * @var array
	 * @access public
	 */
	var $searchExt = array( "html", 
							"htm"
							);

	/**
	 * disSitemapTemplate 
	 * 
	 * @var string
	 * @access public
	 */
	var $disSitemapTemplate = "tmp/sitemap.tpl";

	// 内部で使用
	/**
	 * path_list 
	 *
	 * サイトマップに使用するパスリスト
	 * 
	 * @var array
	 * @access public
	 */
	var $path_list = array();

	/**
	 * pathFull 
	 *
	 * システム内で使用するパス
	 * 
	 * @var string
	 * @access public
	 */
	var $pathFull = "";

	/**
	 * setPriority 
	 *
	 * 並び替えようファイル
	 * 
	 * @var string
	 * @access public
	 */
	var $setPriority = "tmp/sort.txt";

	var $arrPriority = array();

	/**
	 * sortPriority 
	 *
	 * 並び替え用のファイルを優先するか
	 * 
	 * @var string
	 * @access public
	 */
	var $sortPriority = false;

	/**
	 * disSitemapCacheDir 
	 *
	 * キャッシュ保存ディレクトリ
	 * 
	 * @var string
	 * @access public
	 */
	var $disSitemapCacheDir = "tmp/cache";

	/**
	 * disSitemapCacheBaseName 
	 *
	 * キャッシュのベース名
	 * 
	 * @var string
	 * @access public
	 */
	var $disSitemapCacheBaseName = "sitemap";

	/**
	 * cacheTime 
	 *
	 * キャッシュの期間
	 * 
	 * @var string
	 * @access public
	 */
	var $cacheTime = "0";

	/**
	 * charset 
	 *
	 * 出力する文字コード
	 * 
	 * @var string
	 * @access public
	 */
	var $charset = "UTF-8";

	/**
	 * disSitemapPath 
	 *
	 * 空の場合、動的に出力
	 * 実行ファイルからの相対パスの入力でファイルを出力
	 * 
	 * @var string
	 * @access public
	 */
	var $disSitemapPath = "";

	// 置換条件
	var $left_delimiter = "{";
	var $substitution_letter = "sitemap";
	var $right_delimiter = "}";


	var $doc = null;
	var $notSearchFullText = "";
	var $notSearchExtText = "";
	var $SearchExtText = "";

	var $titleKey = "/=title";
	var $pathKey = "/=path";

	var $disData = "";
	var $disSort = "";

	// {{{ __construct
	/**
	 * __construct 
	 * 
	 * @access protected
	 * @return void
	 */
	function __construct(){
		$this->doc = new DOMDocument;
		$this->doc->validateOnParse = true;
	}
	// }}} 

	// {{{ process
	/**
	 * process 
	 * 
	 * @param string $path 
	 * @access public
	 * @return void
	 */
	function process( $path = "../" ){

		// キャッシュのチェック
		if( $this->cacheTime > (int)"0" ){
			$this->cacheTime = (int)$this->cacheTime * (int)"60" * (int)"60";
			$this->disCacheData();
		}

		// 実行ファイルの絶対パスを取得
		$this->pathFull = realpath( $path );

		$this->notSearchFullText = $this->_implode( $this->notSearchFull, $this->pathFull . "/", "full"     ) ;
		$this->notSearchExtText  = $this->_implode( $this->notSearchExt,  "",                    "backward" ) ;
		$this->searchExtText     = $this->_implode( $this->searchExt,     "",                    "backward" ) ;

		// sort 
		if( is_file( $this->setPriority ) ){
			$sort = $this->_getPriority();
		}

		// ファイルの取得
		$this->path_list = $this->get_path( $this->pathFull );

		// 出力
		$this->display();
	}
	// }}} 

	// {{{ get_path
	/**
	 * get_path 
	 *
	 * サイトマップに必要なファイルの取得
	 * 
	 * @param string $directory 
	 * @access public
	 * @return void
	 */
	function get_path( $dir ){

		$path = array();
		if( !is_dir( $dir ) ){
			return false;

		}else if( $hd = opendir( $dir ) ) {

			while( false !== ( $file = readdir( $hd ) ) ) {

				if( $file != '.' && $file != '..' ) {

					$tmp = $dir . "/" . $file;

					if( $this->notSearchFullText !== ""
						&& preg_match( $this->notSearchFullText, $tmp ) ){
					} else if( is_dir( $tmp ) ) {
						// ディレクトリが存在したらディレクトリとして扱う。

						$path["{$file}"] = $this->get_path( $tmp );

						if( is_array( $path["{$file}"] )
							&& count( $path["{$file}"] ) === (int)"0" ){
							unset( $path["{$file}"] );
						}

					} else {

						// ファイルリストに
						if( $this->searchExtText === "" ){
							$path["{$file}"]["{$this->pathKey}"] = $tmp;
							$path["{$file}"]["{$this->titleKey}"] = $this->convertEncoding( $this->_getTitle( $tmp ) );

						}else if( preg_match( $this->notSearchExtText, $file ) ){
						}else if( preg_match( $this->searchExtText, $file ) ){
							$path["{$file}"]["{$this->pathKey}"] = $tmp;
							$path["{$file}"]["{$this->titleKey}"] = $this->convertEncoding( $this->_getTitle( $tmp ) );

						}

					}
				}
			} // while ( )

			ksort( $path );
			closedir( $hd );
		}

		return $path;
	}
	// }}} 

	// {{{ convertEncoding
	/**
	 * convertEncoding 
	 * 
	 * @param string $str 
	 * @access public
	 * @return void
	 */
	function convertEncoding( $str ){
		if( $this->charset == "" ){
			return $str;
		}
		return mb_convert_encoding( $str, $this->charset, "ASCII,JIS,UTF-8,EUC-JP,SJIS" );
	}
	// }}} 

	// {{{ _getTitle
	/**
	 * _getTitle 
	 *
	 * タイトルの取得
	 * 
	 * @param string $filePath 
	 * @access protected
	 * @return void
	 */
	function _getTitle( $filePath ){

		$html = @$this->doc->loadHTML( mb_convert_encoding( $this->_getFileData( $filePath ), 
															"HTML-ENTITIES", 
															"ASCII,JIS,UTF-8,EUC-JP,SJIS"
														  )
									 );
		if( !is_file( $filePath ) ){
			return false;
		}else if( $html ){

			$items = $this->doc->getElementsByTagName( $this->getTag );

			if( $this->getId === null ){
				$_title = $this->_getTitleValue( $items );
			}else {
				$_title = $this->_getTitleValueId( $items );
			}

			$a = explode( $this->splitText, $_title );
			if( $this->getPosition === "last" ){
				$count = count( $a ) - (int)"1";
				$title = $a["{$count}"];
			}else {
				$title = $a["0"];
			}
			return $title;

		}
		return false;

	}
	// }}} 

	// {{{ _getTitleValueId
	/**
	 * _getTitleValueId 
	 * 
	 * @param string $items 
	 * @access protected
	 * @return void
	 */
	function _getTitleValueId( $items ){
		foreach( $items as $item ){
			$id = $item->getAttribute( "id" );
			if( $id === $this->getId ){
				return $this->_getValue( $item );
			}
		}
	}
	// }}} 

	// {{{ _getTitleValue
	/**
	 * _getTitleValue 
	 *
	 * @param string $items 
	 * @access protected
	 * @return void
	 */
	function _getTitleValue( $items ){
		foreach( $items as $item ){
			return $this->_getValue( $item );
		}
	}
	// }}} 

	// {{{ _getValue
	/**
	 * _getValue 
	 *
	 * タイトルの取得
	 * 
	 * @param string $item 
	 * @access protected
	 * @return void
	 */
	function _getValue( $item ){
		foreach($item->childNodes as $i) {
			return $i->nodeValue;
		}
	}
	// }}} 

	// {{{ _implode
	/**
	 * _implode 
	 *
	 * 配列を結合
	 * 
	 * @param string $string 
	 * @access protected
	 * @return void
	 */
	function _implode( $string, $addString = "", $type = false ){
		$data = "";
		if( is_array( $string )
			&& count( $string ) > (int)"0" ){
			$text = "";
			foreach( $string as $v ){
				$add = $this->_replaceSlash( $addString . $v );
				$text .= preg_quote( $add, "/" ) . "|";
			}
			$data = $this->_searchCondition( $text, $type );
		}else if( $string !== "" ){

			$text = "";
			$add = $this->_replaceSlash( $addString . $string );
			$text .= preg_quote( $add, "/" ) . "|";
			$data = $this->_searchCondition( $text, $type );
		}
		return $data;
	}
	// }}} 

	// {{{ _replaceSlash
	/**
	 * _replaceSlash 
	 * 
	 * @param string $text 
	 * @access protected
	 * @return void
	 */
	function _replaceSlash( $text ){
		return preg_replace( array( "/\/+/", "/\/$/" ), array( "/", "" ), $text );
	}
	// }}} 

	// {{{ _searchCondition
	/**
	 * _searchCondition 
	 * 
	 * @param string $text 
	 * @param string $type 
	 * @access protected
	 * @return void
	 */
	function _searchCondition( $text, $type = "" ){

		$data = "";
		$text = preg_replace( "/\|$/", "", $text );
		switch( $type ){
			case "full":
				$data = "/^(" . $text . ")$/";
			break;

			case "backward":
				$data = "/(" . $text . ")$/";
			break;

			default:
				$data = "/(" . $text . ")/";

		}
		return $data;
	}
	// }}} 

	// {{{ _getPriority
	/**
	 * _getPriority 
	 * 
	 * @access protected
	 * @return void
	 */
	function _getPriority(){
		$fp = fopen( $this->setPriority, "rb" );
		if( $fp ){
			$a = array();
			$b = array();
			while ( !feof( $fp ) ) {
				$buffer = fgets( $fp );
				$tmp = preg_split( "/\s+/", $buffer );
				if( $tmp["0"] === "" ){
					continue;
				}
				$path = $tmp["0"];
				unset( $tmp["0"] );
				$title = implode( " ", $tmp );
				$b = $this->_setPriorityList( $path, $path, $title );
				$a = array_merge_recursive( $a, $b );
			}
			$this->arrPriority = $a;
			fclose( $fp );
			return true;
		}
		return false;
	}
	// }}} 

	// {{{ _getFileData
	/**
	 * _getFileData 
	 * 
	 * @param string $path 
	 * @access protected
	 * @return void
	 */
	function _getFileData( $path ){
		$fp = fopen( $path, "rb" );
		if( $fp ){
			$buffer = "";
			while ( !feof( $fp ) ) {
				$buffer .= fgets( $fp );
			}
			fclose( $fp );
			return $buffer;
		}
		return false;
	}
	// }}} 

	// {{{ _setPriorityList
	/**
	 * _setPriorityList 
	 *
	 * 順番を配列に変換
	 * 
	 * @param string $path 
	 * @param string $keepPath 
	 * @param string $title 
	 * @access protected
	 * @return void
	 */
	function _setPriorityList( $path, $keepPath, $title ){
		$t = explode( "/", $path );
		$tmp = $t;
		$arrPath = array();
		if( is_array( $t ) 
			&& count( $t ) > (int)"1" ){
			foreach( $t as $k => $v ){
				unset( $tmp["{$k}"] );
				if( $v !== "" ){
					$tmpPath = implode( "/", $tmp );
					$arrPath["{$v}"] = $this->_setPriorityList( $tmpPath, $keepPath, $title );
					break;
				}
			}
		}else {
			$title = $this->convertEncoding( $title );
			if( $path === "" ){
				$arrPath["{$this->pathKey}"] = $keepPath;
				$arrPath["{$this->titleKey}"] = $title;
			}else {
				$arrPath["{$path}"]["{$this->pathKey}"] = $keepPath;
				$arrPath["{$path}"]["{$this->titleKey}"] = $title;
			}
		}
		return $arrPath;
	}
	// }}} 

	// {{{ display
	/**
	 * display 
	 *
	 * 出力処理
	 * 
	 * @access public
	 * @return void
	 */
	function display( ){

		$arr = $this->_arrayMerge( $this->arrPriority, $this->path_list );
		$rmPath = preg_quote( $this->pathFull, "/" );

		// 出力酔うデータの作成
		$this->_htmlData( $arr, $rmPath );

		// 並び替えよう
		$this->_output( $this->setPriority, $this->disSort );

		// 出力
		$this->_disSitemap();

	}
	// }}} 

	// {{{ _htmlData
	/**
	 * _htmlData 
	 *
	 * サイトマップの出力
	 * 
	 * @param string $arr 
	 * @param string $rmPath 
	 * @param string $hierarchy 
	 * @param string $dir 
	 * @access protected
	 * @return void
	 */
	function _htmlData( $arr, $rmPath, $hierarchy = "0", $parent = "0", $dir = "" ){

		// 優先
		if( is_array( $arr )
			&& count( $arr ) > (int)"0" ){
			if( $hierarchy > (int)"0" ){
				$indent = str_repeat( "\t", $hierarchy );
			}else {
				$indent = "";
			}

			$indentPlus = str_repeat( "\t", $hierarchy + (int)"1" );

			$this->display .= $indent;
			$this->display .= "<ul class=\"level-{$parent}\">\n";
			foreach( $arr as $k => $v ){
				if( is_array( $v )
					&& count( $v ) === (int)"0" ){
					continue;
				}
				$tmp = "";
				if( $dir !== "" ){
					$tmp = preg_replace( "/[^a-z0-9_-]/i", "", $dir );
				}else {
					$tmp = "top-level";
				}

				$this->display .= $indentPlus;
				$this->display .= '<li class="'
					 . $tmp
					 . '">';
				if( isset( $v["{$this->titleKey}"], $v["{$this->pathKey}"] ) ){

					if( preg_match( "/^https?:\/\//", $v["{$this->pathKey}"] ) ){
						$p = $v["{$this->pathKey}"];
					}else {
						$p = preg_replace( array( "/^{$rmPath}/", "/\/+/" ), "/", $v["{$this->pathKey}"] ) ;
					}
					$this->display .= '<a href="' 
						 . $p 
						 . '" class="'
						 . preg_replace( "/[^a-z0-9_-]/i", "-", basename( $v["{$this->pathKey}"] ) )
						 . '">'
						 . $v["{$this->titleKey}"]
						 . "</a>";

					$this->disSort .= 
						   $p 
						 . " " 
						 . $v["{$this->titleKey}"]
						 . "\n";

				}else {
					$tmp = preg_replace( array( "/^\s+/", "/\s+/" ) , array( "", "-" ), $dir . " " .  $k );
					$this->display .= "\n";
					$this->_htmlData( $v, $rmPath, $hierarchy + (int)"2", $parent + (int)"1", $tmp );
					$this->display .= $indentPlus;
				}
				$this->display .= "</li>\n";

			} // End foreach 
			$this->display .= $indent . "</ul>\n";

		}

	}
	// }}} 

	// {{{ _arrayMerge
	/**
	 * _arrayMerge 
	 * 
	 * @param string $path 
	 * @param string $plus 
	 * @access protected
	 * @return void
	 */
	function _arrayMerge( $path, $plus ) {
		$data = $plus;
		if( is_array( $path )
			&& count( $path ) > (int)"0"
			&& is_array( $plus )
			&& count( $plus ) > (int)"0" ){
			$data = array();
			if( $this->sortPriority ){
				$data += $path;
			}
			foreach( $path as $k => $v ){
				if( isset( $data["{$k}"] ) 
					&& is_array( $data["{$k}"] ) 
					&& isset( $data["{$k}"]["{$this->titleKey}"] )
					&& isset( $data["{$k}"]["{$this->pathKey}"] ) ){
				}else if( isset( $plus["{$k}"] ) 
					&& is_array( $plus["{$k}"] ) 
					&& isset( $plus["{$k}"]["{$this->titleKey}"] )
					&& isset( $plus["{$k}"]["{$this->pathKey}"] ) ){
					$data["{$k}"] = $plus["{$k}"];
					unset( $plus["{$k}"] );
				}else if( isset( $plus["{$k}"] ) 
					&& is_array( $plus["{$k}"] ) ){
					$data["{$k}"] = $this->_arrayMerge( $path["{$k}"], $plus["{$k}"] );
				}
			} // End foreach
			$data += $plus;
		} // End if
		return $data;
	}
	// }}} 

	// {{{ _output
	/**
	 * _output 
	 * 
	 * @param string $path 
	 * @param string $data 
	 * @access protected
	 * @return void
	 */
	function _output( $path, $data ){
		$fp = fopen( $path, "a" );

		if( $fp ){
			$retries = (int)"0";
			$max_retries = (int)"100";

			do {
				if( $retries > (int)"0" ){
					usleep( rand( 1, 10000 ) );
				}
				$retries++;
			}while( !flock( $fp, LOCK_EX ) && $retries <= $max_retries );

			if( $retries == $max_retries ){
				return false;
			}
			ftruncate( $fp, "0" );
			fwrite( $fp,$data );
			flock( $fp, LOCK_UN );
			fclose( $fp );
			return true;
		}
		return false;
	}
	// }}} 

	// {{{ _disSitemap
	/**
	 * _disSitemap 
	 * 
	 * @access protected
	 * @return void
	 */
	function _disSitemap(){
		$data = false;
		if( is_file( $this->disSitemapTemplate ) ){
			$data = $this->_getFileData( $this->disSitemapTemplate );
		}

		if( $data !== false ){
			$replace = preg_quote( $this->left_delimiter 
				. '$'
				. $this->substitution_letter 
				. $this->right_delimiter, 
				"/" );
			$data = preg_replace( "/" . $replace . "/", $this->display, $data );
		}else {
			$data = $this->display;
		}

		// 表示またはファイルの出力
		if( $this->disSitemapPath !== "" ){
			$this->_output( $this->disSitemapPath, $data );
			echo "サイトマップを生成しました。";

		}else {
			echo $data;
		}

		// ディレクトリがなければ作成
		if( !is_dir( $this->disSitemapCacheDir ) ){
			mkdir( $this->disSitemapCacheDir, 0777 );
		}

		// キャッシュの書き込み
		$path = $this->disSitemapCacheDir 
			. "/"
			. $this->disSitemapCacheBaseName
			. "_"
			. time();
		$this->_output( $path, $data );


		// キャッシュの削除
		register_shutdown_function( array( $this, "_rmCache" ) );

	}
	// }}} 

	// {{{ disCacheData
	/**
	 * disCacheData 
	 * 
	 * @access public
	 * @return void
	 */
	function disCacheData(){
		$path = $this->_getCache();
		if( is_array( $path )
			&& count( $path ) > "0" ){
			krsort( $path );

			$time = time();
			foreach( $path as $k => $v ){
				$mTime = $this->cacheTime + $k;
				if( $mTime > $time ){

					$a = $this->_getFileData( $v );

					if( $a !== "" ){
						if( $this->disSitemapPath === "" ){
							echo $a;
						}else {
							echo "";
						}
						exit;
					}
				}

				break;
			} // End foreach

		} // End if

	}
	// }}} 

	// {{{ _rmCache
	/**
	 * _rmCache 
	 * 
	 * @access protected
	 * @return void
	 */
	function _rmCache(){

		$path = $this->_getCache();

		$keep = (int)"5";

		if( is_array( $path )
			&& count( $path ) > "0" ){
			krsort( $path );
			$count = count( $path );
			$i = (int)"0";

			foreach( $path as $k => $v ){
				$i++;
				if( $keep >= $i ){
					continue;
				}
				if( is_file( $v ) ){
					@unlink( $v );
				}

			}
		}

	}
	// }}} 

	// {{{ _getCache
	/**
	 * _getCache 
	 * 
	 * @access protected
	 * @return void
	 */
	function _getCache(){

		$path = array();
		$dir = realpath( dirname( __FILE__ ) . "/" . $this->disSitemapCacheDir . "/" );
		if( !is_dir( $dir ) ){
			return false;

		}else if( $hd = opendir( $dir ) ) {

			while( false !== ( $file = readdir( $hd ) ) ) {

				if( $file != '.' && $file != '..' ) {

					$tmp = $dir . "/" . $file;

					if( is_file( $tmp ) ) {
						$a = preg_replace( "/^.*?([0-9]+)$/", "$1", $tmp );

						$path["{$a}"] = $tmp;

					}
				}
			} // while ( )
			closedir( $hd );
		}
		return $path;
	}
	// }}} 

}


?>
