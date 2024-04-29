<?php

use WordgramPlugin\Admin\Admin;

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( "You're not allowed to access this page." );
}

$wordgram_logo = plugins_url( '/assets/images/wordgram-logo.png', WORDGRAM_PLUGIN_FILE );
?>
<link href="<?php echo plugins_url( '/assets/bootstrap-5.3.3-dist/css/bootstrap.min.css', WORDGRAM_PLUGIN_FILE ); ?>" rel="stylesheet">
<script src="<?php echo plugins_url( '/assets/scripts/jquery-3.7.1.min.js', WORDGRAM_PLUGIN_FILE ); ?>"></script>
<script src="<?php echo plugins_url( '/assets/scripts/popper-2.11.8.min.js', WORDGRAM_PLUGIN_FILE ); ?>"></script>
<script src="<?php echo plugins_url( '/assets/bootstrap-5.3.3-dist/js/bootstrap.min.js', WORDGRAM_PLUGIN_FILE ); ?>"></script>
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

        <form class="mt-3" method="post">
            <div class="row form-control">
                <div class="col-12 h4">
                    Sync Options
                </div>
                <div class="col-12 mt-3">
                    <div class="form-switch">
                        <input class="form-check-input" name="useDefaultSyncOptions" type="checkbox" role="switch" id="useDefaultSyncOptions" checked>
                        <label class="form-check-label" for="useDefaultSyncOptions">Default setting</label>
                    </div>
                </div>
                <div class="col-12 collapse mt-3" id="collapseSyncOptions">
                    <div class="row">
                        <div class="col-4">
                            <div class="form-switch">
                                <input class="form-check-input sync-options-true" type="checkbox" role="switch" name="updateTitle" id="updateTitle" checked>
                                <label class="form-check-label" for="updateTitle">Update Titles</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-switch">
                                <input class="form-check-input sync-options-true" type="checkbox" role="switch" name="updateDescription" id="updateDescription" checked>
                                <label class="form-check-label" for="updateDescription">Update Descriptions</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-switch">
                                <input class="form-check-input sync-options-true" type="checkbox" role="switch" name="updateTag" id="updateTag" checked>
                                <label class="form-check-label" for="updateTag">Update Tags</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-switch">
                                <input class="form-check-input sync-options-true" type="checkbox" role="switch" name="updateImage" id="updateImage" checked>
                                <label class="form-check-label" for="updateImage">Update Images</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-switch">
                                <input class="form-check-input sync-options-true" type="checkbox" role="switch" name="updatePrice" id="updatePrice" checked>
                                <label class="form-check-label" for="updatePrice">Update Prices</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-switch">
                                <input class="form-check-input sync-options-true" type="checkbox" role="switch" name="updateQuality" id="updateQuality" checked>
                                <label class="form-check-label" for="updateQuality">Update Qualities</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-switch">
                                <input class="form-check-input sync-options-false" type="checkbox" role="switch" name="updateAllPosts" id="updateAllPosts">
                                <label class="form-check-label" for="updateAllPosts"> Update All Posts
                                        <small class="text-danger mr-2">(If not selected, that will only insert new posts)</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-4">
                            <label for="updateSKU"> Update Only This Post (SKU):
                                    <small class="text-danger mr-2">(Note: It is case sensitive)</small>
                                    <input class="form-control sync-options-text" type="text" placeholder="Enter SKU of post" name="updateSKU" id="updateSKU">
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-12 mt-3">
                    <button type="button" class="btn btn-success sync-shop">
                        <?php _e( 'Sync Shop', 'wordgram' ); ?>
                    </button>
                </div>
            </div>
        </form>
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
    .form-check-label {
        cursor: pointer;
    }
</style>
<script>
    $('#useDefaultSyncOptions').change(function() {
        if(this.checked) {
            $('#collapseSyncOptions').collapse('hide');
            $('.sync-options-true').prop('checked', true);
            $('.sync-options-false').prop('checked', false);
            $('.sync-options-text').val('');
        } else {
            $('#collapseSyncOptions').collapse('show');
        }
    });
</script>