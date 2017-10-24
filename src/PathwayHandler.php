<?php
/**
 * Copyright (C) 2017  J. David Gladstone Institutes
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author
 * @author Mark A. Hershberger <mah@nichework.com>
 */
namespace WikiPathways;

use Content;
use ContentHandler;
use MWException;

class PathwayHandler extends ContentHandler {

	/**
	 * @param string $modelId see docs
	 * @param array $formats see docs
	 */
	public function __construct(
		$modelId = CONTENT_MODEL_PATHWAY, $formats = [ CONTENT_MODEL_PATHWAY ]
	) {
		parent::__construct( $modelId, $formats );
	}

	/**
	 * Serializes a Content object of the type supported by this ContentHandler.
	 *
	 * @since 1.21
	 *
	 * @param Content $content The Content object to serialize
	 * @param null|string $format The desired serialization format
	 * @return string Serialized form of the content
	 */
	public function serializeContent( Content $content, $format = null ) {
		if ( !( $content instanceof PathwayContent ) ) {
			throw new MWException( "Expected PathwayContent object, got " .
				get_class( $content ) );
		}
		return $content->getNativeData();
	}

	/**
	 * Unserializes a Content object of the type supported by this
	 * ContentHandler.
	 *
	 * @since 1.21
	 *
	 * @param string $blob serialized form of the content
	 * @param null|string $format the format used for serialization
	 * @return Content the Content object created by deserializing $blob
	 */
	public function unserializeContent( $blob, $format = null ) {
		$this->checkFormat( $format );

		return new PathwayContent( $blob );
	}

	/**
	 * Creates an empty Content object of the type supported by this
	 * ContentHandler.
	 *
	 * @since 1.21
	 *
	 * @return Content
	 */
	public function makeEmptyContent() {
		return new PathwayContent();
	}

}
