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

class Summary
{
    static function run($file, $times) 
    {
        $tsCurr = date('YmdHis');
        $date = date('Y/m/d', wfTimestamp(TS_UNIX, $tsCurr));

        $fout = fopen($file, 'w');

        //pathways
        $pwPublic = 0;
        $pwPrivate = 0;

        $pathways = StatPathway::getSnapshot($tsCurr);
        foreach($pathways as $p) {
            $wp = new Pathway($p->getPwId());
            if($wp->isPublic()) { $pwPublic += 1; 
            }
            else { $pwPrivate += 1; 
            }
        }

        fwrite($fout, "<p>Last update: $date</p>");
        $pws = <<<PATHWAYS
<h3>Number of pathways</h3><ul>
<li>Public pathways:<b> $pwPublic</b>
<li>Private pathways:<b> $pwPrivate</b>
</ul>
PATHWAYS;
        fwrite($fout, $pws);

        $uOne = 0;
        $uOneNoTest = 0;
        $uInactive = 0;

        $eTotal = 0;
        $eNoTest = 0;
        $eTotalBots = 0;

        $exclude = WikiPathwaysStatistics::getExcludeByTag();
        $users = StatUser::getSnapshot($tsCurr);

        foreach($users as $u) {
            $edits = $u->getPageEdits($tsCurr);
            $editsNoTest = array_diff($edits, $exclude);

            if(count($edits) > 0) { $uOne += 1; 
            }
            if(count($editsNoTest) > 0) { $uOneNoTest += 1; 
            }
            if(count($edits) == 0) { $uInactive += 1; 
            }

            $mwu = User::newFromId($u->getId());
            if($mwu->isBot()) {
                $eTotalBots += count($edits);
            } else {
                $eTotal += count($edits);
                $eNoTest += count($editsNoTest);
            }
        }

        $usr = <<<USERS
<h3>Number of active users</h3><ul>
<li>At least 1 edit:<b> $uOne</b>
<li>At least 1 edit (excluding test/tutorial pathways):<b> $uOneNoTest</b>
</ul>
USERS;
        $edt = <<<EDITS
<h3>Number of edits</h3><ul>
<li>User edits:<b> $eTotal</b>
<li>User edits (excluding test/tutorial pathways):<b> $eNoTest</b>
<li>Bot edits:<b> $eTotalBots</b>
</ul>
EDITS;

        fwrite($fout, $usr);
        fwrite($fout, $edt);

        fclose($fout);
    }
}

Generator::registerTask('Summary', __NAMESPACE__ . '\Summary::run');
