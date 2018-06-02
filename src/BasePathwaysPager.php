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
use RequestContext;
use ThumbnailImage;
use Title;
use WikiPathways\PathwayCache\Factory;

abstract class BasePathwaysPager extends AlphabeticPager {
	protected $species;
	protected $tag;
	protected $sortOrder;
	protected $nameSpace = NS_PATHWAY;
	protected $nsName = "Pathway";

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
			'prev' => $this->msg( 'prevn' )->numParams(
				$this->mLimit
			)->escaped(),
			'next' => $this->msg( 'nextn' )->numParams(
				$this->mLimit
			)->escaped(),
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
	public static function thumbToData( ThumbnailImage $thumb ) {
		// $suffix = $thumb->thumbName( [ "width" => self::MAX_IMG_WIDTH ] );
		// return self::imgToData( $thumb, $suffix );
		return self::imgToData( $thumb, self::MAX_IMG_WIDTH );
	}

	/**
	 * @param File $path that has image
	 * @param int $mime type of the image
	 * @return string
	 */
	public static function pathToData( $path, $mime ) {
		$data = file_get_contents( $path );
		return "data:" . $mime . ";base64," . base64_encode( $data );
	}

	/**
	 * @param ThumbnailImage $img to embed
	 * @param int $width type
	 * @return string
	 */
	public static function imgToData(
		ThumbnailImage $img, $width = self::MAX_IMG_WIDTH
	) {
		$file = $img->getFile();
		$path = $file->getLocalRefPath();

		if ( file_exists( $path ) ) {
			$data = file_get_contents( $path );
			return "data:" . $file->getMimeType() . ";base64,"
						   . base64_encode( $data );
		}
		// return $img->getThumbUrl( $suffix );
		return $file->createThumb( $width );
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
			$this->tag = $label[ wfMessage(
				'browsepathways-all-tags'
			)->plain() ];
		}

		// Following bit copy-pasta from Pager's IndexPager with some
		// bits replace so we don't rely on $this->getOffset() in the
		// constructor

		// NB: the offset is quoted, not validated. It is treated as
		// an arbitrary string to support the widest variety of index
		// types. Be careful outputting it into HTML!
		$this->mOffset = $this->getOffset();

		// Use consistent behavior for the limit options
		$this->mDefaultLimit = intval(
			$this->getUser()->getOption( 'rclimit' )
		);
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
			$this->mOrderType = key( $index );
			$this->mIndexField = current( $index );
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
			$qinf['join_conds']['tag as t2']
				= [ 'JOIN', 't2.page_id = page.page_id' ];
			$qinf['conds']['t2.tag_text'] = $species;
		}

		return $qinf;
	}

	/**
	 * @return string
	 */
	public function getIndexField() {
		return 't1.tag_text';
	}

	/**
	 * Get html to point to a pathway.
	 *
	 * @param Pathway $pathway to use
	 * @return string
	 */
	public function getGPMLlink( Pathway $pathway ) {
		if ( $pathway->getActiveRevision() ) {
			$oldid = "&oldid={$pathway->getActiveRevision()}";
		}
		return Html::Element(
			"a",
			[
				"href" => WPI_SCRIPT_URL
				. "?action=downloadFile&type=gpml&pwTitle="
				. $pathway->getTitleObject()->getFullText() . $oldid
			], " (gpml) "
		);
	}

	private function getLabel( $pathway, $icons ) {
		$label = $pathway->name() . Html::element( 'br' );
		if ( $this->species === '---' ) {
			$label .= "(" . $pathway->species() . Html::element( "br" );
		}
		return $label . $icons;
	}

	private function startThumb( Pathway $pathway, $boxwidth ) {
		$pathId = $pathway->getTitleObject();
		$href = $pathway->getFullURL();
		$oboxwidth = $boxwidth + 2;
		$class = "browsePathways infiniteItem";

		return Html::openElement( "div", [
			'id' => $pathId, 'class' => $class
		] ) . Html::openElement( 'div', [
			'class' => 'thumbinner', 'style' => "width:{$oboxwidth}px;"
		] ) . Html::openElement( 'a', [
			'href' => $href, 'class' => 'internal'
		] );
	}

	/**
	 * Get html for img
	 *
	 * @param Pathway $pathway to get html for
	 * @param int $boxwidth the width of the ... box
	 * @return string html
	 */
	public function getImgElement( Pathway $pathway, $boxwidth ) {
		$png = Factory::getCache( "PNG", $pathway );
		$img = $png->getImgObject();
		$boxheight = -1;

		if ( $png->isCached() ) {
			$thumb = $img->transform( [
				'width' => $boxwidth, 'height' => $boxheight
			] );

			$ret = Html::element( "span", [ "class" => "error" ], $img->getLastError() );
			if ( $thumb ) {
				$width  = $img->getWidth();
				$height = $img->getHeight();

				/* No link to download $link = $this->getGPMLlink( $pathway ); */
				$thumbUrl = $png->getUrl();
				$boxwidth = $thumb->getWidth();
				$boxheight = $thumb->getHeight();

				if ( $thumbUrl === '' ) {
					// Couldn't generate thumbnail? Scale the image client-side.
					$thumbUrl = $img->getViewURL();
					// Approximate...
					$boxheight = min(
						intval( $height * $boxwidth / $width ), 200
					);
				}
				$ret = Html::element( "img", [
					'src' => $thumbUrl,
					'width' => $boxwidth,
					'height' => $boxheight,
					'longdesc' => $pathway->getFullURL(),
					'class' => 'thuumbimage'
				] );
			}
		} else {
			$ret = Html::element(
				"span", [ "class" => "error" ], wfMessage( "wp-pathway-no-thumbnail" )
			);
		}

		return $ret;
	}

	private function endThumb( $pathway, $icons, $withText = true ) {
		global $wgContLang;

		$show = '';
		if ( $withText ) {
			$textalign = $wgContLang->isRTL()
					   ? [ 'style' => "text-align:right" ]
					   : [];

			$show .= Html::rawElement(
				'div', array_merge(
					[ 'class' => "thumbcaption" ], $textalign
				), $this->getLabel( $pathway, $icons ) );
		}
		return $show . Html::closeElement( 'a' ). Html::closeElement( "div" )
					 . Html::closeElement( "div" );
	}

	/**
	 * @param Pathway $pathway to get the thumbnail for
	 * @param string $icons html for representing tags
	 * @param int $boxwidth max width
	 * @param bool $withText show text or no
	 * @return string
	 */
	public function getThumb(
		Pathway $pathway,
		$icons,
		$boxwidth = self::MAX_IMG_WIDTH,
		$withText = true
	) {
		$this->getOutput()->addModuleStyles( [ 'wpi.browsePathways' ] );

		$show = $this->startThumb( $pathway, $boxwidth );
		$show .= $this->getImgElement( $pathway, $boxwidth );
		$show .= $this->endThumb( $pathway, $icons, $withText );

		return str_replace( "\n", ' ', $show );
	}

	/**
	 * @param Title $title of pathway
	 * @return string html
	 */
	public function formatTags( Title $title ) {
		$tags = CurationTag::getCurationImagesForTitle( $title );
		ksort( $tags );

		$tagLabel = Html::openElement( "span", [ 'class' => 'tagIcons' ] );
		foreach ( $tags as $label => $attr ) {
			$img = self::pathToData( $attr['img'], "image/png" );
			$imgLink = Html::element( 'img', [
				'src' => $img,
				'class' => 'pathTag',
				'title' => $label
			] );
			$href = RequestContext::getMain()->getTitle()->getFullURL(
				$this->getRequest()->appendQueryValue( "tag", $attr['tag'] )
			);
			$tagLabel .= Html::rawElement( 'a', [ 'href' => $href ], $imgLink );
		}
		$tagLabel .= Html::closeElement( 'span' );
		return $tagLabel;
	}
}
