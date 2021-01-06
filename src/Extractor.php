<?php
namespace d7sd6u\VKPostsExtractorParser;

require_once "classes/GenericExtractor.php";
require_once "classes/PostExtractor.php";
require_once "classes/PartExtractor.php";
require_once "Utilities.php";

class Extractor extends GenericExtractor {
	private $postExtractor;

	public function __construct($getDoms, $log) {
		$this->getDoms = $getDoms;
		$this->log = $log;

		$this->postExtractor = new PostExtractor($getDoms, $log);
	}

	public function getPostsFromSource($sourceId) {
		$posts = array();
		$urls = array();

		$sourceUrl = 'https://vk.com/' . $sourceId;
		$sourceDom = $this->getDom($sourceUrl, 'source');

		if($sourceDom === null) {
			throw new \Exception('Failed to get source dom from this url: ' . $sourceUrl);
		}

		foreach($sourceDom->find('.post') as $postElem) {
			$postId = substr($postElem->getAttribute('id'), 4);

			try {
				$urls[] = getPostUrlFromId($postId);
			} catch(\Exception $e) {
				$this->log('Failed to extract post\'s url');
				$posts[] = null;
			}
		}

		$postsDoms = $this->getDoms($urls, 'post');

		foreach($postsDoms as $url => $postDom) {
			if($postDom === null) {
				$this->log('Failed to get post dom from this url: ' . $url);
				$posts[] = null;
				continue;
			}

			$this->postExtractor->setDom($postDom);

			if($this->postExtractor->needsMobileDom()) {
				$postId = getPostIdFromUrl($url);
				$mobilePostUrl = getMobilePostUrlFromId($postId);
				$mobileDom = $this->getDom($mobilePostUrl, 'page');

				if($mobileDom === null) {
					$this->log('Failed to get post\'s mobile dom from this url: ' . $mobilePostUrl);
					$posts[] = null;
					continue;
				}

				$this->postExtractor->setMobileDom($mobileDom);
			}

			try {
				$posts[] = $this->postExtractor->extractPost();
			} catch(\Exception $e) {
				$this->log('Failed to extract post: ' . $e->getMessage());
				$posts[] = null;
			}
		}

		return $posts;
	}

   	public function getPostById($postId) {
		$url = getPostUrlFromId($postId);

		$postDom = $this->getDom($url, 'post');
		if($postDom === null) {
			throw new \Exception('Failed to get post dom from this url: ' . $url);
		}

		$this->postExtractor->setDom($postDom);

		if($this->postExtractor->needsMobileDom()) {
			$mobilePostUrl = getMobilePostUrlFromId($postId);
			$mobileDom = $this->getDom($mobilePostUrl, 'post');
			if($mobileDom === null) {
				throw new \Exception('Failed to get post\'s mobile dom from this url: ' . $mobilePostUrl);
			}
			$this->postExtractor->setMobileDom($mobileDom);
		}

		return $this->postExtractor->extractPost();
	}
}
?>
