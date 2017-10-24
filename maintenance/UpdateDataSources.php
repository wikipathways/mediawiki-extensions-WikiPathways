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

use Maintenance;

$mwroot= isset( $IP ) ? $IP : false;
$maint = "/maintenance/Maintenance.php";
$envMW = getenv( "MW_INSTALL_PATH" );
if ( $envMW !== false ) {
    $mwroot = $envMW;
}
if ( !is_readable( "$mwroot$maint") ) {
    die( "Please set MW_INSTALL_PATH to the MediaWiki installation\n" );
}

require "$mwroot$maint";

class UpdateDataSources extends Maintenance {
    public function __construct() {
        parent::__construct();
        $this->addDescription( "Update data sources for WikiPathways" );
    }

    public function execute() {
        $this->output( "Updating datasources cache\n" );
        $start = microtime( true );

        $dir = dirname( DataSourcesCache::getFilename() );
        if ( !file_exists( $dir ) || !is_dir( $dir ) ) {
            $this->output( "Creating Directory ($dir)\n" );
            mkdir( $dir, 1777 );
        }
        DataSourcesCache::update();

        $time = ( microtime( true ) - $start );
        $this->output( "\tUpdated in $time seconds\n" );
    }
}

$maintClass = 'WikiPathways\UpdateDataSources';
require_once RUN_MAINTENANCE_IF_MAIN;

