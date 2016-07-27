<?php
require_once( 'EDD_PROD_SUPPORT-Base-Tests.php' );

class EDD_PROD_SUPPORT_content_restrictions extends EDD_PROD_SUPPORT_Base_Tests {

	/**
	 * Set up
	 */
	public function setUp() {
		parent::setUp();
		#activate_plugin( 'EDD-Product-Support/edd-product-support.php' );
	}

	/*
	 * Teardown.
	 */
	public function tearDown() {
		parent::tearDown();
	}

}
