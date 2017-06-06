<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>UploadiFive Test</title>
<script src="jquery-1.10.0.min.js" type="text/javascript"></script>
<script src="jquery.uploadify.min.js" type="text/javascript"></script>

<link rel="stylesheet" type="text/css" href="uploadify.css">
<style type="text/css">
body {
	font: 13px Arial, Helvetica, Sans-serif;
}
</style>
</head>

<body>
	<h1>Uploadify Demo</h1>
	<form enctype="multipart/form-data">
		<div id="queue"></div>
		<input id="file_upload" name="file_upload" type="file" >
	</form>

	<script type="text/javascript">
		$(function() {
			$('#file_upload').uploadify({
				'swf'      : 'uploadify.swf',
				'uploader' : 'uploadify.php',
				'onUploadSuccess' : function(file, data, response) {
		        }
			});
		});
	</script>
</body>
</html>