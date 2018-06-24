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
 * @author Thomas Kelder <thomaskelder@gmail.com>
 * @author Alexander Pico <apico@gladstone.ucsf.edu>
 * @author Mark A. Hershberger <mah@nichework.com>
 */
namespace WikiPathways\Statistics\Task;

use WikiPathways\Statistics\Generator;

class UserFrequencies
{
    static function run($file, $times) 
    {
        $last = array_pop($times);
        $exclude = WikiPathwaysStatistics::getExcludeByTag();
        $users = StatUser::getSnapshot($last);

        $editCounts = array();
        foreach($users as $u) {
            $mwu = User::newFromId($u->getId());
            if($mwu->isBot()) { continue; //Skip bots
            }
            $all = $u->getPageEdits();
            $edits = array_diff($all, $exclude);
            if(count($edits) > 0) { $editCounts[$u->getName()] = count($edits); 
            }
        }

        $fout = fopen($file, 'w');
        fwrite($fout, "string\tstring\tnumber\n");
        fwrite($fout, "User\tUser rank\tNumber of edits\n");
        WikiPathwaysStatistics::writeFrequencies($fout, $editCounts, true);

        fclose($fout);
    }
}

Generator::registerTask('UserFrequencies', __NAMESPACE__ . '\UserFrequencies::run');
