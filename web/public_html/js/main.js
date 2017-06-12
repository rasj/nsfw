
$(function(){

	$(document).on('click','.ver-original',function(){
		$(this).parents('.parentVerOriginal:first').find('.img-original').removeClass('original-hidden').addClass('original-visible');
	});

	$(document).on('click','.ocultar-original',function(){
		$(this).parents('.parentVerOriginal:first').find('.img-original').removeClass('original-visible').addClass('original-hidden');
	});
});