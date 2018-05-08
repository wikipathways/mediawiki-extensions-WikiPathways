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

use Article;
use AlphabeticPager;
use File;
use Html;
use Title;

abstract class BasePathwaysPager extends AlphabeticPager {
	protected $species;
	protected $tag;
	protected $sortOrder;
	protected $nameSpace = NS_PATHWAY;
	protected $nsName;

	/* 20k is probably too much */
	const MAX_IMG_SIZE = 20480;
	const MAX_IMG_WIDTH = 180;

	/**
	 * @return string bit of html
	 */
	public function getNavigationBar() {
		if ( !$this->isNavigationBarShown() ) {
			return '';
		}

		if ( isset( $this->mNavigationBar ) ) {
			return $this->mNavigationBar;
		}

		$linkTexts = [
			'prev' => $this->msg( 'prevn' )->numParams( $this->mLimit )->escaped(),
			'next' => $this->msg( 'nextn' )->numParams( $this->mLimit )->escaped(),
			'first' => '',
			'last' => ''
		];
		$pagingLinks = $this->getPagingLinks( $linkTexts );

		$this->getOutput()->addModuleStyles( [ 'wpi.browsePathways' ] );
		$this->mNavigationBar =
			 Html::rawElement(
				 "div", [ "class" => "browseNavBar" ],
				 Html::rawElement(
					 "div", [ "class" => "prevNav" ], $pagingLinks['prev']
				 ) . Html::rawElement(
					 "div", [ "class" => "nextNav" ], $pagingLinks['next']
				 )
			 );

		return $this->mNavigationBar;
	}

	/**
	 * @param File $thumb to get data for
	 * @return string
	 */
	public static function thumbToData( File $thumb ) {
		$suffix = $thumb->thumbName( [ "width" => self::MAX_IMG_WIDTH ] );
		return self::imgToData( $thumb, $suffix );
	}

	/**
	 * @param File $img to embed
	 * @param string $suffix type
	 * @return string
	 */
	public static function imgToData( File $img, $suffix = null ) {
		$path = $img->getLocalRefPath( $suffix );

		if (
			$img->isLocal() && $img->exists()
			&& $img->getSize() < self::MAX_IMG_SIZE
		) {
			$data = file_get_contents( $path );
			return "data:" . $img->getMimeType() . ";base64,"
						   . base64_encode( $data );
		}
		return $img->getThumbUrl( $suffix );
	}

	/**
	 * @param Title $title to check
	 * @return bool
	 */
	public static function hasRecentEdit( Title $title ) {
		global $wgPathwayRecentSinceDays;
		$article = new Article( $title );

		$timeStamp = wfTimeStamp( TS_UNIX, $article->getTimestamp() );
		$prev = date_create( "now" );
		$prev->modify( "-$wgPathwayRecentSinceDays days" );
		/* @ indicates we have a unix timestmp */
		$date = date_create( "@$timeStamp" );

		return $date > $prev;
	}

	/**
	 * @return string
	 */
	public function getOffset() {
		return $this->getRequest()->getText( 'offset' );
	}

	/**
	 * @return string
	 */
	public function getLimit() {
		return $this->getRequest()->getText( 'offset' );
	}

	/**
	 * @return bool
	 */
	public function isBackwards() {
		return ( $this->getRequest()->getVal( 'dir' ) == 'prev' );
	}

	/**
	 * @return string
	 */
	public function getOrder() {
		return $this->getRequest()->getVal( 'order' );
	}

	/**
	 * @param BrowsePathways $page object to use
	 */
	public function __construct( BrowsePathways $page ) {
		$this->page = $page;
		$this->mExtraSortFields = [];
		$this->species = $page->getSpecies();
		if ( $page->getTag() !== "---" ) {
			$this->tag = $page->getTag();
		} else {
			$label = CurationTag::getUserVisibleTagNames();
			$this->tag = $label[ wfMessage( 'browsepathways-all-tags' )->plain() ];
		}

		// Following bit copy-pasta from Pager's IndexPager with some bits replace
		// so we don't rely on $this->getOffset() in the constructor

		// NB: the offset is quoted, not validated. It is treated as an
		// arbitrary string to support the widest variety of index types. Be
		// careful outputting it into HTML!
		$this->mOffset = $this->getOffset();

		// Use consistent behavior for the limit options
		$this->mDefaultLimit = intval( $this->getUser()->getOption( 'rclimit' ) );
		$this->mLimit = $this->getLimit();

		$this->mIsBackwards = $this->isBackwards();
		$this->mDb = wfGetDB( DB_SLAVE );

		$index = $this->getIndexField();
		$order = $this->getOrder();
		if ( is_array( $index ) && isset( $index[$order] ) ) {
			$this->mOrderType = $order;
			$this->mIndexField = $index[$order];
		} elseif ( is_array( $index ) ) {
			// First element is the default
			reset( $index );
			list( $this->mOrderType, $this->mIndexField ) = each( $index );
		} else {
			// $index is not an array
			$this->mOrderType = null;
			$this->mIndexField = $index;
		}

		if ( !isset( $this->mDefaultDirection ) ) {
			$dir = $this->getDefaultDirections();
			$this->mDefaultDirection = is_array( $dir )
									 ? $dir[$this->mOrderType]
									 : $dir;
		}
	}

	/**
	 * @return array
	 */
	public function getQueryInfo() {
		$qinf = [
			'noptions' => [ 'DISTINCT' ],
			'tables' => [ 'page', 'tag as t0', 'tag as t1' ],
			'fields' => [ 't1.tag_text', 'page_title' ],
			'conds' => [
				'page_is_redirect' => '0',
				'page_namespace' => $this->nameSpace,
				't0.tag_name' => $this->tag,
				't1.tag_name' => 'cache-name'
			],
			'join_conds' => [
				'tag as t0' => [ 'JOIN', 't0.page_id = page.page_id' ],
				'tag as t1' => [ 'JOIN', 't1.page_id = page.page_id' ],
			]
		];
		if ( $this->species !== '---' ) {
			$species = preg_replace( "/_/", " ", $this->species );
			$qinf['tables'][] = 'tag as t2';
			$qinf['join_conds']['tag as t2'] = [ 'JOIN', 't2.page_id = page.page_id' ];
			$qinf['conds']['t2.tag_text'] = $species;
		}

		return $qinf;
	}

	public function getIndexField() {
		return 't1.tag_text';
	}

	public function getGPMLlink( $pathway ) {
		if ( $pathway->getActiveRevision() ) {
			$oldid = "&oldid={$pathway->getActiveRevision()}";
		}
		return XML::Element(
			"a",
			[
				"href" => WPI_SCRIPT_URL . "?action=downloadFile&type=gpml&pwTitle="
				. $pathway->getTitleObject()->getFullText() . $oldid
			], " (gpml) "
		);
	}

	public function getThumb(
		$pathway, $icons, $boxwidth = self::MAX_IMG_WIDTH, $withText = true
	) {
		global $wgContLang;

		$label = $pathway->name() . '<br/>';
		if ( $this->species === '---' ) {
			$label .= "(" . $pathway->species() . ")<br/>";
		}
		$label .= $icons;

		$boxheight = -1;
		$href = $pathway->getFullURL();
		$class = "browsePathways infiniteItem";
		$pathId = $pathway->getTitleObject();
		$textalign = $wgContLang->isRTL() ? ' style="text-align:right"' : '';
		$oboxwidth = $boxwidth + 2;

		$this->getOutput()->addModuleStyles( [ 'wpi.browsePathways' ] );

		$show = "<div id='{$pathId}' class='{$class}'>"
		   . "<div class='thumbinner' style='width:{$oboxwidth}px;'>"
		   . '<a href="'.$href.'" class="internal">';

		$link = "";
		$img = $pathway->getImage();

		if ( !$img->exists() ) {
			$pathway->updateCache( FILETYPE_PNG );
		}
		$thumbUrl = '';
		$error = '';

		$width  = $img->getWidth();
		$height = $img->getHeight();

		$thumb = $img->transform( [ 'width' => $boxwidth, 'height' => $boxheight ] );
		if ( $thumb ) {
			$thumbUrl = $this->thumbToData( $img );
			$boxwidth = $thumb->getWidth();
			$boxheight = $thumb->getHeight();
		} else {
			$error = $img->getLastError();
		}

		if ( $thumbUrl == '' ) {
			// Couldn't generate thumbnail? Scale the image client-side.
			$thumbUrl = $img->getViewURL();
			if ( $boxheight == -1 ) {
				// Approximate...
				$boxheight = intval( $height * $boxwidth / $width );
			}
		}
		if ( $error ) {
			$show .= htmlspecialchars( $error );
		} else {
			$show .= '<img src="'.$thumbUrl.'" '.
				  'width="'.$boxwidth.'" height="'.$boxheight.'" ' .
				  'longdesc="'.$href.'" class="thumbimage" />';
			/* No link to download $link = $this->getGPMLlink( $pathway ); */
		}

		$show .= '</a>';
		if ( $withText ) {
			$show .= $link.'<div class="thumbcaption"'.$textalign.'>'.$label."</div>";
		}
		$show .= "</div></div>";

		return str_replace( "\n", ' ', $show );
	}

	/**
	 * @param Title $title of pathway
	 * @return string html
	 */
	public function formatTags( Title $title ) {
		$tags = CurationTag::getCurationImagesForTitle( $title );
		ksort( $tags );

		$tagLabel = "<span class='tagIcons'>";
		foreach ( $tags as $label => $attr ) {
			$img = wfLocalFile( $attr['img'] );
			$imgLink = Html::element( 'img', [
				'src' => $this->imgToData( $img ),
				"title" => $label
			] );
			$href = $this->getRequest()->appendQueryValue( "tag", $attr['tag'] );
			$tagLabel .= Html::element( 'a', [ 'href' => $href ], null ) . $imgLink . "</a>";
		}
		$tagLabel .= "</span>";
		return $tagLabel;
	}
}
