<?php
/**
 * Provide a admin area view for the plugin
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @since      1.0.0
 * @package    Triibo_Auth
 * @subpackage Triibo_Auth/Templates
 * @author     Mateus Costa <mateus@costamateus.com.br>
 */
?>

<style>
	.form-table tr:nth-child( 1 ) th, .form-table tr:nth-child( 1 ) td { padding-top:0!important; padding-bottom:5px !important; }
	.form-table tr:nth-child( 2 ) th, .form-table tr:nth-child( 2 ) td { padding-top:0!important; }
</style>

<div class="wrap">
	<h2>Triibo Auth</h2>

	<?php settings_errors(); ?>

	<form method="POST" action="options.php">
		<?php
			settings_fields( "triibo_auth_settings" );
			do_settings_sections( "triibo_auth_settings" );
			submit_button();
		?>
	</form>
</div>
