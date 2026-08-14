<?php
/**
 * Tests for the Notiblock REST API routes.
 *
 * @package Notiblock
 */

/**
 * Covers route registration and both permission callbacks.
 */
class RestTest extends WP_UnitTestCase {

	const ROUTE = '/notiblock/v1/settings';

	/**
	 * REST server instance.
	 *
	 * @var WP_REST_Server
	 */
	private $server;

	/**
	 * Boots a REST server for each test.
	 */
	public function set_up() {
		parent::set_up();

		delete_option( 'notiblock_global_notice' );
		notiblock_get_settings( true );

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init' );
	}

	/**
	 * Cleans up the REST server.
	 */
	public function tear_down() {
		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tear_down();
	}

	/**
	 * Switches the current user to a freshly created user of the given role.
	 *
	 * @param string $role Role slug.
	 * @return int User ID.
	 */
	private function acting_as( $role ) {
		$user_id = $this->factory()->user->create( array( 'role' => $role ) );
		wp_set_current_user( $user_id );

		return $user_id;
	}

	/**
	 * Dispatches a request against the settings route.
	 *
	 * @param string $method HTTP method.
	 * @param array  $params Request body parameters.
	 * @return WP_REST_Response Response object.
	 */
	private function dispatch( $method, array $params = array() ) {
		$request = new WP_REST_Request( $method, self::ROUTE );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return $this->server->dispatch( $request );
	}

	/**
	 * The route is registered.
	 */
	public function test_route_is_registered() {
		$this->assertArrayHasKey( self::ROUTE, $this->server->get_routes() );
	}

	/**
	 * Anonymous users cannot read the settings.
	 */
	public function test_get_requires_authentication() {
		wp_set_current_user( 0 );

		$this->assertSame( 401, $this->dispatch( 'GET' )->get_status() );
	}

	/**
	 * A subscriber lacks edit_posts and is refused.
	 */
	public function test_get_rejects_subscriber() {
		$this->acting_as( 'subscriber' );

		$this->assertSame( 403, $this->dispatch( 'GET' )->get_status() );
	}

	/**
	 * A contributor has edit_posts and may read the settings for the editor preview.
	 */
	public function test_get_allows_contributor() {
		$this->acting_as( 'contributor' );

		$response = $this->dispatch( 'GET' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content', $response->get_data() );
	}

	/**
	 * Writing requires manage_options, which an editor does not have.
	 */
	public function test_post_rejects_editor() {
		$this->acting_as( 'editor' );

		$this->assertSame( 403, $this->dispatch( 'POST', array( 'content' => '<p>x</p>' ) )->get_status() );
	}

	/**
	 * A rejected write leaves the stored option untouched.
	 */
	public function test_rejected_post_does_not_persist() {
		$this->acting_as( 'editor' );

		$this->dispatch( 'POST', array( 'content' => '<p>x</p>' ) );

		$this->assertFalse( get_option( 'notiblock_global_notice', false ) );
	}

	/**
	 * An administrator can write, and gets the sanitized values back.
	 */
	public function test_post_allows_administrator() {
		$this->acting_as( 'administrator' );

		$response = $this->dispatch(
			'POST',
			array(
				'content'     => '<p>Hello</p>',
				'always_show' => true,
			)
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( '<p>Hello</p>', $response->get_data()['content'] );
	}

	/**
	 * A bad date range surfaces as a 400 rather than a silent success.
	 */
	public function test_post_invalid_range_returns_400() {
		$this->acting_as( 'administrator' );

		$response = $this->dispatch(
			'POST',
			array(
				'content'    => '<p>Hello</p>',
				'start_date' => '2026-06-10',
				'end_date'   => '2026-06-01',
			)
		);

		$this->assertSame( 400, $response->get_status() );
	}
}
