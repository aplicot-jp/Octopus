<?php
/**
 * Octopus
 * HTML出力テンプレートエンジン
 *
 * PHP version 5
 * @author aplicot <info@aplicot.jp>
 * @Copyright 2015 aplicot
 * @version 1.0.1
 *
 * This software is released under the LGPL License.
 * http://opensource.org/licenses/LGPL-3.0
 */
class Octopus {
	private $html;
	private $data;
	private $loop_data;
	private $template_dir;
	public function __construct() {
	}
	public function template_dir($dir) {
		$this->template_dir = $dir;
	}
	public function view($name) {
		$file = $this->template_dir . $name;
		if (file_exists ( $file )) {
			$handle = fopen ( $file, "r" );
			$this->html = fread ( $handle, filesize ( $file ) );
			fclose ( $handle );
		}
		$this->html = preg_replace_callback ( '#\[\$([^\]]*)\](.*?)\[/\$([^\]]*?)\]#is', array (
				$this,
				'loop' 
		), $this->html );
		$this->html = preg_replace_callback ( '#\[\[\$([^\]]*)\]\]#is', array (
				$this,
				'tag' 
		), $this->html );
		header ( "Content-type: text/html; charset=utf-8" );
		echo $this->html;
	}
	public function set_var($name, $var) {
		$this->data [$name] = $var;
	}
	public function set_array($var) {
		if (is_array ( $var )) {
			while ( list ( $key, $val ) = each ( $var ) ) {
				$this->data [$key] = $val;
			}
		}
	}
	
	// テンプレートのループ処理
	private function loop($match) {
		$code = '';
		if (is_array ( $this->data [$match [1]] )) {
			foreach ( $this->data [$match [1]] as $this->loop_data ) {
				$code .= preg_replace_callback ( '#\[\[\$([^\]]*)\]\]#is', array (
						$this,
						'tag' 
				), $match [2] );
			}
		}
		return $code;
	}
	
	// テンプレート変数に値を代入
	private function tag($match) {
		// 表示オプションの有無を判定
		if (strpos ( $match [1], ':' ) !== false) {
			$tag_array = explode ( ':', $match [1] );
			$tag = $tag_array [0];
			$option = 1;
		} else {
			$tag = $match [1];
			$option = 0;
		}
		
		// 配列のデータか変数のデータかを判定
		if (isset ( $this->loop_data [$tag] ) && $this->loop_data [$tag] !== '') {
			$value = $this->loop_data [$tag];
		} elseif (isset ( $this->data [$tag] ) && $this->data [$tag] !== '') {
			$value = $this->data [$tag];
		} else {
			$value = '';
		}
		
		// 表示オプションがある場合の処理
		if ($option == 1 && $value !== '') {
			foreach ( $tag_array as $func ) {
				if ($func != $tag) {
					$value = $this->$func ( $value );
				}
			}
		}
		
		return $value;
	}
	
	// brオプション
	private function br($value) {
		return preg_replace ( "/\r\n\n|\r\n|\n|\r/", "<br>\n", $value );
	}
	
	// number_formatオプション
	private function number($value) {
		return number_format ( $value );
	}
	
	// 数字を日本表記 （不動産用に万円まで）
	private function number_jp($value) {
		$oku = floor ( $value / 100000000 );
		$man = ($value % 100000000) / 10000;
		
		$result = '';
		if ($oku) {
			$result = number_format ( $oku ) . '億';
		}
		if ($man) {
			$man = number_format ( $man, 2 );
			$man = preg_replace ( "/\.?0+$/", '', $man );
			$result .= $man . '万';
		}
		
		return $result;
	}
}
