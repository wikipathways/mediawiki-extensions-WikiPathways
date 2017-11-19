<?php
/*
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
 */
namespace WikiPathways;

class PathwayContributionsByAuthor {
		private $userid;
		private $pathways;

		public function __construct( $userid ) {
				$this->userid = $userid;
				$this->load();
		}

		private function load() {
				$dbr = wfGetDB( DB_SLAVE );

		// (select * from text order by old_id DESC)
			$sql = "select count(rev_page) as revisions, rev_page as rev_page_id, page_title, old_id as revision_id, old_text, ((select rev_user from revision where rev_page = rev_page_id order by rev_id ASC limit 0, 1) = ".$this->userid.") as isAuthor from revision, page,  text  where rev_text_id = old_id AND page_namespace = 102 AND rev_page = page_id AND rev_user = ". $this->userid. " group by rev_page order by revision_id DESC";

				// echo $sql; die;
				$res = $dbr->query( $sql );

				while ( $row = $dbr->fetchObject( $res ) ) {
			$checkPrivate = 0;
// var_dump( $row); die;
			try{
				$pathway = new Pathway( $row->page_title );
			} catch ( Exception $e ) {
				// ignore if does not exist
				// return array("error"=>"Pathway ".$row->title." does not exist");
			}

// echo "a"; var_dump($pathway->getFullURL());
// echo "b"; var_dump($pathway->isDeleted());
// echo "c"; var_dump($pathway->isPublic());
// echo "d"; var_dump($pathway->getName(true));

			$row->url = $pathway->getFullURL();

			/*check if deleted*/
// $res2 = $dbr->query("select old_text from revision, text where rev_text_id = old_id and rev_page = " . $row->rev_page_id ." order by rev_id DESC limit 0,1" );
// $row2 = $res2->fetchObject($res);

			/*check if private*/
// $res3 = $dbr->query("SELECT tag_text FROM tag WHERE tag_name = 'page_permissions' AND page_id = " . $row->rev_page_id );
// $row->rev_page_id;
// while($row3 = $dbr->fetchObject($res3))
// $checkPrivateTag++;

			/* action upon previous checks */
			if ( /*!substr($row2->old_text, 0, strlen("{{deleted")) === "{{deleted"*/  $pathway->isPublic() && !$pathway->isDeleted() ) {
							$this->pathways[] = $row;
			}
				}
		}

		function getList() {
// return array("aa");
// var_dump($this);
				return $this->pathways;
		}
}
