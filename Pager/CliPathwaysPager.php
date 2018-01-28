<?php

/**
 *  So you have a pager class... that could easily be used as an iterator
 */

class CliPathwaysPager extends BasePathwaysPager {

	/**
	 * @param int $offset to start at
	 * @return CliPathwaysPager
	 */
	public function nextPager( $offset ) {
		self::$myOffset = $offset;
		self::$myLimit = 50;
		self::$myBackwards = false;
		self::$myOrder = null;

		return new self();
	}

	/**
	 * @return CliPathwaysPager
	 */
	public static function initPager() {
		self::$myOffset = null;
		self::$myLimit = 50;
		self::$myBackwards = false;
		self::$myOrder = null;

		return new self();
	}

	// set these directly for now.
	protected static $myOffset;
	protected static $myLimit;
	protected static $myBackwards;
	protected static $myOrder;

	/**
	 * @return int
	 */
	public function getOffset() {
		return self::$myOffset;
	}
	/**
	 * @return int
	 */
	public function getLimit() {
		return self::$myLimit;
	}

	/**
	 * @return bool
	 */
	public function isBackwards() {
		return self::$myBackwards;
	}
	/**
	 * @return string
	 */
	public function getOrder() {
		return self::$myOrder;
	}

	/**
	 * @param int $row bogus
	 * @throws MWException
	 */
	public function formatRow( $row ) {
		throw new MWException( "You shouldn't see this!" );
	}

	/**
	 * @param DatabaseResult $res to use
	 * @return string
	 */
	function getKey( $res ) {
		return $res->page_title;
	}

	/**
	 * @param DatabaseResult $res to use
	 * @return string
	 */
	function getValue( $res ) {
		return $res->tag_text;
	}

}
