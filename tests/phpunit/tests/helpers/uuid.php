<?php
/**
 * Tests for UUID extraction and validation helpers.
 *
 * @package Hipcall
 */

/**
 * @group hipcall
 * @group helpers
 */
class Tests_Helpers_Uuid extends WP_UnitTestCase {

	const UUID = '8e32e0b8-fd33-450b-a5b0-b3359e1384e1';

	/**
	 * @dataProvider data_inputs_containing_a_uuid
	 *
	 * @covers ::hipcall_extract_uuid
	 *
	 * @param string $raw Raw input pasted by the administrator.
	 */
	public function test_extract_uuid_should_find_the_uuid_in_pasted_input( $raw ) {
		$this->assertSame( self::UUID, hipcall_extract_uuid( $raw ) );
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_inputs_containing_a_uuid() {
		return array(
			'bare uuid'                      => array( self::UUID ),
			'uppercase uuid is lowercased'   => array( strtoupper( self::UUID ) ),
			'surrounding whitespace'         => array( "  \n" . self::UUID . "\t " ),
			'widget url'                     => array( 'https://use.hipcall.com.tr/widget/' . self::UUID ),
			'full widget script from portal' => array( '<script async src="https://use.hipcall.com/widget/' . self::UUID . '"></script>' ),
			'tracker script from portal'     => array( '<script async src="https://use.hipcall.com/track/v1/h.js" data-hipcall-id="' . self::UUID . '"></script>' ),
		);
	}

	/**
	 * @dataProvider data_inputs_without_a_uuid
	 *
	 * @covers ::hipcall_extract_uuid
	 *
	 * @param string $raw Raw input.
	 */
	public function test_extract_uuid_should_return_empty_string_when_no_uuid_is_present( $raw ) {
		$this->assertSame( '', hipcall_extract_uuid( $raw ) );
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_inputs_without_a_uuid() {
		return array(
			'empty string'         => array( '' ),
			'random text'          => array( 'my widget' ),
			'truncated uuid'       => array( '8e32e0b8-fd33-450b-a5b0-b3359e1384e' ),
			'non-hex characters'   => array( '8e32e0b8-fd33-450b-a5b0-b3359e1384zz' ),
			'script without a uuid' => array( '<script async src="https://use.hipcall.com/widget/"></script>' ),
		);
	}

	/**
	 * @covers ::hipcall_is_valid_uuid
	 */
	public function test_is_valid_uuid_should_accept_lowercase_and_uppercase_uuids() {
		$this->assertTrue( hipcall_is_valid_uuid( self::UUID ), 'Lowercase UUID should be valid.' );
		$this->assertTrue( hipcall_is_valid_uuid( strtoupper( self::UUID ) ), 'Uppercase UUID should be valid.' );
	}

	/**
	 * @dataProvider data_invalid_uuids
	 *
	 * @covers ::hipcall_is_valid_uuid
	 *
	 * @param mixed $value Value to validate.
	 */
	public function test_is_valid_uuid_should_reject_anything_that_is_not_exactly_a_uuid( $value ) {
		$this->assertFalse( hipcall_is_valid_uuid( $value ) );
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_invalid_uuids() {
		return array(
			'empty string'         => array( '' ),
			'null'                 => array( null ),
			'uuid inside a url'    => array( 'https://use.hipcall.com/widget/' . self::UUID ),
			'uuid with whitespace' => array( ' ' . self::UUID ),
			'missing hyphens'      => array( str_replace( '-', '', self::UUID ) ),
			'script injection'     => array( self::UUID . '"><script>alert(1)</script>' ),
		);
	}
}
