<?php


namespace WordgramPlugin\Admin;


use Automattic\WooCommerce\RestApi\Utilities\ImageAttachment;

class Admin {

	const WORDGRAM_CONNECT_NONCE_OPTION = 'wordgram_connect_nonce';
	const WORDGRAM_API_KEY_OPTION = 'wordgram_api_key';
	const WC_ORDER_CREATED_WEBHOOK_ID_OPTION = 'wordgram_order_created_webhook_id';
	const WC_ORDER_UPDATED_WEBHOOK_ID_OPTION = 'wordgram_order_updated_webhook_id';
	const WORDGRAM_PRODUCT_IDS_OPTION = 'wordgram_product_ids';

	const CONFIGURATIONS_SUBMENU_SLUG = 'wordgram-configurations';

	const BATCH_PROCESSING_LIMIT = 100;

	private static $api_key_data;

	public static function init_hooks() {
		add_action( 'admin_menu', [ __CLASS__, 'wordgram_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ] );
		add_action( 'admin_print_footer_scripts', [ __CLASS__, 'print_footer_scripts' ] );

		add_action( 'admin_post_wordgram-connect-response', [ __CLASS__, 'wordgram_connect_response' ] );
		add_action( 'admin_post_nopriv_wordgram-connect-response', [ __CLASS__, 'wordgram_connect_response' ] );

		add_action( 'wp_ajax_wordgram-disconnect', [ __CLASS__, 'wordgram_disconnect' ] );

		add_action( 'wp_ajax_wordgram-sync-shop', [ __CLASS__, 'wordgram_sync_shop' ] );

		add_action( 'wp_ajax_wordgram-test-connection', [ __CLASS__, 'wordgram_test_connection' ] );

		add_action( 'wp_ajax_wordgram-product-hook', [ __CLASS__, 'wordgram_product_hook' ] );
		add_action( 'wp_ajax_nopriv_wordgram-product-hook', [ __CLASS__, 'wordgram_product_hook' ] );

		add_action( 'schedule_remove_wordgram_products', [ __CLASS__, 'schedule_remove_wordgram_products' ], 10, 2 );

		add_action( 'wp_ajax_wordgram-order-hook', [ __CLASS__, 'wordgram_order_hook' ] );
		add_action( 'wp_ajax_nopriv_wordgram-order-hook', [ __CLASS__, 'wordgram_order_hook' ] );

		add_filter( 'plugin_action_links_' . plugin_basename( WORDGRAM_PLUGIN_FILE ), [ __CLASS__, 'add_plugin_action_links' ] );

		add_filter( 'woocommerce_webhook_payload', [ __CLASS__, 'filter_wc_webhook_payload' ], 10, 4 );
	}

	public static function render_missing_or_outdated_wc_notice() {
		?>
        <div class="notice notice-error">
            <p>
                <strong>Wordgram Plugin</strong> plugin is a WooCommerce
                extension. Please install and activate
                <a href="https://wordpress.org/plugins/woocommerce/"
                   target="_blank">WooCommerce</a>
                for this plugin to function properly.
            </p>
        </div>
		<?php
	}

	public static function enqueue_scripts( $hook_suffix ) {
		if ( 'toplevel_page_' . self::CONFIGURATIONS_SUBMENU_SLUG === $hook_suffix ) {
			wp_enqueue_script(
				'wordgram-configuration',
				plugins_url( '/assets/scripts/admin/page-configuration.js', WORDGRAM_PLUGIN_FILE ),
				[ 'jquery' ],
				'1.0.1',
				true
			);
		}
	}

	public static function add_plugin_action_links( $actions ) {
		return array_merge( [
			'configure' => '<a href="' . admin_url( 'admin.php?page=' . self::CONFIGURATIONS_SUBMENU_SLUG ) . '">' . __( 'Configure', 'wordgram' ) . '</a>',
		], $actions );
	}

	public static function wordgram_admin_menu() {
		global $submenu;

		add_menu_page(
			__( 'Wordgram', 'wordgram' ),
			__( 'Wordgram', 'wordgram' ),
			'manage_options',
			self::CONFIGURATIONS_SUBMENU_SLUG,
			'',
			'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjU4IiBoZWlnaHQ9I' .
			'jI4NCIgdmlld0JveD0iMCAwIDI1OCAyODQiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRw' .
			'Oi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CiAgICA8Zz4KICAgICAgICA8Zz4KICAgICA' .
			'gICAgICAgPGc+CiAgICAgICAgICAgICAgICA8Zz4KICAgICAgICAgICAgICAgICAgIC' .
			'A8cGF0aCBkPSJNMjU3LjI5NCA4Ny44MDQ5QzI1Ny4yOTQgMTA3LjM3MyAyNDEuNDMzI' .
			'DEyMy4yMzQgMjIxLjg2NSAxMjMuMjM0QzIwMi4yOTcgMTIzLjIzNCAxODYuNDM0IDEw' .
			'Ny4zNzMgMTg2LjQzNCA4Ny44MDQ5QzE4Ni40MzQgNjguMjM2OSAyMDIuMjk3IDUyLjM' .
			'3NTYgMjIxLjg2NSA1Mi4zNzU2QzI0MS40MzMgNTIuMzc1NiAyNTcuMjk0IDY4LjIzNj' .
			'kgMjU3LjI5NCA4Ny44MDQ5WiIKICAgICAgICAgICAgICAgICAgICAgICAgICBmaWxsP' .
			'SIjZmZmZmZmIi8+CiAgICAgICAgICAgICAgICA8L2c+CiAgICAgICAgICAgICAgICA8' .
			'Zz4KICAgICAgICAgICAgICAgICAgICA8cGF0aCBkPSJNMTc0LjExMSAyMS41NjY3QzE' .
			'3NC4xMTEgMzMuNDc3NCAxNjQuNDU2IDQzLjEzMjEgMTUyLjU0NSA0My4xMzIxQzE0MC' .
			'42MzUgNDMuMTMyMSAxMzAuOTc5IDMzLjQ3NzQgMTMwLjk3OSAyMS41NjY3QzEzMC45N' .
			'zkgOS42NTYwOCAxNDAuNjM1IDguMzk1MTFlLTA1IDE1Mi41NDUgOC4zOTUxMWUtMDVD' .
			'MTY0LjQ1NiA4LjM5NTExZS0wNSAxNzQuMTExIDkuNjU2MDggMTc0LjExMSAyMS41NjY' .
			'3WiIKICAgICAgICAgICAgICAgICAgICAgICAgICBmaWxsPSIjZmZmZmZmIi8+CiAgIC' .
			'AgICAgICAgICAgICA8L2c+CiAgICAgICAgICAgICAgICA8Zz4KICAgICAgICAgICAgI' .
			'CAgICAgICA8cGF0aCBkPSJNMjE3Ljk1OSAxNTIuMTA2SDIxMy41MjNWMTUwLjUwM0gy' .
			'MjQuMzIxVjE1Mi4xMDZIMjE5Ljg2M1YxNjUuMDg4SDIxNy45NTlWMTUyLjEwNloiCiA' .
			'gICAgICAgICAgICAgICAgICAgICAgICAgZmlsbD0iI2ZmZmZmZiIvPgogICAgICAgIC' .
			'AgICAgICAgPC9nPgogICAgICAgICAgICAgICAgPGc+CiAgICAgICAgICAgICAgICAgI' .
			'CAgPHBhdGggZD0iTTIzOC4yMzcgMTU4LjY4M0MyMzguMTI5IDE1Ni42NDkgMjM3Ljk5' .
			'NyAxNTQuMjA0IDIzOC4wMjEgMTUyLjM4NUgyMzcuOTU0QzIzNy40NTcgMTU0LjA5NiA' .
			'yMzYuODUyIDE1NS45MTMgMjM2LjExNiAxNTcuOTI3TDIzMy41NDEgMTY1LjAwMUgyMz' .
			'IuMTEzTDIyOS43NTQgMTU4LjA1NUMyMjkuMDYxIDE1NiAyMjguNDc3IDE1NC4xMTcgM' .
			'jI4LjA2NiAxNTIuMzg1SDIyOC4wMjRDMjI3Ljk4IDE1NC4yMDQgMjI3Ljg3MiAxNTYu' .
			'NjQ5IDIyNy43NDIgMTU4LjgzNUwyMjcuMzUyIDE2NS4wODlIMjI1LjU1NkwyMjYuNTc' .
			'zIDE1MC41MDNIMjI4Ljk3NkwyMzEuNDYyIDE1Ny41NTdDMjMyLjA2OSAxNTkuMzU1ID' .
			'IzMi41NjYgMTYwLjk1NSAyMzIuOTM0IDE2Mi40NzFIMjMyLjk5OEMyMzMuMzY4IDE2M' .
			'C45OTcgMjMzLjg4NiAxNTkuMzk3IDIzNC41MzYgMTU3LjU1N0wyMzcuMTMzIDE1MC41' .
			'MDNIMjM5LjUzNkwyNDAuNDQyIDE2NS4wODlIMjM4LjYwNEwyMzguMjM3IDE1OC42ODN' .
			'aIgogICAgICAgICAgICAgICAgICAgICAgICAgIGZpbGw9IiNmZmZmZmYiLz4KICAgIC' .
			'AgICAgICAgICAgIDwvZz4KICAgICAgICAgICAgICAgIDxnPgogICAgICAgICAgICAgI' .
			'CAgICAgIDxwYXRoIGQ9Ik0xNDYuNjA0IDI1MS4wODJINTcuODA0VjIyMi41MjFIODcu' .
			'MjMwN1YxMzcuMjYySDY1LjkzMlYxMTAuODM4SDExNS42MjhWMjIyLjUyMUgxNDYuNjA' .
			'0VjI1MS4wODJaTTEwMi4yMDQgNzguNzU0M0M0NS43NTg3IDc4Ljc1NDMgMCAxMjQuNT' .
			'E0IDAgMTgwLjk2QzAgMjM3LjQwNiA0NS43NTg3IDI4My4xNjUgMTAyLjIwNCAyODMuM' .
			'TY1QzE1OC42NTEgMjgzLjE2NSAyMDQuNDA5IDIzNy40MDYgMjA0LjQwOSAxODAuOTZD' .
			'MjA0LjQwOSAxMjQuNTE0IDE1OC42NTEgNzguNzU0MyAxMDIuMjA0IDc4Ljc1NDNaIgo' .
			'gICAgICAgICAgICAgICAgICAgICAgICAgIGZpbGw9IiNmZmZmZmYiLz4KICAgICAgIC' .
			'AgICAgICAgIDwvZz4KICAgICAgICAgICAgPC9nPgogICAgICAgIDwvZz4KICAgIDwvZ' .
			'z4KPC9zdmc+Cg=='
		);
		add_submenu_page(
			self::CONFIGURATIONS_SUBMENU_SLUG,
			__( 'Configurations', 'wordgram' ),
			__( 'Configurations', 'wordgram' ),
			'manage_options',
			self::CONFIGURATIONS_SUBMENU_SLUG,
			[ __CLASS__, 'configurations_page' ]
		);
		$submenu[ self::CONFIGURATIONS_SUBMENU_SLUG ][] = [ __( 'Catalog', 'wordgram' ), 'manage_options', 'https://admin.wordgram.com/marketplace/catalog/grid' ];
	}

	public static function configurations_page() {
		include dirname( WORDGRAM_PLUGIN_FILE ) . '/templates/admin/page-configuration.php';
	}

	public static function print_footer_scripts() {
		?>
        <script>
            document.querySelector('#toplevel_page_<?php echo self::CONFIGURATIONS_SUBMENU_SLUG; ?> .wp-submenu li:last-of-type a').setAttribute('target', '_blank');
        </script>
		<?php
	}

	public static function create_wc_order_webhooks() {
		$api_key_data = self::get_api_key_data();
		if ( isset( $api_key_data['api_key'] ) ) {
			$topics       = [
				'order.created' => [
					'name'        => 'Send new order to Wordgram',
					'option_name' => self::WC_ORDER_CREATED_WEBHOOK_ID_OPTION,
				],
				'order.updated' => [
					'name'        => 'Send updated order to Wordgram',
					'option_name' => self::WC_ORDER_UPDATED_WEBHOOK_ID_OPTION,
				],
			];
			$delivery_url = add_query_arg( [
				'api_key'  => $api_key_data['api_key'],
				'state'    => $api_key_data['identifier'],
				'platform' => 'WordPress/WooCommerce',
			], 'https://admin.wordgram.com/api/orders/new' );
			$user_id      = get_current_user_id();

			// Remove any existing webhooks, so there are no orphaned
			// entries present and we don't create duplicates.
			self::remove_wc_order_webhooks();

			foreach ( $topics as $topic => $val ) {
				$webhook = new \WC_Webhook();
				$webhook->set_name( $val['name'] );
				$webhook->set_user_id( $user_id );
				$webhook->set_topic( $topic );
				$webhook->set_secret( self::generate_unique_identifier() );
				$webhook->set_delivery_url( $delivery_url );
				$webhook->set_status( 'active' );
				$webhook->save();
				update_option( $val['option_name'], $webhook->get_id(), false );
			}
		}
	}

	private static function remove_wc_order_webhooks() {
		$webhook_options = [
			self::WC_ORDER_CREATED_WEBHOOK_ID_OPTION,
			self::WC_ORDER_UPDATED_WEBHOOK_ID_OPTION,
		];
		foreach ( $webhook_options as $webhook_id_option ) {
			$webhook_id = get_option( $webhook_id_option );
			if ( $webhook_id ) {
				$webhook = null;
				try {
					$webhook = wc_get_webhook( $webhook_id );
				} catch ( \Exception $e ) {
					continue;
				}
				if ( $webhook ) {
					$webhook->delete( true );
					delete_option( $webhook_id_option );
				}
			}
		}
	}

	private static function is_active_wordgram_webhook( $webhook_id ) {
		return in_array( $webhook_id, [
			absint( get_option( self::WC_ORDER_CREATED_WEBHOOK_ID_OPTION ) ),
			absint( get_option( self::WC_ORDER_UPDATED_WEBHOOK_ID_OPTION ) ),
		], true );
	}

	public static function filter_wc_webhook_payload( $payload, $resource, $resource_id, $webhook_id ) {
		if ( self::is_active_wordgram_webhook( $webhook_id )
		     && $resource === 'order'
		     && isset( $payload['id'], $payload['order_key'], $payload['status'] )
		) {
			$identifier = $payload['order_key'];
			$payload    = [
				'platform_order_id'   => $payload['id'],
				'status'              => $payload['status'],
				'customer_first_name' => $payload['billing']['first_name'],
				'customer_last_name'  => $payload['billing']['last_name'],
				'customer_email'      => $payload['billing']['email'],
				'customer_phone'      => $payload['billing']['phone'],
				'billing_address'     => [
					'line_1'  => $payload['billing']['address_1'],
					'line_2'  => $payload['billing']['address_2'],
					'city'    => $payload['billing']['city'],
					'state'   => $payload['billing']['state'],
					'country' => $payload['billing']['country'],
					'zip'     => $payload['billing']['postcode'],
				],
				'sub_total'           => $payload['total'],
				'shipping_cost'       => $payload['shipping_total'],
				'discount'            => $payload['discount_total'],
				'tax'                 => $payload['total_tax'],
				'total'               => $payload['total'],
				'shipping_method'     => isset( $payload['shipping_lines'] ) && count( $payload['shipping_lines'] ) > 0
					? $payload['shipping_lines'][0]['method_title']
					: null,
				'payment_method'      => $payload['payment_method'],
				'currency'            => $payload['currency'],
				'products'            => array_map( function ( $product ) {
					return [
						'id'            => $product['id'],
						'sku'           => $product['sku'],
						'unit_price'    => wc_format_decimal( $product['price'] ),
						'qty'           => $product['quantity'],
						'line_total'    => $product['subtotal'],
						'tax'           => $product['total_tax'],
						'discount'      => null,
						'product_title' => $product['name'],
						'cost'          => null,
					];
				}, $payload['line_items'] ),
			];
			self::log_to_db( 'wordgram_wc_order_webhook', $identifier, $payload );
		}

		return $payload;
	}

	private static function log_to_db( $type, $identifier, $data ) {
		global $wpdb;

		return $wpdb->insert( "{$wpdb->prefix}wordgram_plugin_log", [
			'user_id'    => get_current_user_id(),
			'type'       => $type,
			'identifier' => $identifier,
			'payload'    => maybe_serialize( $data ),
		] );
	}

	public static function generate_unique_identifier() {
		return uniqid( wp_rand( 10000, 99999 ) );
	}

	public static function get_unique_identifier() {
		$state = get_option( self::WORDGRAM_CONNECT_NONCE_OPTION );
		if( $state ) {
			return $state;
		}
		$state = self::generate_unique_identifier();
		update_option( self::WORDGRAM_CONNECT_NONCE_OPTION, $state, false );
		return $state;
	}

	public static function get_wordgram_connect_url() {
		$identifier = self::generate_unique_identifier();
		update_option( self::WORDGRAM_CONNECT_NONCE_OPTION, $identifier, false );

		return add_query_arg( [
			'shop_name'           => get_bloginfo( 'name' ),
			'platform'            => 'WordPress/WooCommerce',
			'platform_url'        => home_url(),
			'redirect_url'        => admin_url( 'admin-post.php?action=wordgram-connect-response' ),
			'state'               => $identifier,
			'product_webhook_url' => admin_url( 'admin-ajax.php?action=wordgram-product-hook' ),
			'order_webhook_url'   => admin_url( 'admin-ajax.php?action=wordgram-order-hook' ),
		], WORDGRAM_SERVICE_URL.'/register-shop' );
	}

	public static function wordgram_connect_response() {
		$data = isset($_POST['data']) ? $_POST['data'] : [];
		if ( isset( $data['api_key'], $data['state'], $data['instagram_username'] )
		     && get_option( self::WORDGRAM_CONNECT_NONCE_OPTION )
		        === ( $identifier = sanitize_text_field( $data['state'] ) )
		) {
			$data = [
				'api_key'    => sanitize_text_field( $data['api_key'] ),
				'instagram_username' => sanitize_text_field( $data['instagram_username'] ),
				'identifier' => $identifier,
			];
			update_option( self::WORDGRAM_API_KEY_OPTION, $data, false );
			self::create_wc_order_webhooks();
			self::log_to_db( 'wordgram_connect_response', $identifier, $data );
            wp_send_json_success( [
				'code'    => 'connected',
				'message' => 'Connected successfully.',
			] );
		} else {
			wp_send_json_error();
		}
	}

	private static function get_api_key_data() {
		if ( empty( self::$api_key_data ) ) {
			self::$api_key_data = (array) get_option( self::WORDGRAM_API_KEY_OPTION, [] );
		}

		return self::$api_key_data;
	}

	private static function set_api_key_data($api_key) {
		update_option( self::WORDGRAM_API_KEY_OPTION, $api_key, false );
	}

	public static function remove_api_key_data() {
		delete_option( self::WORDGRAM_API_KEY_OPTION );
		self::$api_key_data = null;
	}

	public static function cleanup_on_disconnect() {
		self::remove_wc_order_webhooks();
		self::schedule_delete_all_wordgram_products();
		self::remove_api_key_data();
	}

	public static function wordgram_disconnect() {
		$api_key_data = self::get_api_key_data();
		if ( empty( $api_key_data ) ) {
			wp_send_json_error();
		}
		$response = wp_remote_post(WORDGRAM_SERVICE_URL . '/disconnect-shop', [
			'body' => json_encode([
				'api_key'  => $api_key_data['api_key'],
				'state'    => $api_key_data['identifier'],
				'instagram_username' => $api_key_data['instagram_username'],
				'platform' => 'WordPress/WooCommerce'
			]),
			'headers' => [
				'Content-Type' => 'application/json',
				'accept'       => 'application/json',
				'Accept-Encoding' => 'gzip, deflate, br',
			],
		]);
		if ( ! is_wp_error( $response ) && 200 === $response['response']['code'] ) {
			$body = json_decode( $response['body'] );
			if ( true === $body->success && $api_key_data['identifier'] === $body->state ) {
				self::cleanup_on_disconnect();
				self::log_to_db( 'wordgram_disconnect_response', $api_key_data['identifier'], $body );
				wp_send_json_success( [
					'code'    => 'disconnected',
					'message' => 'Disconnected successfully.',
				] );
			}
		}
		wp_send_json_error();
	}

	public static function wordgram_sync_shop() {
		$data = isset($_POST['data']) ? $_POST['data'] : [];
		$api_key_data = self::get_api_key_data();
		if ( empty( $api_key_data ) ) {
			wp_send_json_error();
		}
		$response = wp_remote_post(WORDGRAM_SERVICE_URL . '/sync-shop', [
			'body' => json_encode([
				'api_key'  => $api_key_data['api_key'],
				'state'    => $api_key_data['identifier'],
				'instagram_username' => $api_key_data['instagram_username'],
				'platform' => 'WordPress/WooCommerce',
				'update_price' => isset($data['updatePrice']) ? $data['updatePrice'] : 1,
				'update_title' => isset($data['updateTitle']) ? $data['updateTitle'] : 1,
				'update_quality' => isset($data['updateQuality']) ? $data['updateQuality'] : 1,
				'update_description' => isset($data['updateDescription']) ? $data['updateDescription'] : 1,
				'update_tags' => isset($data['updateTag']) ? $data['updateTag'] : 1,
				'update_images' => isset($data['updateImage']) ? $data['updateImage'] : 1,
			]),
			'headers' => [
				'Content-Type' => 'application/json',
				'accept'       => 'application/json',
				'Accept-Encoding' => 'gzip, deflate, br',
			],
		]);
		if ( ! is_wp_error( $response ) && 200 === $response['response']['code'] ) {
			$body = json_decode( $response['body'] );
			if ( true === $body->success && $api_key_data['identifier'] === $body->state ) {
				self::log_to_db( 'wordgram_sync_shop_response', $api_key_data['identifier'], $body );
				wp_send_json_success( [
					'code'    => 'synced',
					'message' => 'The sync process has started.',
				] );
			}
		}
		wp_send_json_error();
	}

	public static function wordgram_test_connection()
	{
		$api_key_data = self::get_api_key_data();
		if (empty($api_key_data)) {
			wp_send_json_success([
				'code'    => 'not_authenticated',
				'message' => __('Not authenticated.', 'wordgram'),
			]);
		}
		$response = wp_remote_post(WORDGRAM_SERVICE_URL . '/is-connect', [
			'body' => json_encode([
				'api_key'  => $api_key_data['api_key'],
				'state'    => $api_key_data['identifier'],
				'instagram_username' => $api_key_data['instagram_username'],
				'platform' => 'WordPress/WooCommerce'
			]),
			'headers' => [
				'Content-Type' => 'application/json',
				'accept'       => 'application/json',
				'Accept-Encoding' => 'gzip, deflate, br',
			],
		]);
		if (!is_wp_error($response)) {
			$body = json_decode($response['body']);
			if ($api_key_data['identifier'] === $body->state) {
				if (200 === $response['response']['code'] && $body && 'success' === $body->status) {
					wp_send_json_success([
						'code'       => 'verified_successfully',
						'instagram_username' => $api_key_data['instagram_username'],
						'message'    => $body->message
					]);
				} else {
					self::remove_api_key_data();
					wp_send_json_success([
						'code'    => 'not_found',
						'message' => 'No matching record found.',
					]);
				}
			}
		}
		wp_send_json_error();
	}

	public static function wordgram_order_hook() {
		$body   = self::parse_and_verify_json_body();
		$errors = [];
		if ( is_wp_error( $body ) ) {
			array_push( $errors, $body );
			wp_send_json_error( $errors );
		}

		if ( isset( $body['order']['order_id'], $body['order']['status'], $body['order'] ) ) {
			$wc_order = wc_get_order( $body['order']['order_id'] );
			if ( $wc_order ) {
				if ( isset( $body['order']['shipping_details']['tracking_number'] ) ) {
					if ( function_exists( 'wc_st_add_tracking_number' ) ) {
						wc_st_add_tracking_number(
							$wc_order->get_id(),
							$body['order']['shipping_details']['tracking_number'],
							$body['order']['shipping_details']['carrier'],
							$body['order']['shipping_details']['shipping_date']
						);
					} else {
						$wc_order->add_order_note(
							"Wordgram added tracking number:" . PHP_EOL .
							"Tracking Number: {$body['order']['shipping_details']['tracking_number']}" . PHP_EOL .
							"Carrier: {$body['order']['shipping_details']['carrier']}" . PHP_EOL .
							"Shipping Date: {$body['order']['shipping_details']['shipping_date']}"
						);
					}
				}
				$updated = $wc_order->update_status(
					$body['order']['status'],
					__( 'Order status updated by Wordgram webhook', 'wordgram' ),
					true
				);
				if ( $updated ) {
					$wc_order->save();
					wp_send_json_success();
				} else {
					array_push( $errors, new \WP_Error(
						'order_status_update_failed',
						"Order couldn't be updated to status: ${$body['order']['status']}."
					) );
				}
			} else {
				array_push( $errors, new \WP_Error(
					'order_not_found',
					"WC order with id {$body['order']['order_id']} couldn't be found."
				) );
			}
		} else {
			array_push( $errors, new \WP_Error( 'invalid_body', 'Missing or invalid body.' ) );
		}
		wp_send_json_error( $errors );
	}

	private static function parse_and_verify_json_body() {
		$output   = null;
		$json_str = file_get_contents( 'php://input' );
		if ( strlen( $json_str ) > 0 ) {
			$body = json_decode( $json_str, true );
			return $body; // remove it later to enable authentication
			if ( is_array( $body ) && isset( $body['state'], $body['action'], $body['api_key'] ) ) {
				$api_key_data = self::get_api_key_data();
				if ( ! empty( $api_key_data ) && $api_key_data['api_key'] === $body['api_key'] ) {
					return $body;
				}

				return new \WP_Error( 'authentication_failed', 'Failed to verify the authenticity of the request.' );
			}
		}

		return new \WP_Error( 'invalid_json', 'Missing or invalid JSON body.' );
	}

	private static function insert_products( $products, $is_update = true ) {
		$errors               = [];
		$inserted_product_ids = [];
		foreach ( $products as $product ) {

			$wc_product = new \WC_Product_Simple();
			
			$SKU = $product['SKU'] ?? null;
			try {
				if($SKU){
					$wc_product->set_sku($SKU);
				}
			} catch (\WC_Data_Exception $e) {
				if ($is_update) {
					$wc_product = wc_get_product(wc_get_product_id_by_sku($SKU));
					if (!is_a($wc_product, \WC_Product::class)) {
						array_push($errors, new \WP_Error(
							'product_not_found',
							"Product with SKU: {$SKU} couldn't be found."
						));
						continue;
					}
				} else {
					continue;
				}
			}
			if (isset($product['Name'])) {
				$wc_product->set_name($product['Name']);
			}
			if (isset($product['Description'])) {
				$wc_product->set_description($product['Description']);
			}
			if (isset($product['Price'])) {
				$wc_product->set_regular_price($product['Price']);
			}
			if (isset($product['QTY'])) {
				$wc_product->set_stock_quantity($product['QTY']);
			}
			$wc_product->set_manage_stock(true);
			if (isset($product['tags'])) {
				$tag_ids = [];
				$tag_names = [];
				foreach ($product['tags'] as $tag) {
					$tag_id = wp_create_tag($tag['name']);
					if (is_wp_error($tag_id)) {
						array_push($errors, $tag_id);
						continue;
					}
					if (is_array($tag_id)) {
						$tag_id = $tag_id['term_id'];
					}
					array_push($tag_names, $tag['name']);
					array_push($tag_ids, $tag_id);
				}
				$wc_product->set_tag_ids($tag_ids);
			}
			$wc_product->save();
			$wc_product_id = $wc_product->get_id();
			if (isset($product['tags'])) {
				wp_set_object_terms($wc_product_id, $tag_names, 'product_tag');
			}
			if (isset($product['Images'])) {
				$images        = [];
				foreach ($product['Images'] as $image) {
					$image_attachment = new ImageAttachment(0, $wc_product_id);
					try {
						$image_attachment->upload_image_from_src($image['url']);
						
					} catch (\WC_REST_Exception $e) {
						self::log_to_db('wordgram_image_upload_error', $wc_product_id, $image['url']);
						echo ("error" . $e->getMessage() . "<br>..." . $image['url']);
						array_push($errors, $e->getMessage());
						try {
							$alt_url = WORDGRAM_SERVICE_URL."/proxy?url=" . urlencode($image['url']);
							$image_attachment->upload_image_from_src($alt_url);
						} catch (\WC_REST_Exception $e2) {
							self::log_to_db('wordgram_image_upload_error', $wc_product_id, $alt_url);
							echo ("error" . $e2->getMessage() . "<br>..." . $alt_url);
							array_push($errors, $e2->getMessage());
							continue;
						}
					}
					array_push($images, $image_attachment->id);
				}
				if (count($images) > 0) {
					$wc_product->set_image_id(array_shift($images));
				}
				if (count($images) > 0) {
					$wc_product->set_gallery_image_ids($images);
				}
			}
			$wc_product->save();
			if (!$is_update) {
				array_push($inserted_product_ids, $wc_product_id);
			}
			self::log_to_db($is_update ? 'wordgram_update_product' : 'wordgram_add_product', $wc_product_id, $product);
		}
		if (!empty($inserted_product_ids)) {
			self::insert_wordgram_product_ids($inserted_product_ids);
		}

		return $errors;
	}

	private static function get_wordgram_product_ids() {
		return (array) get_option( self::WORDGRAM_PRODUCT_IDS_OPTION, [] );
	}

	private static function insert_wordgram_product_ids( $product_ids ) {
		return update_option(
			self::WORDGRAM_PRODUCT_IDS_OPTION,
			array_keys(
				array_fill_keys( self::get_wordgram_product_ids(), true ) +
				array_fill_keys( $product_ids, true )
			),
			false
		);
	}

	private static function remove_wordgram_product_ids( $product_ids ) {
		return update_option(
			self::WORDGRAM_PRODUCT_IDS_OPTION,
			array_diff( self::get_wordgram_product_ids(), $product_ids ),
			false
		);
	}

	private static function schedule_delete_all_wordgram_products() {
		$product_ids = self::get_wordgram_product_ids();
		for ( $i = 0; $i < ceil( count( $product_ids ) / self::BATCH_PROCESSING_LIMIT ); $i ++ ) {
			$current_batch_ids = array_slice( $product_ids, $i * self::BATCH_PROCESSING_LIMIT, self::BATCH_PROCESSING_LIMIT );
			wp_schedule_single_event( time() + $i * 30, 'schedule_remove_wordgram_products', [ $current_batch_ids, $i ] );
		}
		delete_option( self::WORDGRAM_PRODUCT_IDS_OPTION );
	}

	public static function schedule_remove_wordgram_products( $ids, $batch_number = 0 ) {
		self::remove_wordgram_products( $ids, false, false );
	}

	private static function remove_wordgram_products( $ids, $by_sku = false, $remove_ids = true ) {
		$product_ids = [];
		if ( $by_sku ) {
			foreach ( $ids as $sku ) {
				$product_id = wc_get_product_id_by_sku( $sku );
				if ( is_int( $product_id ) && $product_id > 0 ) {
					array_push( $product_ids, $product_id );
				}
			}
		} else {
			$product_ids = $ids;
		}
		$removed_product_ids = [];
		foreach ( $product_ids as $product_id ) {
			$wc_product = wc_get_product( $product_id );
			if ( is_a( $wc_product, \WC_Product::class ) ) {
				$wc_product->delete( true );
				array_push( $removed_product_ids, $wc_product->get_id() );
			}
		}
		self::log_to_db( 'wordgram_remove_products', 'rp-' . count( $ids ) . '-' . time(), $product_ids );
		if ( $remove_ids ) {
			self::remove_wordgram_product_ids( $removed_product_ids );
		}
	}

	public static function wordgram_product_hook() {
		$body   = self::parse_and_verify_json_body();
		$errors = [];
		if ( is_wp_error( $body ) ) {
			array_push( $errors, $body );
			wp_send_json_error( $errors );
		}
		if ( count( $body['products'] ) > self::BATCH_PROCESSING_LIMIT ) {
			array_push( $errors, new \WP_Error(
					'batch_limit_exceeded',
					'More than ' . self::BATCH_PROCESSING_LIMIT . ' products are not allowed.' )
			);
			wp_send_json_error( $errors );
		}
		if ( 'addProduct' === $body['action'] ) {
			array_merge( $errors, self::insert_products( $body['products'] ) );
		} elseif ( 'updateProduct' === $body['action'] ) {
			array_merge( $errors, self::insert_products( $body['products'], true ) );
		} elseif ( 'removeProduct' === $body['action'] ) {
			self::remove_wordgram_products( $body['products'], true );
		}
		if ( empty( $errors ) ) {
			wp_send_json_success();
		}
		wp_send_json_error( $errors );
	}
}