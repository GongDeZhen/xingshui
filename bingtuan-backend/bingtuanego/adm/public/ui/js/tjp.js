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
})