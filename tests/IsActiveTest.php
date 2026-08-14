<?php
/**
 * Tests for notiblock_is_active().
 *
 * @package Notiblock
 */

/**
 * Covers the date-window logic that decides whether the notification shows.
 */
class IsActiveTest extends WP_UnitTestCase {

	/**
	 * Returns a date offset from "today" in the site timezone.
	 *
	 * The notiblock_is_active() comparison uses current_time( 'Y-m-d' ), so the
	 * fixtures have to be built from the same clock.
	 *
	 * @param int $days Offset in days. Negative for the past.
	 * @return string Date in Y-m-d format.
	 */
	private function date_offset( $days ) {
		return gmdate( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . " {$days} days" ) );
	}

	/**
	 * Builds a settings array with the given overrides.
	 *
	 * @param array $overrides Values to override the defaults with.
	 * @return array Settings array.
	 */
	private function settings( array $overrides = array() ) {
		return array_merge(
			array(
				'content'     => '<p>Notice</p>',
				'start_date'  => '',
				'end_date'    => '',
				'always_show' => false,
			),
			$overrides
		);
	}

	/**
	 * Enabling always_show short-circuits every date check.
	 */
	public function test_always_show_overrides_dates() {
		$settings = $this->settings(
			array(
				'always_show' => true,
				'start_date'  => $this->date_offset( 10 ),
				'end_date'    => $this->date_offset( 20 ),
			)
		);

		$this->assertTrue( notiblock_is_active( $settings ) );
	}

	/**
	 * With no dates and no always_show there is nothing to display.
	 */
	public function test_no_dates_is_inactive() {
		$this->assertFalse( notiblock_is_active( $this->settings() ) );
	}

	/**
	 * A start date in the future has not begun yet.
	 */
	public function test_future_start_is_inactive() {
		$settings = $this->settings( array( 'start_date' => $this->date_offset( 1 ) ) );

		$this->assertFalse( notiblock_is_active( $settings ) );
	}

	/**
	 * A start date in the past is active with no end date.
	 */
	public function test_past_start_is_active() {
		$settings = $this->settings( array( 'start_date' => $this->date_offset( -1 ) ) );

		$this->assertTrue( notiblock_is_active( $settings ) );
	}

	/**
	 * The window is inclusive of the start date itself.
	 */
	public function test_start_date_today_is_active() {
		$settings = $this->settings( array( 'start_date' => $this->date_offset( 0 ) ) );

		$this->assertTrue( notiblock_is_active( $settings ) );
	}

	/**
	 * The window is inclusive of the end date itself.
	 */
	public function test_end_date_today_is_active() {
		$settings = $this->settings( array( 'end_date' => $this->date_offset( 0 ) ) );

		$this->assertTrue( notiblock_is_active( $settings ) );
	}

	/**
	 * An end date in the past has already lapsed.
	 */
	public function test_past_end_is_inactive() {
		$settings = $this->settings( array( 'end_date' => $this->date_offset( -1 ) ) );

		$this->assertFalse( notiblock_is_active( $settings ) );
	}

	/**
	 * Today inside an explicit window is active.
	 */
	public function test_inside_window_is_active() {
		$settings = $this->settings(
			array(
				'start_date' => $this->date_offset( -2 ),
				'end_date'   => $this->date_offset( 2 ),
			)
		);

		$this->assertTrue( notiblock_is_active( $settings ) );
	}

	/**
	 * Today after an entirely past window is inactive.
	 */
	public function test_outside_window_is_inactive() {
		$settings = $this->settings(
			array(
				'start_date' => $this->date_offset( -10 ),
				'end_date'   => $this->date_offset( -5 ),
			)
		);

		$this->assertFalse( notiblock_is_active( $settings ) );
	}

	/**
	 * Called with no argument the function reads the stored option.
	 */
	public function test_falls_back_to_stored_settings() {
		update_option(
			'notiblock_global_notice',
			$this->settings( array( 'always_show' => true ) )
		);

		// Bypass the request-level static cache seeded by earlier tests.
		notiblock_get_settings( true );

		$this->assertTrue( notiblock_is_active() );
	}
}
