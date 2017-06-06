
//图文详情多图片上传
$(function()
{
	var j=1;
	$('#add_file_one').click(function()
	{
		var inputs = document.getElementsByName("goods_desc_img[]");

		$('#add_file_one').before("<div class='upimg col-xs-12 left'><input type='file' name='goods_desc_img[]' id='file' class='col-xs-6 left'><a class='col-xs-6' onclick='($(this).parent().remove(),delImg())'>删  除</a></div>");
		if (inputs.length >= 10) {
			$("#add_file_one").attr("style","display:none");
		}
	})
});
function delImg() {
	var inputs = document.getElementsByName("goods_desc_img[]");
	if (inputs.length < 10) {
		$("#add_file").attr("style","display:block;width:21%");
	} 
}
//商品大图多图片上传
$(function()
{
	var j=1;
	$('#add_file_two').click(function()
	{
		var inputs = document.getElementsByName("original_img[]");

		$('#add_file_two').before("<div class='upimg col-xs-12 left'><input type='file' name='original_img[]' class='col-xs-6 left'><a class='col-xs-6' onclick='($(this).parent().remove(),delImg())'>删  除</a></div>");
		if (inputs.length >= 10) {
			$("#add_file_two").attr("style","display:none");
		}
	})
});
function delImg() {
	var inputs = document.getElementsByName("original_img[]");
	if (inputs.length < 10) {
		$("#add_file_two").attr("style","display:block;width:21%");
	} 
}
//地区
$(document).on("change", '#province',function() {
	var province = $(this).val();
    var city = $('#city');
    var area = $('#area');
    if (province == 0) {
      city.html('<option value="">请选择</option>');
      area.html('<option value="">请选择</option>');
      return;
    }

    $.post('/region/getCitylist',{'province':province},function(result) {
    	var cityHtml = '';
    	var areaHtml = '';
        if(result.success == 1) {
          	for(var i=0;i<result.data.data.city.length;i++){
          		cityHtml += '<option value="'+result.data.data.city[i].id+'">'+result.data.data.city[i].name+'</option>';
          	}
          	city.html(cityHtml);
          	areaHtml += '<option value="">请选择</option>';
          	for(var i=0;i<result.data.data.area.length;i++){
          		areaHtml += '<option value="'+result.data.data.area[i].id+'">'+result.data.data.area[i].name+'</option>';
          	}
        	area.html(areaHtml);
        } else {
        	cityHtml += '<option value="">请选择</option>';
        }
    },'json');
});

//$(document).on("change", '#city',function() {
//	var city = $('#city').val();
//    var area = $('#area');
//    $.post('/region/getArealist',{'city':city},function(result) {
//    	var html = '';
//        if(result.success == 1) {
//        	html += '<option value="">请选择</option>';
//          	for(var i=0;i<result.data.data.length;i++){
//          	    html += '<option value="'+result.data.data[i].id+'">'+result.data.data[i].name+'</option>';
//    	area.html(html);
//          	}
//        }
//    },'json')
//});

//公司、经销商
$(document).on("change", '#company',function() {
	var company = $(this).val();
    var warehouse = $('#warehouse');
    if (company == 0) {
    	warehouse.html('<option value="0">请选择</option>');
      return;
    }

    $.post('/region/getWarehouseList',{'company':company},function(result) {
    	var warehouseHtml = '';
        if(result.success == 1) {
        	if (result.data.length == 0) {
        		warehouseHtml += '<option value="0">请选择</option>';
        	} else {
        		warehouseHtml += '<option value="0">请选择</option>';
              	for(var i=0;i<result.data.length;i++){
              		warehouseHtml += '<option value="'+result.data[i].id+'">'+result.data[i].name+'</option>';
              	}
        	}
          	warehouse.html(warehouseHtml);
        } else {
        	warehouseHtml += '<option value="0">请选择</option>';
        }
    },'json');
});

//$(document).on("change", '#warehouse',function() {
//	var warehouse = $('#warehouse').val();
//    $.post('/region/getWarehouseList',{'warehouse':warehouse},function(result) {
//    	var html = '';
//        if(result.success == 1) {
//        	html += '<option value="0">请选择</option>';
//          	for(var i=0;i<result.data.data.length;i++){
//          	    html += '<option value="'+result.data.data[i].id+'">'+result.data.data[i].name+'</option>';
//          	}
//        }
//    },'json')
//});