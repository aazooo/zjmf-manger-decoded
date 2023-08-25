<input hidden="hidden" value="{$errorMsg}" id="errorMsg" />
<script>
	$(function() {
		let errorMsg = document.getElementById('errorMsg').value;
		if (errorMsg) {
			Toast(errorMsg);
		}
	});
</script>
</body>
</html>
