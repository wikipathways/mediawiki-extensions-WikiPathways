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
use WikiPathways\Statistics\User as StatUser;
use User;

class EditCounts
{
    static function run($file, $times) 
    {
        //Number of edits in month, number of total edits
        //Exclude bot edits
        //Exclude test/tutorial edits

        $exclude = WikiPathwaysStatistics::getExcludeByTag();

        $botEdits = array();
        $testEdits = array();
        $realEdits = array();
        $botEditsInt = array();
        $testEditsInt = array();
        $realEditsInt = array();

        $tsPrev = array_shift($times);
        foreach($times as $tsCurr) {
            $date = date('Y/m/d', wfTimestamp(TS_UNIX, $tsCurr));
            wfDebugLog(__NAMESPACE__, $date);

            $users = StatUser::getSnapshot($tsCurr);

            $botCount = $testCount = $realCount = 0;
            $botCountInt = $testCountInt = $realCountInt = 0;

            foreach($users as $u) {
                $mwu = User::newFromId($u->getId());
                $bot = $mwu->isBot();

                $edits = $u->getPageEdits($tsCurr);
                $editsInt = $u->getPageEdits($tsCurr, $tsPrev);

                if($bot) {
                    $botCount += count($edits);
                    $botCountInt += count($editsInt);
                } else {
                    //Remove test edits
                    $rc = array_diff($edits, $exclude);
                    $rcInt = array_diff($editsInt, $exclude);

                    $testCount += count($edits) - count($rc);
                    $testCountInt += count($editsInt) - count($rcInt);
                    $realCount += count($rc);
                    $realCountInt += count($rcInt);
                }
            }

            $botEdits[$date] = $botCount;
            $botEditsInt[$date] = $botCountInt;
            $testEdits[$date] = $testCount;
            $testEditsInt[$date] = $testCountInt;
            $realEdits[$date] = $realCount;
            $realEditsInt[$date] = $realCountInt;

            $tsPrev = $tsCurr;
        }

        $fout = fopen($file, 'w');
        fwrite($fout, "date\tnumber\tnumber\tnumber\tnumber\tnumber\tnumber\n");
        fwrite(
            $fout, "Time\tUser edits\tUser edits in month\t" .
            "Test/tutorial edits\tTest/tutorial edits in month\t" .
            "Bot edits\tBot edits in month\n"
        );

        foreach(array_keys($realEdits) as $date) {
            $row = array(
             $realEdits[$date], $realEditsInt[$date],
             $testEdits[$date], $testEditsInt[$date],
             $botEdits[$date], $botEditsInt[$date]
            );
            fwrite($fout, $date . "\t" . implode("\t", $row) . "\n");
        }

        fclose($fout);
    }
}

Generator::RegisterTask('EditCounts', __NAMESPACE__ . '\EditCounts::run');
