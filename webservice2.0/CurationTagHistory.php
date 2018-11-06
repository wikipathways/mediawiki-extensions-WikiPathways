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
/**
 * @namespace http://www.wikipathways.org/webservice
 **/
class CurationTagHistory {
    public function __construct( $histRow ) {
        $this->tagName = $histRow->getTagName();
        $this->pathwayId = Title::newFromId( $histRow->getPageId() )->getText();
        $this->action = $histRow->getAction();
        $this->user = User::newFromId( $histRow->getUser() )->getName();
        $this->time = $histRow->getTime();
        $this->text = $histRow->getText();
    }

    /**
     *@var string $tagName The name of the tag that was affected
     */
    public $tagName;

    /**
     *@var string $text The text of the tag at time this action was performed
     */
    public $text;

    /**
     *@var string $pathwayId The id of the pathway this tag applies to
     */
    public $pathwayId;

    /**
     *@var string $action The action that was performed on the tag
     */
    public $action;

    /**
     *@var string $user The name of the user that performed the action
     */
    public $user;

    /**
     *@var string $time The timestamp of the date the action was performed
     */
    public $time;
}
