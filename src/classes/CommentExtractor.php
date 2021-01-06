<?php
namespace d7sd6u\VKPostsExtractorParser;

require_once "GenericExtractor.php";
require_once "PartExtractor.php";
require_once dirname(__DIR__) . "/Utilities.php";

class CommentExtractor extends GenericExtractor {
	private $commentElem;

	private $partExtractor;

	private $comment;

	public function __construct($getDoms, $log) {
		$this->getDoms = $getDoms;
		$this->inheritedLog = $log;

		$commentLog = function($message) {
			$this->log($message);
		};

		$this->partExtractor = new PartExtractor($getDoms, $commentLog);
	}

	protected function log($message) {
		$enrichedMessage = array();

		if(is_string($message)) {
			$enrichedMessage['text'] = $message;
		} else {
			$enrichedMessage = $message;
		}

		if(isset($this->comment['id'])) {
			$enrichedMessage['commentId'] = $this->comment['id'];
		} else {
			$enrichedMessage['commentId'] = null;
		}

		($this->inheritedLog)($enrichedMessage);
	}

	public function setComment($commentElem) {
		$this->commentElem = $commentElem;
	}

	public function extractComment() {
		$this->comment = array();

		$this->comment['id'] = $this->extractCommentId();

		if(!has($this->commentElem, '.reply_text > div')) {
			$this->createDeletedComment();
		} else {
			$this->comment['author'] = $this->extractCommentAuthor();

			$this->comment['text'] = $this->extractCommentText();

			$this->comment['likes'] = $this->extractCommentLikes();

			$this->comment['images'] = $this->extractCommentImages();
			$this->comment['videos'] = $this->extractCommentVideos();
			$this->comment['audios'] = $this->extractCommentAudios();
			$this->comment['files'] = $this->extractCommentFiles();
		}
		$this->comment['timestamp'] = $this->extractCommentTimestamp();

		$this->comment['replyId'] = $this->extractCommentReplyId();

		$this->comment['url'] = $this->extractCommentUrl();

		return $this->comment;
	}

	private function extractCommentId() {
		if(preg_match('/post(-?\d+_\d+)/', $this->commentElem->getAttribute('id'), $matches)) {
			return $matches[1];
		} else {
			$this->log('Failed to extract comment id');
		}
	}

	private function createDeletedComment() {
		$this->comment['author'] = array(
			'name' => 'Comment deleted by author or moderator',
			'link' => '',
			'avatar' => 'https://vk.com/images/wall/deleted_avatar_50.png'
		);
		$this->comment['text'] = array(
			'plaintext' => '',
			'html' => '',
			'emojis' => '',
		);
		$this->comment['likes'] = 0;
		$this->comment['videos'] = $this->comment['files'] = $this->comment['audios'] = $this->comment['images'] = array();
	}

	private function extractCommentAuthor() {
		return array(
			'name' => $this->extractCommentAuthorName(),
			'link' => $this->extractCommentAuthorLink(),
			'avatar' => $this->extractCommentAuthorAvatar()
		);
	}

	private function extractCommentAuthorName() {
		if(has($this->commentElem, '.author')) {
			return $this->commentElem->find('.author')[0]->plaintext;
		} else {
			$this->log('Failed to extract comment author name');
		}
	}

	private function extractCommentAuthorLink() {
		if(hasAttr($this->commentElem, 'href', '.reply_image')) {
			return 'https://vk.com' . $this->commentElem->find('.reply_image')[0]->getAttribute('href');
		} else {
			$this->log('Failed to extract comment author link');
		}
	}

	private function extractCommentAuthorAvatar() {
		if(hasAttr($this->commentElem, 'src', '.reply_img')) {
			return $this->commentElem->find('.reply_img')[0]->getAttribute('src');
		} else {
			$this->log('Failed to extract comment author avatar');
		}
	}

	private function extractCommentText() {
		return $this->partExtractor->extractText($this->commentElem->find('.wall_reply_text'));
	}

	private function extractCommentLikes() {
		if(has($this->commentElem, '.like_button_count')) {
			$likes = $this->commentElem->find('.like_button_count')[0]->plaintext;
			return empty($likes) ? 0 : $likes;
		} else {
			$this->log('extractCommentLikes() failed to find .like_button_count');
		}
	}

	private function extractCommentImages() {
		return $this->partExtractor->extractImages($this->commentElem);
	}

	private function extractCommentVideos() {
		return $this->partExtractor->extractVideos($this->commentElem);
	}

	private function extractCommentAudios() {
		return $this->partExtractor->extractAudios($this->commentElem);
	}

	private function extractCommentFiles() {
		return $this->partExtractor->extractFiles($this->commentElem);
	}

	private function extractCommentTimestamp() {
		if(has($this->commentElem, '.rel_date')) {
			$timestampElem = $this->commentElem->find('.rel_date')[0];
			return $this->partExtractor->extractTimestamp($timestampElem);
		} else {
			$this->log('extractCommentTimestamp() failed to find .rel_date');
		}
	}

	private function extractCommentReplyId() {
		if(has($this->commentElem, '.reply_to')) {
			preg_match('/return wall\.showReply\(this, \'-?\d+_\d+\', \'(-?\d+_\d+)\'/',
				$this->commentElem->find('.reply_to')[0]->getAttribute('onclick'),
				$matches);

			$id = $matches[1];
		// else this comment is...
		} elseif(has($this->commentElem, '.wd_lnk')) {
			preg_match('/wall(-?\d+_)\d+\?reply=(\d+)(&thread=(\d+))?/',
				$this->commentElem->find('.wd_lnk')[0]->getAttribute('href'), $matches);

			if(isset($matches[4])) {
				$id = $matches[1] . $matches[4]; // reply to a branch root
			} else {
				$id = $matches[1] . $matches[2]; // reply to post itself
			}
		} else {
			$this->log('Failed to extract comment reply id');
		}

		return $id;
	}

	private function extractCommentUrl() {
		$commentId = $this->extractCommentId($this->commentElem);
		if($commentId !== null) {
			return 'https://vk.com/wall' . $commentId;
		} else {
			$this->log('Failed to extract comment url');
		}
	}
}