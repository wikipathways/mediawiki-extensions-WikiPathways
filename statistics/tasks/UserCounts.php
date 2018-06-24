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

class UserCounts
{
    static function run($file, $times) 
    {
        $registered = array();
        $everActive = array();
        $intervalActive = array();

        $tsPrev = array_shift($times);
        foreach($times as $tsCurr) {
            $date = date('Y/m/d', wfTimestamp(TS_UNIX, $tsCurr));
            wfDebugLog(__NAMESPACE__, $date);

            $users = StatUser::getSnapshot($tsCurr);

            $everCount = 0;
            $intervalCount = 0;

            $minEdits = 1;
            foreach($users as $u) {
                if(count($u->getPageEdits($tsCurr)) >= $minEdits) {
                    $everCount++; 
                }
                if(count($u->getPageEdits($tsCurr, $tsPrev)) >= $minEdits) {
                    $intervalCount++; 
                }
            }

            $everActive[$date] = $everCount;
            $intervalActive[$date] = $intervalCount;
            $registered[$date] = count($users) - $everCount;

            $tsPrev = $tsCurr;
        }

        $fout = fopen($file, 'w');
        fwrite($fout, "date\tnumber\tnumber\tnumber\n");
        fwrite($fout, "Time\tRegistered users\tEditing users\tEdited in month\n");

        foreach(array_keys($registered) as $date) {
            $row = array(
             $registered[$date], $everActive[$date], $intervalActive[$date]
            );
            fwrite($fout, $date . "\t" . implode("\t", $row) . "\n");
        }

        fclose($fout);
    }
}

Generator::registerTask('UserCounts', __NAMESPACE__ . '\UserCounts::run');
