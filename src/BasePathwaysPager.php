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

abstract class BasePathwaysPager extends \AlphabeticPager
{
	protected $species;
	protected $tag;
	protected $sortOrder;
	protected $ns = NS_PATHWAY;
	protected $nsName;

	/* 20k is probably too much */
	const MAX_IMG_SIZE = 20480;
	const MAX_IMG_WIDTH = 180;

	public static function thumbToData( $thumb )
	{
		$data = "";
		$suffix = $thumb->thumbName([ "width" => self::MAX_IMG_WIDTH ]);
		$thumbnail = $thumb->getThumbPath($suffix);

		if ($thumb->isLocal() && file_exists($thumbnail)
			&& filesize($thumbnail) < self::MAX_IMG_SIZE
		) {
			$c = file_get_contents($thumbnail);
			list( $thumbExt, $thumbMime )
			= $thumb->handler->getThumbType($thumb->getExtension(), $thumb->getMimeType());
			return "data:" . $thumbMime . ";base64," . base64_encode($c);
		}
		return $thumb->getThumbUrl($suffix);
	}

	public static function imgToData( $img )
	{
		$data = "";
		$path = $img->getPath();

		if ($img->isLocal() && file_exists($path)
			&& filesize($path) < self::MAX_IMG_SIZE
		) {
			$c = file_get_contents($path);
			return "data:" . $img->getMimeType() . ";base64," . base64_encode($c);
		}
		return $thumb->getThumbUrl($suffix);
	}

	public static function hasRecentEdit( $title )
	{
		global $wgPathwayRecentSinceDays;
		$article = new Article($title);

		$ts = wfTimeStamp(TS_UNIX, $article->getTimestamp());
		$prev = date_create("now");
		$prev->modify("-$wgPathwayRecentSinceDays days");
		/* @ indicates we have a unix timestmp */
		$date = date_create("@$ts");

		return $date > $prev;
	}

	public function getOffset()
	{
		return $this->getOffset()->getText('offset');
	}

	public function getLimit()
	{
		$result = $this->getOffset()->getLimitOffset();
		return $result[0];
	}

	public function isBackwards()
	{
		return ( $this->getOffset()->getVal('dir') == 'prev' );
	}

	public function getOrder()
	{
		return $this->getOffset()->getVal('order');
	}

	public function __construct( $species = "---", $tag = "---", $sortOrder = 0 )
	{
		global $wgCanonicalNamespaceNames;

		if (! isset($wgCanonicalNamespaceNames[ $this->ns ]) ) {
			throw new MWException("Invalid namespace {$this->ns}");
		}
		$this->nsName = $wgCanonicalNamespaceNames[ $this->ns ];
		$this->species = $species;
		$this->sortOrder = $sortOrder;
		if ($tag !== "---" ) {
			$this->tag = $tag;
		} else {
			$label = CurationTag::getUserVisibleTagNames();
			$this->tag = $label[ wfMesage('browsepathways-all-tags')->plain() ];
		}

		// Follwing bit copy-pasta from Pager's IndexPager with some bits replace
		// so we don't rely on $this->getOffset() in the constructor
		global $wgUser;

		// NB: the offset is quoted, not validated. It is treated as an
		// arbitrary string to support the widest variety of index types. Be
		// careful outputting it into HTML!
		$this->mOffset = $this->getOffset();

		// Use consistent behavior for the limit options
		$this->mDefaultLimit = intval($wgUser->getOption('rclimit'));
		$this->mLimit = $this->getLimit();

		$this->mIsBackwards = $this->isBackwards();
		$this->mDb = wfGetDB(DB_SLAVE);

		$index = $this->getIndexField();
		$order = $this->getOrder();
		if (is_array($index) && isset($index[$order]) ) {
			$this->mOrderType = $order;
			$this->mIndexField = $index[$order];
		} elseif (is_array($index) ) {
			// First element is the default
			reset($index);
			list( $this->mOrderType, $this->mIndexField ) = each($index);
		} else {
			// $index is not an array
			$this->mOrderType = null;
			$this->mIndexField = $index;
		}

		if (!isset($this->mDefaultDirection) ) {
			$dir = $this->getDefaultDirections();
			$this->mDefaultDirection = is_array($dir)
			? $dir[$this->mOrderType]
			: $dir;
		}
	}

	public function getQueryInfo()
	{
		$q = [
		'options' => [ 'DISTINCT' ],
		'tables' => [ 'page', 'tag as t0', 'tag as t1' ],
		'fields' => [ 't1.tag_text', 'page_title' ],
		'conds' => [
		'page_is_redirect' => '0',
		'page_namespace' => $this->ns,
		't0.tag_name' => $this->tag,
		't1.tag_name' => 'cache-name'
		],
		'join_conds' => [
		'tag as t0' => [ 'JOIN', 't0.page_id = page.page_id' ],
		'tag as t1' => [ 'JOIN', 't1.page_id = page.page_id' ],
		]
		];
		if ($this->species !== '---' ) {
			$species = preg_replace("/_/", " ", $this->species);
			$q['tables'][] = 'tag as t2';
			$q['join_conds']['tag as t2'] = [ 'JOIN', 't2.page_id = page.page_id' ];
			$q['conds']['t2.tag_text'] = $species;
		}

		return $q;
	}

	public function getIndexField()
	{
		return 't1.tag_text';
		// This should look at $this->sortOrder for the field to sort on.
	}

	public function getTopNavigationBar()
	{
		return $this->getNavigationBar();
	}

	public function getBottomNavigationBar()
	{
		return $this->getNavigationBar();
	}

	public function getGPMLlink( $pathway )
	{
		if ($pathway->getActiveRevision() ) {
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

	public function getThumb( $pathway, $icons, $boxwidth = self::MAX_IMG_WIDTH, $withText = true )
	{
		global $wgContLang;

		$label = $pathway->name() . '<br/>';
		if ($this->species === '---' ) {
			$label .= "(" . $pathway->species() . ")<br/>";
		}
		$label .= $icons;

		$boxheight = -1;
		$framed = false;
		$href = $pathway->getFullURL();
		$class = "browsePathways infinite-item";
		$id = $pathway->getTitleObject();
		$textalign = $wgContLang->isRTL() ? ' style="text-align:right"' : '';
		$oboxwidth = $boxwidth + 2;
		$s = "<div id='{$id}' class='{$class}'>"
		. "<div class='thumbinner' style='width:{$oboxwidth}px;'>"
		. '<a href="'.$href.'" class="internal">';

		$link = "";
		$img = $pathway->getImage();

		if (!$img->exists() ) {
			$s .= "Image does not exist";
		} else {
			$imgURL = $img->getURL();

			$thumbUrl = '';
			$error = '';

			$width  = $img->getWidth();
			$height = $img->getHeight();

			$thumb = $img->getThumbnail($boxwidth, $boxheight);
			if ($thumb ) {
				$thumbUrl = $this->thumbToData($img);
				$boxwidth = $thumb->width;
				$boxheight = $thumb->height;
			} else {
				$error = $img->getLastError();
			}

			if ($thumbUrl == '' ) {
				// Couldn't generate thumbnail? Scale the image client-side.
				$thumbUrl = $img->getViewURL();
				if ($boxheight == -1 ) {
					// Approximate...
					$boxheight = intval($height * $boxwidth / $width);
				}
			}
			if ($error ) {
				$s .= htmlspecialchars($error);
			} else {
				$s .= '<img src="'.$thumbUrl.'" '.
				'width="'.$boxwidth.'" height="'.$boxheight.'" ' .
				'longdesc="'.$href.'" class="thumbimage" />';
				/* No link to download $link = $this->getGPMLlink( $pathway ); */
			}
		}
		$s .= '</a>';
		if ($withText ) {
			$s .= $link.'<div class="thumbcaption"'.$textalign.'>'.$label."</div>";
		}
		$s .= "</div></div>";

		return str_replace("\n", ' ', $s);
	}

	public function formatTags( $title )
	{
		$tags = CurationTag::getCurationImagesForTitle($title);
		ksort($tags);
		$tagLabel = "<span class='tag-icons'>";
		foreach ( $tags as $label => $attr ) {
			$img = wfLocalFile($attr['img']);
			$imgLink = Xml::element('img', [ 'src' => $this->imgToData($img), "title" => $label ]);
			$href = $this->getOffset()->appendQuery([ "tag" => $attr['tag'] ]);
			$tagLabel .= Xml::element('a', [ 'href' => $href ], null) . $imgLink . "</a>";
		}
		$tagLabel .= "</span>";
		return $tagLabel;
	}
}
