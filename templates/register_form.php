<div id="register-form" class="widecolumn">
	<?php if( $attributes['show_title'] ): ?>
		<h3><?php echo _e( 'Register', 'onex-login' ); ?></h3>
	<?php endif; ?>

    <?php if( count( $attributes['errors'] ) > 0 ): ?>
        <?php foreach( $attributes['errors'] as $error ): ?>
            <p><?php echo $error; ?></p>
        <?php endforeach; ?>
    <?php endif; ?>

	<form id="signupform" action="<?php echo wp_registration_url(); ?>" method="post">
        <!-- <p class="form-row">
            <label for="username"><?php //_e( 'Username', 'onex-login' ); ?> <strong>*</strong></label>
            <input type="text" name="username" id="username">
        </p> -->
		<p class="form-row">
            <label for="email"><?php _e( 'Email', 'onex-login' ); ?> <strong>*</strong></label>
            <input type="text" name="email" id="email">
        </p>
 
        <p class="form-row">
            <label for="first_name"><?php _e( 'First name', 'onex-login' ); ?></label>
            <input type="text" name="first_name" id="first-name">
        </p>
 
        <p class="form-row">
            <label for="last_name"><?php _e( 'Last name', 'onex-login' ); ?></label>
            <input type="text" name="last_name" id="last-name">
        </p>
 
        <p class="form-row">
            <?php _e( 'Note: Password akan dikirimkan email yang anda masukkan.', 'onex-login' ); ?>
        </p>
 
        <p class="signup-submit">
            <input type="submit" name="submit" class="register-button"
                   value="<?php _e( 'Register', 'onex-login' ); ?>"/>
        </p>
	</form>
</div>