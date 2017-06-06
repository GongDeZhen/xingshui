

function uploadImagse() {

    // 引入插件
    var uploader = new plupload.Uploader({

        runtimes : 'html5,flash,silverlight,html4',
        browse_button : 'pickfiles',
        container: document.getElementById('container'),
        url : '/exhibit/upload/',
        flash_swf_url : '../../js/plupload/Moxie.swf',
        silverlight_xap_url : '../../js/plupload/Moxie.xap',
        filters : {
            max_file_size : '10mb',
            mime_types: [
                {title : "Image files", extensions : "jpg,gif,png"},
                {title : "Zip files", extensions : "zip"}
            ]
        },

        init: {
            PostInit: function() {

            },

            FilesAdded: function(up) {

                uploader.start();
            },

            BeforeUpload: function(uploader, file) {
                var html = '';
                html += '<li class="newPic">0%<p>' + file.name + '</p></li>';
                $(html).appendTo('.showProduc');
            },

            UploadProgress: function(uploader,file) {

                $('.newPic').html(file.percent + '%<p>' + file.name + '</p>');
            },

            // 每上传一个文件执行一次
            FileUploaded: function(up, files, res) {
                res = eval("("+res['response']+")");
                if (res.id != 0) {
                    var html = '';
                    html += '<li title="' + res.result.name + '">';
                    html += '   <img src="' + res.result.hash + '" />';
                    html += '   <input type="hidden" name="pro[]" value="' + res.id + '" />';
                    html += '   <p title="' + res.result.name + '">' + res.result.name.substring(0,5) + '</p>';
                    html += '   <span></span>';
                    html += '</li>';
                    $(html).prependTo('.showProduc');
                    $('.newPic').remove();
                } else {
                    alert('上传失败')
                }
            },

            // 全部上传完成后执行
            UploadComplete: function(uploader, files) {
                $('.newPic').remove();
            },

            Error: function(up, err) {
                //document.getElementById('console').innerHTML += "\nError #" + err.code + ": " + err.message;
            }
        }
    });
    uploader.init();
}