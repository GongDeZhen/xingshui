$(function(){

	//全选
	$("#checkall").click(function(){
		var $this=$(this);
		$this.toggleClass("icon-check-on");
		if ($this.hasClass("icon-check-on")) {
			$(".check-box").find(".icon-check-gray").addClass("icon-check-on");
		}
		else{
			$(".check-box").find(".icon-check-gray").removeClass("icon-check-on");
		}
	});
	//单选
	$(".check-box").click(function(){
		$(this).find(".icon-check-gray").toggleClass("icon-check-on");
		if(!($(".icon-check-gray").hasClass(".icon-check-on"))){
			$("#checkall").removeClass("icon-check-on");
		}
	});
	//订单详情展示隐藏
	var flag=1;
	$(".toogle-goods").click(function(){
		var liList=$(".dingdan-d li").length;
		var liFirst=$(".dingdan-d li")[0];
		if(flag){
			$(this).parents().find('.dingdan-d li').hide();
			$(liFirst).show();
			$(this).find('p').html('点击展开全部'+liList+'件商品<span class="icon icon-down-cart "></span>');
			flag=0;
		}else{
			$(this).parents().find('.dingdan-d li').show();
			$(this).find('p').html('点击展开全部<span class="icon  icon-top-cart"></span>');
			flag=1;
		}		
	})
})