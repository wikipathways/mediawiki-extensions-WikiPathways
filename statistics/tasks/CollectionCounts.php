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
use WikiPathways\Statistics\Tag;
use WikiPathways\Statistics\Pathway;
use WikiPathways\CurationTag;

class CollectionCounts
{
    static function run($file, array $times) 
    {
        $collectionCounts = array();
        $collections = array(
        "Curation:FeaturedPathway",
        "Curation:AnalysisCollection",
        "Curation:CIRM_Related",
        "Curation:Wikipedia",
        "Curation:Reactome_Approved"
        );

        foreach($times as $tsCurr) {
            $date = date('Y/m/d', wfTimestamp(TS_UNIX, $tsCurr));
            wfDebugLog(__NAMESPACE__, $date);

            $snapshot = Tag::getSnapshot($tsCurr, $collections);
            $pathways = Pathway::getSnapshot($tsCurr);

            //Remove tags on deleted pages
            $existPages = array();
            foreach($pathways as $pathway) {
                $existPages[] = $pathway->getId();
            }
            $removeTags = array();
            foreach($snapshot as $tag) {
                if(!in_array($tag->getPageId(), $existPages)) {
                    $removeTags[] = $tag;
                }
            }
            $snapshot = array_diff($snapshot, $removeTags);

            $counts = array();
            foreach($collections as $c) {
                $counts[$c] = 0;
            }
            foreach($snapshot as $tag) {
                $type = $tag->getType();
                if(array_key_exists($type, $counts)) {
                    $counts[$type] = $counts[$type] + 1;
                }
            }
            $collectionCounts[$date] = $counts;
        }

        $collectionNames = array();
        foreach($collections as $c) {
            $collectionNames[] = CurationTag::getDisplayName($c);
        }

        $fout = fopen($file, 'w');
        fwrite(
            $fout, "date\t" .
            implode("\t", array_fill(0, count($collections), "number")) . "\n"
        );
        fwrite(
            $fout, "Time\t" .
            implode("\t", $collectionNames) . "\n"
        );

        foreach(array_keys($collectionCounts) as $date) {
            $values = $collectionCounts[$date];
            fwrite($fout, $date . "\t" . implode("\t", $values) . "\n");
        }

        fclose($fout);
    }
}

Generator::registerTask(
    'CollectionCounts', __NAMESPACE__ . '\CollectionCounts::run'
);
