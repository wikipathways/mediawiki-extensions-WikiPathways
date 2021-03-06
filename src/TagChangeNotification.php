<?php
/**
 * Copyright (C) 2017  J. David Gladstone Institutes
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
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
 * @author Mark A. Hershberger
 */
namespace WikiPathways;

/**
 * Modification to the default EmailNotification class
 * that sets a custom email text.
 */
class TagChangeNotification extends EmailNotification {
	private $taghist;

	function __construct( $taghist ) {
		$this->taghist = $taghist;
	}

	/**
	 * Send emails corresponding to the user $editor editing the page $title.
	 * Also updates wl_notificationtimestamp.
	 *
	 * May be deferred via the job queue.
	 *
	 * @param User $editor
	 * @param Title $title
	 * @param $timestamp
	 * @param $summary
	 * @param $minorEdit
	 * @param $oldid (default: false)
	 */
	function notifyOnTagChange() {
		$taghist = $this->taghist;
		$this->actuallyNotifyOnPageChange(
			User::newFromId( $taghist->getUser() ),
			Title::newFromId( $taghist->getPageId() ),
			$taghist->getTime(),
			$taghist->getAction(),
			"1",
			false
		);
	}

	function composeCommonMailtext() {
		global $wgPasswordSender, $wgNoReplyAddress;
		global $wgEnotifFromEditor, $wgEnotifRevealEditorAddress;
		global $wgEnotifImpersonal;
		$this->composed_common = true;

		$subject = $this->template_subject;
		$body    = $this->template_body;

		$subject = wfMsgForContent( 'tagemail_subject' );
		$body    = wfMsgForContent( 'tagemail_body' );

		$from    = ''; /* fail safe */
		$replyto = ''; /* fail safe */
		$keys    = [];

		$pagetitle = $this->title->getPrefixedText();
		$keys['$PAGETITLE']          = $pagetitle;
		$keys['$PAGETITLE_URL']      = $this->title->getFullUrl();

		$keys['$ACTION'] = $this->taghist->getAction();
		$keys['$TAGNAME'] = CurationTag::getDisplayName( $this->taghist->getTagName() );

		$subject = strtr( $subject, $keys );

		# Reveal the page editor's address as REPLY-TO address only if
		# the user has not opted-out and the option is enabled at the
		# global configuration level.
		$editor = $this->editor;
		$name    = $editor->getName();
		$adminAddress = new MailAddress( $wgPasswordSender, 'WikiPathways' );
		$editorAddress = new MailAddress( $editor );
		if ( $wgEnotifRevealEditorAddress
			&& ( $editor->getEmail() != '' )
			&& $editor->getOption( 'enotifrevealaddr' ) ) {
			if ( $wgEnotifFromEditor ) {
				$from = $editorAddress;
			} else {
				$from    = $adminAddress;
				$replyto = $editorAddress;
			}
		} else {
			$from    = $adminAddress;
			$replyto = new MailAddress( $wgNoReplyAddress );
		}

		if ( $editor->isIP( $name ) ) {
			# real anon (user:xxx.xxx.xxx.xxx)
			$utext = wfMsgForContent( 'enotif_anon_editor', $name );
			$subject = str_replace( '$PAGEEDITOR', $utext, $subject );
			$keys['$PAGEEDITOR']       = $utext;
			$keys['$PAGEEDITOR_EMAIL'] = wfMsgForContent( 'noemailtitle' );
		} else {
			$subject = str_replace( '$PAGEEDITOR', $name, $subject );
			$keys['$PAGEEDITOR']          = $name;
			$emailPage = SpecialPage::getSafeTitleFor( 'Emailuser', $name );
			$keys['$PAGEEDITOR_EMAIL'] = $emailPage->getFullUrl();
		}
		$userPage = $editor->getUserPage();
		$keys['$PAGEEDITOR_WIKI'] = $userPage->getFullUrl();
		$body = strtr( $body, $keys );
		$body = wordwrap( $body, 72 );

		# now save this as the constant user-independent part of the message
		$this->from    = $from;
		$this->replyto = $replyto;
		$this->subject = $subject;
		$this->body    = $body;
		wfDebug( var_export( $this->subject, true ) . " SUBJ_END\n" );
		wfDebug( var_export( $this->body, true ) . " BODY_END\n" );
	}
}
