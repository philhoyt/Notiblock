<?php
/**
 * Tests for notiblock_save_settings().
 *
 * @package Notiblock
 */

/**
 * Covers the capability gate, sanitization, and date validation on save.
 */
class SaveSettingsTest extends WP_UnitTestCase {

	/**
	 * Resets state between tests.
	 */
	public function set_up() {
		parent::set_up();
		delete_option( 'notiblock_global_notice' );
		notiblock_get_settings( true );
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
	 * A subscriber cannot save settings.
	 */
	public function test_subscriber_is_rejected() {
		$this->acting_as( 'subscriber' );

		$result = notiblock_save_settings( array( 'content' => '<p>nope</p>' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'insufficient_permissions', $result->get_error_code() );
	}

	/**
	 * An editor cannot save settings — this needs manage_options, not edit_posts.
	 */
	public function test_editor_is_rejected() {
		$this->acting_as( 'editor' );

		$result = notiblock_save_settings( array( 'content' => '<p>nope</p>' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'insufficient_permissions', $result->get_error_code() );
	}

	/**
	 * Nothing is written to the database when the capability check fails.
	 */
	public function test_rejected_save_does_not_persist() {
		$this->acting_as( 'subscriber' );

		notiblock_save_settings( array( 'content' => '<p>nope</p>' ) );

		$this->assertFalse( get_option( 'notiblock_global_notice', false ) );
	}

	/**
	 * An administrator can save.
	 */
	public function test_administrator_can_save() {
		$this->acting_as( 'administrator' );

		$result = notiblock_save_settings(
			array(
				'content'     => '<p>Hello</p>',
				'always_show' => true,
			)
		);

		$this->assertTrue( $result );
		$this->assertSame( '<p>Hello</p>', notiblock_get_settings( true )['content'] );
	}

	/**
	 * An end date before the start date is refused.
	 */
	public function test_end_before_start_is_rejected() {
		$this->acting_as( 'administrator' );

		$result = notiblock_save_settings(
			array(
				'content'    => '<p>Hello</p>',
				'start_date' => '2026-06-10',
				'end_date'   => '2026-06-01',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'invalid_date_range', $result->get_error_code() );
	}

	/**
	 * Enabling always_show skips the date-range validation entirely.
	 */
	public function test_always_show_skips_range_validation() {
		$this->acting_as( 'administrator' );

		$result = notiblock_save_settings(
			array(
				'content'     => '<p>Hello</p>',
				'start_date'  => '2026-06-10',
				'end_date'    => '2026-06-01',
				'always_show' => true,
			)
		);

		$this->assertTrue( $result );
	}

	/**
	 * Malformed dates are discarded rather than stored.
	 */
	public function test_malformed_dates_are_cleared() {
		$this->acting_as( 'administrator' );

		notiblock_save_settings(
			array(
				'content'    => '<p>Hello</p>',
				'start_date' => 'not-a-date',
				'end_date'   => '06/01/2026',
			)
		);

		$saved = notiblock_get_settings( true );

		$this->assertSame( '', $saved['start_date'] );
		$this->assertSame( '', $saved['end_date'] );
	}

	/**
	 * Script tags are stripped by wp_kses_post().
	 */
	public function test_script_tags_are_stripped() {
		$this->acting_as( 'administrator' );

		notiblock_save_settings(
			array(
				'content'     => '<p>Safe</p><script>alert(1)</script>',
				'always_show' => true,
			)
		);

		$saved = notiblock_get_settings( true );

		$this->assertStringNotContainsString( '<script', $saved['content'] );
		$this->assertStringContainsString( 'Safe', $saved['content'] );
	}

	/**
	 * A javascript: href does not survive sanitization.
	 *
	 * This is the server-side half of the client-side URL validation in
	 * RichEditor.jsx.
	 */
	public function test_javascript_href_is_stripped() {
		$this->acting_as( 'administrator' );

		notiblock_save_settings(
			array(
				'content'     => '<p><a href="javascript:alert(1)">x</a></p>',
				'always_show' => true,
			)
		);

		$saved = notiblock_get_settings( true );

		$this->assertStringNotContainsString( 'javascript:', $saved['content'] );
	}

	/**
	 * Safe formatting markup is preserved.
	 */
	public function test_safe_markup_is_preserved() {
		$this->acting_as( 'administrator' );

		notiblock_save_settings(
			array(
				'content'     => '<p><strong>Bold</strong> and <a href="https://example.com">link</a></p>',
				'always_show' => true,
			)
		);

		$saved = notiblock_get_settings( true );

		$this->assertStringContainsString( '<strong>Bold</strong>', $saved['content'] );
		$this->assertStringContainsString( 'https://example.com', $saved['content'] );
	}

	/**
	 * The always_show flag is stored as a real boolean regardless of input shape.
	 */
	public function test_always_show_is_cast_to_boolean() {
		$this->acting_as( 'administrator' );

		notiblock_save_settings(
			array(
				'content'     => '<p>Hello</p>',
				'always_show' => 'true',
			)
		);

		$this->assertTrue( notiblock_get_settings( true )['always_show'] );
	}

	/**
	 * Missing keys fall back to defaults instead of raising notices.
	 */
	public function test_missing_keys_use_defaults() {
		$this->acting_as( 'administrator' );

		$result = notiblock_save_settings( array() );

		$this->assertTrue( $result );

		$saved = notiblock_get_settings( true );

		$this->assertSame( '', $saved['content'] );
		$this->assertFalse( $saved['always_show'] );
	}
}
