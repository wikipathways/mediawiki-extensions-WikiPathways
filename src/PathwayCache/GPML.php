<?php

/**
 * Copyright (C) 2018  J. David Gladstone Institutes
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
 * @author Mark A. Hershberger <mah@nichework.com>
 */
namespace WikiPathways\PathwayCache;

use Revision;
use WikiPathways\Pathway;

class GPML extends Base {
	protected $mimeType = "text/xml";

	/**
	 * Find out if the current user has permissions to view this pathway
	 *
	 * @return bool
	 */
	public function isReadable() {
		return $this->pathway->getTitleObject()->userCan( 'read' );
	}

	/**
	 * Utility function that throws an exception if the
	 * current user doesn't have permissions to view the
	 * pathway.
	 *
	 * @throw Exception
	 */
	private function checkReadable() {
		if ( !$this->isReadable() ) {
			throw new Exception(
				"Current user doesn't have permissions to view this pathway"
			);
		}
	}

	/**
	 * Get the GPML code for this pathway (the active revision will be
	 * used, see Pathway::getActiveRevision)
	 *
	 * @return string
	 */
	public function doRender() {
		wfDebugLog( __METHOD__,  "called\n" );
		$this->checkReadable();
		$gpmlTitle = $this->pathway->getTitleObject();
		$gpmlRef = Revision::newFromTitle(
			$gpmlTitle, $this->pathway->getActiveRevision()
		);

		return $gpmlRef == null ? false : $gpmlRef->getSerializedData();
	}
}
