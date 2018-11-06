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
 */
class HistoryRow {
    /**
     * @var string $revision - the revision number
     **/
    public $revision;
    /**
     * @var string $comment - the edit description
     **/
    public $comment;
    /**
     * @var string $revision - the username ofthe user that edited this revision
     **/
    public $user;
    /**
     * @var string $revision - the timestamp of this revision
     **/
    public $timestamp;
}

