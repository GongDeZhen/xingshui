<?php
/*
Uploadify
Copyright (c) 2012 Reactive Apps, Ronnie Garcia
Released under the MIT License <http://www.opensource.org/licenses/mit-license.php> 
*/

// Define a destination
if (isset($_POST['path'])) {
	$targetFolder = $_POST['path']; // Relative to the root
}else{
	$targetFolder = '../../data'; // Relative to the root
}

//$verifyToken = md5('unique_salt' . $_POST['timestamp']);

//if (!empty($_FILES) && $_POST['token'] == $verifyToken) {
	$tempFile = $_FILES['Filedata']['tmp_name'];
	//echo is_uploaded_file($tempFile);
	$targetPath = $targetFolder;
    $md5file = md5_file($tempFile);
   
    $to = substr($md5file , 0 , 2);
    $four = substr($md5file , 2 , 2);
    $explode = array_reverse(explode('.',$_FILES['Filedata']['name']));
    switch (strtolower($explode[0])){
    	case 'jpg':
    	case 'jpeg':
    	case 'png':
    	case 'gif':
    		$ext = 'jpg';
    		break;
    	default:
    		$ext = $explode[0];
    }
    $targetFile = rtrim($targetPath,'/') . '/' .$to.'/'.$four.'/'. $md5file.'.'.$ext;
	$images = explode('/',$targetFile);
	 
	//print_r($images);
    if(!file_exists('../../data'.'/'.$to.'/'.$four)){
        mkdir("../../data".'/'.$to.'/'.$four,0777,true);
   }   
   // Validate the file type
    $fileParts = pathinfo($_FILES['Filedata']['name']);
    if ($fileParts['extension']) {
     move_uploaded_file($tempFile,$targetFile);
     $str = substr($targetFile,5);
    echo $md5file.'.'.$explode[0].'||'.$str;
    } else {
    	echo '2';
   }
?>