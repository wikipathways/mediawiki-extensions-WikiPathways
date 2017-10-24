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
 */
namespace WikiPathways;

use MWException;
use Article;
use Revision;
use HistoryPager;

class PathwayHistory {
	public static function history( $input, $argv, $parser ) {
		$parser->disableCache();
		try {
			$pathway = Pathway::newFromTitle( $parser->mTitle );
			return self::getHistory( $pathway );
		} catch ( MWException $e ) {
			return "Error: $e";
		}
	}

	static function getHistory( $pathway ) {
		global $wgUser, $wpiScriptURL;

		$gpmlTitle = $pathway->getTitleObject();
		$gpmlArticle = new Article( $gpmlTitle );
		$hist = new HistoryPager( $gpmlArticle );

		$pager = new GpmlHistoryPager( $pathway, $hist );

		$s = $pager->getBody();
		return $s;
	}

	/**
	 * Generates dynamic display of radio buttons for selecting versions to compare
	 */
	function diffButtons( $rev, $firstInList, $counter, $linesonpage ) {
		if ( $linesonpage > 1 ) {
			$radio = [
				'type'  => 'radio',
				'value' => $rev->getId(),
				# do we really need to flood this on every item?
				#                               'title' => wfMsgHtml( 'selectolderversionfordiff' )
			];

			if ( !$rev->userCan( Revision::DELETED_TEXT ) ) {
				$radio['disabled'] = 'disabled';
			}

			/** @todo: move title texts to javascript */
			if ( $firstInList ) {
				$first = wfElement( 'input', array_merge(
					$radio,
					[
						'style' => 'visibility:hidden',
						'name'  => 'old' ] ) );
				$checkmark = [ 'checked' => 'checked' ];
			} else {
				if ( $counter == 2 ) {
					$checkmark = [ 'checked' => 'checked' ];
				} else {
					$checkmark = [];
				}
				$first = wfElement( 'input', array_merge(
					$radio,
					$checkmark,
					[ 'name'  => 'old' ] ) );
				$checkmark = [];
			}
			$second = wfElement( 'input', array_merge(
				$radio,
				$checkmark,
				[ 'name'  => 'new' ] ) );
			return $first . $second;
		} else {
			return '';
		}
	}

}
