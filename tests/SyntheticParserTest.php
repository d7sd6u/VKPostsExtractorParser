<?php
use PHPUnit\Framework\TestCase;

use d7sd6u\VKPostsExtractorParser\Extractor as Parser;

use DiDom\Document;

final class SyntheticParserTest extends TestCase
{
	private $getDomsSynthetic;

	protected function setUp(): void {
		date_default_timezone_set('UTC');

		$this->getDomsSynthetic = function($urls, $context) {
			$doms = array();

			foreach($urls as $url) {
				$encodedUrl = urlencode($url);

				$foundPaths = glob("tests/data/pages/*/$encodedUrl");
				if(empty($foundPaths)) {
					throw new \Exception('Failed to find pregenerated page');
				}
				$str = file_get_contents($foundPaths[0]);
				
				$str = iconv('windows-1251', 'utf-8//ignore', $str);

				try {
					$dom = new Document($str);
					$doms[$url] = $dom;
				} catch(\Exception $e) {
					$doms[$url] = null;
				}
			}

			return $doms;
		};
	}

	/**
	 * @dataProvider pregeneratedPostsBySource
	 */
	public function testGetPostFromSourceStillWorksOnPregeneratedData($sourceId, $pregeneratedPosts): void {
		$parser = new Parser($this->getDomsSynthetic, function($message) {});
		$posts = $parser->getPostsFromSource($sourceId);

		$postsById = array();
		foreach($posts as $post) {
			$postsById[$post['id']] = $post;
		}

		$this->assertEquals($postsById, $pregeneratedPosts);
	}

	public function pregeneratedPostsBySource() {
		$pairs = array();

		$postsBySource = array();

		$postPaths = glob('tests/data/posts/*/*');
		foreach($postPaths as $postPath) {
			$pathAsArray = explode('/', $postPath);
			$postId = array_pop($pathAsArray);
			$sourceId = array_pop($pathAsArray);

			$postInJson = file_get_contents($postPath);
			$post = json_decode($postInJson, true);

			$postsBySource[$sourceId][$post['id']] = $post;
		}

		foreach($postsBySource as $sourceId => $posts) {
			$pairs[] = array($sourceId, $posts);
		}

		return $pairs;
	}

	/**
	 * @dataProvider pregeneratedPostsById
	 */
	public function testGetPostByIdStillWorksOnPregeneratedData($postId, $pregeneratedPost): void {
		$parser = new Parser($this->getDomsSynthetic, function($message) {});
		$post = $parser->getPostById($postId);

		$this->assertEquals($post, $pregeneratedPost);
	}

	public function pregeneratedPostsById() {
		$pairs = array();

		$postsBySource = array();

		$postPaths = glob('tests/data/posts/*/*');
		foreach($postPaths as $postPath) {
			$postInJson = file_get_contents($postPath);
			$post = json_decode($postInJson, true);

			$pairs[] = array($post['id'], $post);
		}

		return $pairs;
	}
}