<link type="text/css" href="{$Setting.web_view}/assets/libs/toastr/build/toastr.min.css" rel="stylesheet" />
<script src="{$Setting.web_view}/assets/libs/toastr/build/toastr.min.js"></script>

<script type="text/javascript">
	$(function () {
		toastr.success('[value]');
		setTimeout(function () {
			var url = '[url]'
			if (url) {
				location.href = url
			}
		}, 500);
	});
</script>