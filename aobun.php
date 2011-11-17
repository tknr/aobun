<?php

//=========================================================================
/**
 * 青空文庫からhtmlへ変換
 * @param string $basename
 * @param string $encode_src
 * @param string $encode_dst
 * @param string $file_full_path
 * @return array(string)
 * @see http://kanji-database.sourceforge.net/aozora/grammar.html
 */
function aozora_to_html($basename,$encode_src,$encode_dst,$file_full_path){
	$filename_array = array_reverse(explode('.',$file_full_path));
	$ext = strtolower($filename_array[0]);
	if(strcasecmp('txt', $ext)!=0){
		return false;
	}
	mb_regex_encoding($encode_dst);
	$contents = array();
	$fp = @fopen($file_full_path, "r");
	while(($line = @fgets($fp))){
		$line = mb_convert_encoding($line, $encode_dst, $encode_src);
		if(trim($line) === '［＃改ページ］'){
			$line = '<div style="height:20ex;"></div><hr size="1" width="100%" />';
		}
		elseif(strpos($line, '［＃地付き］', 0) === 0){
			$line = str_replace('［＃地付き］','',$line);
			$line = '<div align="right">'.$line.'</div>';
		}
		elseif(strpos($line, '［＃ここで字下げ終わり］', 0) === 0){
			$line = str_replace('［＃ここで字下げ終わり］','</p>',$line);
		}
		else{
			$convert_line = mb_convert_kana($line,n,$encode_dst);
			{
				$pattern = '/<img src="(.*?)">/';
				$replacement = '<a href="${1}"><img src="'.$basename.'?f=${1}" border="0" /></a>';
				$convert_line = preg_replace($pattern, $replacement, $convert_line);
			}
			{
				$pattern = '/\［＃地から(\d)字上げ\］(.+)/';
				$replacement = '<div align="right" style="margin-right:${1}em;">${2}</div>';
				$convert_line = preg_replace($pattern, $replacement, $convert_line);
			}
			{
				$pattern = '/\［＃ここから(\d)字下げ\］/';
				$replacement = '<p style="margin-left:${1}em;">';
				$convert_line = preg_replace($pattern, $replacement, $convert_line);
			}
			{
				$pattern = '/\［＃ここから(\d)字下げ、折り返して(\d)字下げ\］/';
				$replacement = '<p style="margin-left:${2}em;">';
				$convert_line = preg_replace($pattern, $replacement, $convert_line);
			}
			{
				$pattern = '/[一-龥朗-鶴]\［＃「(.*?)」([0-9]\-[0-9]+)\-([0-9]+)\］/u';
				$replacement = '<img src="http://www.aozora.gr.jp/gaiji/${2}/${2}-${3}.png" width="14" height="14" border="0" alt="${1}" style="vertical-align:middle;" />';
				$convert_line = preg_replace($pattern, $replacement, $convert_line);
			}
			{
				$pattern = '/[一-龥朗-鶴]\［＃「(.*?)」、第[0-9]水準([0-9]\-[0-9]+)\-([0-9]+)\］/u';
				$replacement = '<img src="http://www.aozora.gr.jp/gaiji/${2}/${2}-${3}.png" width="14" height="14" border="0" alt="${1}" style="vertical-align:middle;" />';
				$convert_line = preg_replace($pattern, $replacement, $convert_line);
			}
			{
				$pattern = '/[一-龥朗-鶴]《([ぁ-んァ-ヶー・]+)》\［＃「(.*?)」、第[0-9]水準([0-9]\-[0-9]+)\-([0-9]+)\］/u';
				$replacement = '<img src="http://www.aozora.gr.jp/gaiji/${3}/${3}-${4}.png" width="14" height="14" border="0" alt="${2}" style="vertical-align:middle;" /><sub><small>(${1})</small></sub>';
				$convert_line = preg_replace($pattern, $replacement, $convert_line);
			}
			{
				$pattern = '/(^[一-龥朗-鶴]+)《([ぁ-んァ-ヶー・]+)》/u';
				$replacement = '<u>${1}</u><sub><small>(${2})</small></sub>';
				$convert_line = preg_replace($pattern, $replacement, $convert_line);
			}
			{
				$pattern = '/([^一-龥朗-鶴])([一-龥朗-鶴]+)《([ぁ-んァ-ヶー・]+)》/u';
				$replacement = '${1}<u>${2}</u><sub><small>(${3})</small></sub>';
				$convert_line = preg_replace($pattern, $replacement, $convert_line);
			}
			{
				$pattern = '/\｜(.+)<u>/u';
				$replacement = '${1}<u>';
				$convert_line = preg_replace($pattern, $replacement, $convert_line);
			}
			{
				$pattern = '/\［＃「(.*?)」に傍点\］/u';
				if(preg_match_all($pattern, $convert_line,$matches,PREG_PATTERN_ORDER)){
					;
					for($i=0;$i<count($matches[0]);$i++){
						$convert_line = str_replace($matches[0][$i], '', $convert_line);
						$src = $matches[1][$i];
						$dst = '';
						for($j=0;$j<mb_strlen( $src ,$encode_dst );$j++){
							$c=mb_substr( $src , $j, 1,$encode_dst  );
							$dst .= $c.'<sup><strong>’</strong></sup>';
						}
						$convert_line = str_replace($src,'<i>'.$dst.'</i>', $convert_line);
					}
				}
			}
			{
				$pattern = '/\［＃「(.*?)」太字\］/u';
				if(preg_match_all($pattern, $convert_line,$matches,PREG_PATTERN_ORDER)){
					for($i=0;$i<count($matches[0]);$i++){
						$convert_line = str_replace($matches[0][$i], '', $convert_line);
						$convert_line = str_replace($matches[1][$i], '<strong>'.$matches[1][$i].'</strong>', $convert_line);
					}
				}
			}
			{
				$pattern = '/(　+)\?/u';
				$replacement = '<hr size="1" width="80%" />';
				$convert_line = preg_replace($pattern, $replacement, $convert_line);
			}
			$line = $convert_line;
		}
		$contents[] = $line;
	}
	@fclose($fp);

	return $contents;
}

/**
 * ページングリンク出力
 * @param string $url
 * @param int $max_count
 * @param int $current_page
 * @param int $rows_per_page
 * @param int $paging_width
 * @param bool $use_access_key
 * @param bool $output_max
 * @return string
 */
function get_paging_links($url = '/',$max_page = 0,$current_page = 1,$paging_width = 10){

	$output = '';

	if($current_page < 1){
		$current_page = 1;
	}

	$half_width = floor($paging_width /2);
	$from = $current_page - $half_width;
	$to = $current_page + $half_width;
	if($max_page <= $paging_width){
		$from = 1;
		$to = $max_page;
	}else if($current_page <= $half_width){
		$from = 1;
		$to = $paging_width;
	}else if(($current_page > ($max_page - $half_width)) && ($max_page - $paging_width > 0)){
		$from = $max_page - $paging_width;
		$to = $max_page;
	}

	// prev
	if($current_page > $from){
		$output .= ' <a href="'.$url.$from.'">&lt;&lt;</a>';
		$output .= ' <a href="'.$url.($current_page-1).'" accesskey="4" directkey="4" nonumber="nonumber">[4]&lt;</a>';
	}

	// numeric page list
	for($count = $from ; $count <= $to ; $count++){
		if($count != $from){
			$output .= '|';
		}
		if($count == $current_page){
			$output.= ' '.$count.'/'.$max_page.' ';
		}else{
			$output.= ' <a href="'.$url.$count.'">'.$count.'</a>';
		}
	}

	// next
	if($current_page < $to){
		$output .= ' <a href="'.$url.($current_page+1).'" accesskey="6" directkey="6" nonumber="nonumber">&gt;[6]</a>';
		$output .= ' <a href="'.$url.$to.'">&gt;&gt;</a>';
	}

	return $output;
}

/**
 * 再帰implode
 * @param array $array
 * @param string $glue
 * @return string
 */
function reflexive_implode($array,$glue= '<br />'){
	$string = '';

	foreach($array as $index=>$value){
		if($index != 0){
			$string .= $glue;
		}
		if(is_array($value)){
			$string .= reflexive_implode($value,$glue);
		}else{
			$string .= $value;
		}
	}
	return $string;
}

/**
 * get bytes of array
 * @param array $array
 * @param string $glue
 * @return number
 */
function get_bytes($array,$glue= '<br />'){
	if(is_array($array)){
		$imp = reflexive_implode($array, $glue);
	}else{
		$imp = $array.$glue;
	}
	return strlen(bin2hex($imp)) / 2;
}

/**
 * split with bytes
 * @param array(string) $array
 * @param number $target_bytes
 * @return multitype:multitype: multitype:unknown
 */
function split_with_bytes($array,$target_bytes = 4000){
	$contents = array();
	$tmp_array = array();
	for($i= 0;$i<count($array);$i++){
		$tmp_array[] = $array[$i];
		$bytes = get_bytes($tmp_array);
		if($bytes >= $target_bytes){
			$contents[] = $tmp_array;
			$tmp_array = array();
		}
	}
	if(!empty($tmp_array)){
		$contents[] = $tmp_array;
	}
	return $contents;
}
/**
 * get image file info
 * @param string $file_path
 * @param number $max_width
 * @return multitype:
 */
function imgfileinfo($file_path,$max_width=64){
	$img= @getimagesize($file_path);
	if (($img[0] < $max_width) and ($img[1] < $max_width)){
		$tw=$img[0]; $th=$img[1];
	} else {
		if ($img[0] < $img[1]){
			$th=$max_width; $tw=$img[0]*$th/$img[1];
		}
		if ($img[0] > $img[1]){
			$tw=$max_width; $th=$img[1]*$tw/$img[0];
		}
		if ($img[0] == $img[1]){
			$tw=$max_width; $th=$max_width;
		}
	}
	$img_prop = array();
	$img_prop['ext']=$img[2];
	$img_prop['ow']=$img[0];
	$img_prop['oh']=$img[1];
	$img_prop['tw']=$tw;
	$img_prop['th']=$th;

	return $img_prop;
}

/**
 * convert and output image file
 * @param string $self_dir
 * @param string $filename
 * @param number $img_max_width
 * @return boolean
 */
function img_out($self_dir,$filename,$img_max_width){
	$file_full_path = $self_dir.'/'.$filename;
	$filename_array = array_reverse(explode('.',$filename));
	$ext = $filename_array[0];
	$ext_img_index = array_search($ext, array('jpg','png','gif','bmp'));
	if($ext_img_index === false){
		return false;
	}
	$img_p = imgfileinfo($filename,$img_max_width);
	$tw = intval($img_p['tw']);
	$th = intval($img_p['th']);
	$ow = intval($img_p['ow']);
	$oh = intval($img_p['oh']);
	$img_o = null;
	$img_t = null;
	if ($gdv){
		$img_t = imagecreate($tw,$th);
	} else { $img_t = imagecreatetruecolor($tw,$th);
	}

	switch($ext_index){
		case 0:
		default:{
			$img_o = imagecreatefromjpeg($file_full_path);	break;
		}
		case 1:{
			$img_o = imagecreatefrompng($file_full_path);	break;
		}
		case 2:{
			$img_o = imagecreatefromgif($file_full_path);	break;
		}
		case 3:{
			$img_o = imagecreatefromwbmp($file_full_path);	break;
		}
	}
	ImageCopyResized( $img_t,$img_o,0,0,0,0,$tw,$th,$ow,$oh);
	switch($ext_index){
		case 0:
		default:{
			header("content-type: image/jpeg");	imagejpeg($img_t,null,75);	break;
		}
		case 1:{
			header("content-type: image/png");	imagepng($img_t,null,9);	break;
		}
		case 2:{
			header("content-type: image/gif");	imagegif($img_t);	break;
		}
		case 3:{
			header("content-type: image/bmp");	imagewbmp($img_t);	break;
		}
	}
	ImageDestroy($img_o);
	ImageDestroy($img_t);
	return true;
}

/**
 * get file list in directory
 * @param string $basename
 * @param string $self_dir
 * @param string $filename
 * @param boolean $humanistic
 * @param string $date_format
 * @return boolean|multitype:string
 */
function list_dir($basename,$self_dir,$filename,$humanistic = true,$date_format = 'Y/m/d H:i:s'){
	$file_full_path = $self_dir.'/'.$filename;
	$file_info = pathinfo($file_full_path);
	if(!is_dir($file_full_path)){
		return false;
	}
	$files = array();

	$directry_info = dir($file_full_path);
	while( $file = $directry_info->read() ){
		$file_path = $file_full_path.'/'.$file;
		$file_info = pathinfo($file_path);
		$filename_array = array_reverse(explode('.',$file));
		$ext = $filename_array[0];
		$ext_img_index = array_search($ext, array('txt','htm','html'));
		if(!is_dir($file_path) && $ext_img_index !== false){
			$filesize = filesize($file_path);
			if($humanistic){
				$filesize = getFileSizeString($filesize);
			}
			$date = date($date_format,filemtime($file_path));
			switch($ext_img_index){
				case 0:
					$files[] = '<a href="'.$basename.'?f='.$file.'">'.$file.'</a>|'.$date.'|'.$filesize;
					break;
				default:
					$files[] = '<a href="'.$file.'">'.$file.'</a>|'.$date.'|'.$filesize;
					break;
			}
		}
	}
	$directry_info->close();
	sort($files);
	return $files;
}

/**
 * get file size as humanistic
 * @param number $filesize
 * @return string
 */
function getFileSizeString($filesize = 0){
	if($filesize < 1000){
		return $filesize . ' b';
	}
	$ts_1 = round($filesize/1000,1);
	$ts_2 = round($filesize/1000000,2);
	if($ts_2 > 1){
		return $ts_2 . ' Mb';
	}
	return $ts_1.' Kb';
}

//=========================================================================
$ENCODE_SRC = 'SJIS';
$ENCODE_DST = 'UTF-8';
$BYTES_PER_PAGE = 4000;
$IMG_MAX_WIDTH = 128;
$self_dir = dirname(__FILE__);
$basename = pathinfo(__FILE__,PATHINFO_BASENAME);
$filename = isset($_GET['f']) ? $_GET['f'] : '';
$file_full_path = $self_dir.'/'.$filename;
$current_page = isset($_GET['p']) ? $_GET['p'] : '1';

$title = pathinfo(__FILE__,PATHINFO_FILENAME);

if(img_out($self_dir,$filename, $IMG_MAX_WIDTH)){
	exit;
}
$contents_array = aozora_to_html($basename,$ENCODE_SRC, $ENCODE_DST, $file_full_path);
if(!$contents_array){
	$contents_array = list_dir($basename, $self_dir, $filename);
	if(strlen($filename)!=0){
		$title .= ' - ' .$filename;
	}
}else{
	$title .= ' - ' .$contents_array[0];
}

$contents = split_with_bytes($contents_array,$BYTES_PER_PAGE);

$paging_link = get_paging_links($basename.'?f='.$filename.'&p=',count($contents),$current_page,10);

$current_contents = $contents[$current_page-1];
echo '<?xml version="1.0" encoding="'.$ENCODE_DST.'"?>';
?>
<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?=$ENCODE_DST?>" />
<title><?=$title?></title>
<style type="text/css">
body {
	margin: 0;
	padding: 0;
	line-height: 1.4;
	color: #333;
	font-family: Arial, sans-serif;
	font-size: 1em;
}
</style>
</head>
<body>
	<div>
		<a name="header" id="header"></a> <a href="#footer" accesskey="8" directkey="8" nonumber="nonumber">[8]↓</a> | <a href="<?=$basename?>" acceskey="0" directkey="0" nonumber="nonumber">[0]戻る</a>
		<hr size="2" width="100%" />
		<?=get_bytes($current_contents)?>/<?=get_bytes($contents_array)?> bytes <?=$paging_link?>
		<hr size="1" width="100%" />
		<?php foreach($current_contents as $index=>$line){?>
		<?= $line?><br />
		<?php }?>
		<hr size="1" width="100%" />
		<?=get_bytes($current_contents)?>/<?=get_bytes($contents_array)?> bytes <?=$paging_link?>
		<hr size="2" width="100%" />
		<a href="#header" accesskey="2">[2]↑</a> | <a href="<?=$basename?>" acceskey="0" directkey="0" nonumber="nonumber">[0]戻る</a><a name="footer" id="footer"></a>
	</div>
</body>
</html>
