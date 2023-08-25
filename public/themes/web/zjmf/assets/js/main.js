var jj = {};
//初始化
jj.init = function(){
	jj.events();		//事件总体控制
}
//事件总体控制
jj.events = function(){
	jj.nav();
}

//导航
jj.nav = function(){
	var proSortWidth = 0;
	$('.navbar-toggle').click(function(){
		$('body,html').toggleClass('open');
	});
	$('.product-nav li').each(function(){
		proSortWidth += $(this).width() + 40;
	})
	$('.product-nav').width(proSortWidth-20);
	$(".Pro_Anchor ul li a").click(function() {
	    $("html, body").animate({
	      scrollTop: $($(this).attr("href")).offset().top-30 + "px"
	    }, {
	      duration: 500,
	      easing: "swing"
	    });
	    return false;
	});
}


//dom加载完毕执行
$(function(){
	jj.init();
});


