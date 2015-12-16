<div class="login-form-container">
	<?php if( $attributes['show_title'] ) : ?>
		<h2><?php _e( 'Sign In', 'onex-login' ); ?></h2>
	<?php endif; ?>
	<!-- Show errors if there are any -->
	<?php if ( count( $attributes['errors'] ) > 0 ) : ?>
		<?php foreach ( $attributes['errors'] as $error ) : ?>
			<p class="login-error">
				<?php echo $error; ?>
			</p>
		<?php endforeach; ?>
	<?php endif; ?>
	<!-- Show logged out message if user just logged out -->
	<?php if( $attributes['logged_out'] ) : ?>
		<p class="login-info">
			<?php _e( 'You have signed out. Would you like to sign in again?', 'onex-login'); ?>
		</p>
	<?php endif; ?>

	<?php if( $attributes['registered'] ): ?>
		<p class="login-info">
			<?php
				printf(
					__( 'You have successfully registered to <strong>%s</strong>. We have emailed your password to the email address you entered.', 'onex-login'),
					get_bloginfo( 'name' )
				);
			?>
		</p>
	<?php endif; ?>

	<?php
		wp_login_form(
			array(
				'label_username' => __( 'Email', 'onex-login' ),
				'label_log_in' => __( 'Sign In', 'onex-login' ),
				'redirect' => $attributes['redirect'],
			)
		);
	?>

	<a class="forgot-password" href="<?php echo wp_lostpassword_url(); ?>" >
		<?php _e( 'Forgot your password?', 'onex-login' ); ?>
	</a>
</div>