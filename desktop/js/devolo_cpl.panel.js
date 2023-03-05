
$('.mainnav li:first-child').addClass('active')

function hideall(){
	$('#div_devolo_cpl > div').hide()
	$('.mainnav li').removeClass('active')
}

$('.mainnav li[data-panel]').on('click',function(){
	hideall()
	panelId = $(this).data('panel')
	$(this).addClass('active')
	$('#' + panelId).show()
})
