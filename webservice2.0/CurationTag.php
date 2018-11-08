<?php
/**
 * Copyright (C) 2015-2018  J. David Gladstone Institutes
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
 * @author tina
 * @author nuno
 * @author anders
 * @author Mark A. Hershberger
 */
namespace WikiPathways\WebService;

use WikiPathways\Pathway;
use Title;
use User;
/**
 * @namespace http://www.wikipathways.org/webservice
 **/
class CurationTag {
    public function __construct( $metatag ) {
        $this->name = $metatag->getName();
        $this->displayName = \WikiPathways\CurationTag::getDisplayName( $this->name );
        $title = Title::newFromId( $metatag->getPageId() );
        if ( $title ) {
            $pathway = Pathway::newFromTitle( $title );
            if ( $pathway->isReadable() && !$pathway->isDeleted() ) {
                $this->pathway = new PathwayInfo( $pathway );
            }
        }

        $this->revision = $metatag->getPageRevision();
        $this->text = $metatag->getText();
        $this->timeModified = $metatag->getTimeMod();
        $this->userModified = User::newFromId( $metatag->getUserMod() )->getName();
    }

    /**
     * @var string $name The internal tag name
     **/
    public $name;

    /**
     * @var string $displayName The display name of the tag
     */
    public $displayName;

    /**
     * @var object PathwayInfo $pathway The pathway this tag applies to
     */
    public $pathway;

    /**
     *@var string $revision The revision this tag applies to. '0' is used for tags that apply to all revisions.
     */
    public $revision;

    /**
     *@var string $text The tag text.
     */
    public $text;

    /**
     *@var long $timeModified The timestamp of the last modified date
     */
    public $timeModified;

    /**
     *@var string $userModified The username of the user that last modified the tag
     */
    public $userModified;
}
