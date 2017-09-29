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
 *
 * @author
 * @author Mark A. Hershberger <mah@nichework.com>
 */

namespace WikiPathways;

class MagicWord
{
    // Make this configurable in extension.json or otherwise?
    public static $variableIDs = [
    'pathwayname','pathwayspecies', 'pathwayimagepage',
    'pathwaygpmlpage', 'pathwayoftheday'
    ];
    protected static $expanded;

    protected static function getVariableIDs() 
    {
        if (!self::$expanded ) {
            self::$expanded = array_map(
                function ( $word ) {
                    $upper = strtoupper("mag_" . $word);

                    // how this is handled in older code
                    define($upper, $upper);

                    return $upper;
                }, self::$variableIDs
            );
        }
        return self::$expanded;
    }

    protected static function getPathwayVariable( $pathway, $index ) 
    {
        switch ( $index ) {
        case 'MAG_PATHWAYNAME':
            return $pathway->name();
        case 'MAG_PATHWAYSPECIES':
            return $pathway->species();
        case 'MAG_PATHWAYIMAGEPAGE':
            return $pathway->getFileTitle(FILETYPE_IMG)->getFullText();
        case 'MAG_PATHWAYGPMLPAGE':
            return $pathway->getTitleObject()->getFullText();
        }
    }

    public static function onMagicWordMagicWords( &$magicWords ) 
    {
        foreach ( self::getVariableIDs() as $var ) {
            $magicWords[] = $var;
        }
    }

    public static function onMagicWordwgVariableIDs( &$variables ) 
    {
        foreach ( self::getVariableIDs() as $var ) {
            $variables[] = constant($var);
        }
    }

    public static function onLanguageGetMagic( &$langMagic, $langCode = 0 ) 
    {
        $varID = self::getVariableIDs();
        foreach ( range(0, count($varID) - 1) as $ind ) {
            $langMagic[constant($varID[$ind])] = [ 0, self::$variableIDs[$ind] ];
            $langMagic[$varID[$ind]] = [ 0, self::$variableIDs[$ind] ];
        }
    }

    public static function onParserGetVariableValueSwitch(
        &$parser, &$cache, &$index, &$ret
    ) {
        switch ( $index ) {
        case 'MAG_PATHWAYOFTHEDAY':
            $pwd = new PathwayOfTheDay(null);
            $pw = $pwd->todaysPathway();
            $ret = $pw->getTitleObject()->getFullText();
            break;
        case 'MAG_PATHWAYNAME':
        case 'MAG_PATHWAYSPECIES':
        case 'MAG_PATHWAYIMAGEPAGE':
        case 'MAG_PATHWAYGPMLPAGE':
            $title = $parser->mTitle;
            if ($title->getNamespace() == NS_PATHWAY ) {
                $pathway = Pathway::newFromTitle($title);
                $ret = self::getPathwayVariable($pathway, $index);
            } else {
                $ret = "NOT A PATHWAY";
            }
            break;
        }
        return true;
    }
}
