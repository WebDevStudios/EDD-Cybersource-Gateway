<?php
require_once( 'EDD_PROD_SUPPORT-Base-Tests.php' );

class EDD_PROD_SUPPORT_files_tests extends EDD_PROD_SUPPORT_Base_Tests {

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

	/**
	 * Tests for our inc files being present and available.
	 */
	public function test_EDD_PROD_SUPPORT_files_exist() {
		$this->assertFileExists( EDD_PROD_SUPPORT_DIRECTORY_PATH . 'bbp-content-restriction.php' );
		$this->assertFileExists( EDD_PROD_SUPPORT_DIRECTORY_PATH . 'edd-product-support.php' );
		$this->assertFileExists( EDD_PROD_SUPPORT_DIRECTORY_PATH . 'readme.txt' );
		$this->assertFileExists( EDD_PROD_SUPPORT_DIRECTORY_PATH . 'upgrade.php' );
		$this->assertFileExists( EDD_PROD_SUPPORT_DIRECTORY_PATH . 'languages/wds-product-support.pot' );
		$this->assertFileExists( EDD_PROD_SUPPORT_DIRECTORY_PATH . 'template-tags.php' );
		$this->assertFileExists( EDD_PROD_SUPPORT_DIRECTORY_PATH . 'widgets/widgets.php' );
		$this->assertFileExists( EDD_PROD_SUPPORT_DIRECTORY_PATH . 'widgets/widget-user-forums-list.php' );
		$this->assertFileExists( EDD_PROD_SUPPORT_DIRECTORY_PATH . 'shortcodes/edd_user_product_support_forum_list.php' );
	}
}
