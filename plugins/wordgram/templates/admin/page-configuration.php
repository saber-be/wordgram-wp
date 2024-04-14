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
        <form action="<?php echo(WORDGRAM_SERVICE_URL.'/register-shop'); ?>" method="post">
            <input type="text" id="instagram_username" required name="instagram_username" placeholder="Enter your instagram username">
            <input type="hidden" name="shop_name" required value="<?php echo get_bloginfo( 'name' ); ?>">
            <input type="hidden" name="platform" required value="WordPress/WooCommerce">
            <input type="hidden" name="platform_url" required value="<?php echo home_url(); ?>">
            <input type="hidden" name="redirect_url" required value="<?php echo admin_url( 'admin-post.php?action=wordgram-connect-response' ); ?>">
            <input type="hidden" name="state" value="<?php echo Admin::get_unique_identifier(); ?>">
            <input type="hidden" name="product_webhook_url" required value="<?php echo admin_url( 'admin-ajax.php?action=wordgram-product-hook' ); ?>">
            <input type="hidden" name="order_webhook_url" required value="<?php echo admin_url( 'admin-ajax.php?action=wordgram-order-hook' ); ?>">
            
        <button class="button button-primary connect" >
            <?php _e( 'Connect to Wordgram', 'wordgram' ); ?>
        </button>
        
    </form>
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
            &lpar;<strong class="instagram_username"></strong>&rpar;
        </p>
        <button class="button button-secondary disconnect">
			<?php _e( 'Disconnect', 'wordgram' ); ?>
        </button>
        <button class="button button-success sync-shop">
			<?php _e( 'Sync Shop', 'wordgram' ); ?>
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
