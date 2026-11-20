<?php
/**
 * Tests for region selection helpers.
 *
 * @package Hipcall
 */

/**
 * @group hipcall
 * @group helpers
 */
class Tests_Helpers_Region extends WP_UnitTestCase {

	/**
	 * Force the locale returned by determine_locale().
	 *
	 * @param string $locale Locale such as 'tr_TR'.
	 */
	private function set_locale( $locale ) {
		add_filter(
			'pre_determine_locale',
			static function () use ( $locale ) {
				return $locale;
			}
		);
	}

	/**
	 * @dataProvider data_locales
	 *
	 * @covers ::hipcall_get_default_region
	 *
	 * @param string $locale   Site locale.
	 * @param string $expected Expected default region.
	 */
	public function test_default_region_should_follow_the_site_locale( $locale, $expected ) {
		$this->set_locale( $locale );

		$this->assertSame( $expected, hipcall_get_default_region() );
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_locales() {
		return array(
			'Turkish'          => array( 'tr_TR', 'tr' ),
			'English (US)'     => array( 'en_US', 'global' ),
			'German'           => array( 'de_DE', 'global' ),
			'Turkmen is not Turkish' => array( 'tk', 'global' ),
		);
	}

	/**
	 * @covers ::hipcall_get_base_url
	 */
	public function test_base_url_should_use_turkiye_domain_when_region_is_tr() {
		update_option( 'hipcall_region', 'tr' );

		$this->assertSame( 'https://use.hipcall.com.tr', hipcall_get_base_url() );
	}

	/**
	 * @covers ::hipcall_get_base_url
	 */
	public function test_base_url_should_use_global_domain_when_region_is_global() {
		update_option( 'hipcall_region', 'global' );

		$this->assertSame( 'https://use.hipcall.com', hipcall_get_base_url() );
	}

	/**
	 * @covers ::hipcall_get_base_url
	 */
	public function test_saved_region_should_win_over_the_site_locale() {
		$this->set_locale( 'tr_TR' );
		update_option( 'hipcall_region', 'global' );

		$this->assertSame( 'https://use.hipcall.com', hipcall_get_base_url() );
	}

	/**
	 * @covers ::hipcall_get_base_url
	 */
	public function test_base_url_should_fall_back_to_locale_default_when_region_is_not_saved() {
		delete_option( 'hipcall_region' );
		$this->set_locale( 'tr_TR' );

		$this->assertSame( 'https://use.hipcall.com.tr', hipcall_get_base_url() );
	}
}
