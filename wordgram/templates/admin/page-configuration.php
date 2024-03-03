<?php

use WordgramPlugin\Admin\Admin;

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( "You're not allowed to access this page." );
}

$wordgram_logo = plugins_url( '/assets/images/wordgram-logo.png', WORDGRAM_PLUGIN_FILE );
?>

<div class="wrap <?php echo Admin::CONFIGURATIONS_SUBMENU_SLUG; ?>-container">
    <img class="wordgram-logo" src="<?php echo $wordgram_logo; ?>" alt="wordgram logo"
         width="120">
    <section class="not-connected" style="display: none;">
        <p>
			<?php
			_e( "Let’s connect to your Wordgram account and import the drop shipping catalog " .
			    "into your website. This should only take a minute!", 'wordgram' );
			?>
        </p>
        <button class="button button-primary connect"
                data-url="<?php echo esc_url( Admin::get_wordgram_connect_url() ); ?>">
			<?php _e( 'Connect to Wordgram', 'wordgram' ); ?>
        </button>
    </section>
    <section class="testing">
        <span class="spinner is-active"></span>
        <p>
			<?php _e( 'Testing connection to Wordgram servers...', 'wordgram' ); ?>
        </p>
        <p class="error" style="display: none;">
			<?php _e( 'Failed to test the connection.' ); ?>
            <a href="<?php echo admin_url( 'admin.php?page=' . Admin::CONFIGURATIONS_SUBMENU_SLUG ); ?>">
				<?php _e( 'Reload', 'wordgram' ); ?>
            </a>
        </p>
    </section>
    <section class="connected" style="display: none;">
        <p>
			<?php _e( 'This website is connected to your Wordgram account' ); ?>
            &lpar;<strong class="email"></strong>&rpar;
        </p>
        <button class="button button-secondary disconnect">
			<?php _e( 'Disconnect', 'wordgram' ); ?>
        </button>
    </section>
</div>
<style>
    .wordgram-logo {
        margin-bottom: 1em;
    }

    .testing {
        display: flex;
        align-items: center;
    }

    .testing .spinner {
        margin: 0;
    }

    .testing p {
        margin-left: 1em;
    }

    .wordgram-configurations-container p.error {
        color: #aa0000;
    }
</style>
