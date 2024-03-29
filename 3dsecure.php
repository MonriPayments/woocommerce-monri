<?php

require __DIR__ . '/../../../wp-load.php';

if ( ! isset( $_GET['acsUrl'], $_GET['pareq'], $_GET['returnUrl'], $_GET['token'] ) ) {
	return;
}
?>
<!DOCTYPE html>
<html>
	<head>
        <title><?php esc_html_e( 'Monri 3D Secure Verification' ) ?></title>
	</head>
	<body style="display:none">
        <p><?php esc_html_e( 'Invoking 3-D secure form, please wait ...', 'monri' ); ?></p>
		<form id="3ds-redirect" name="form" action="<?php echo esc_url($_GET['acsUrl']) ?>" method="post">
			<input  class="form-control" type="hidden" name="PaReq" value="<?php echo esc_attr( $_GET['pareq'] ) ?>">
			<input  class="form-control" type="hidden" name="TermUrl" value="<?php echo esc_attr( $_GET['returnUrl'] ) ?>">
			<input  class="form-control" type="hidden" name="MD" value="<?php echo esc_attr( $_GET['token'] ) ?>">

			<noscript>
				<input type="submit"/>
			</noscript>
		</form>
		<script>document.form.submit();</script>
	</body>
</html>
