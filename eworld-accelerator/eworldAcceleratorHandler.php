<?php
/**
 * Created by PhpStorm.
 * User: Ben
 * Date: 18/09/2015
 * Time: 17:10
 */

class eworldAcceleratorHandler {

	/** @var string $directory */
	private $directory;
	/** @var EworldAcceleratorAPI $api */
	private $api;

	function __construct() {
		$this->directory = get_option('eacc-dir', '');

		// Hooks
		add_action( 'save_post', array($this, 'savePost') );
		add_action( 'edit_category', array($this, 'saveCategory') );
		add_action( 'delete_category', array($this, 'saveCategory') );
		add_action( 'comment_post', array($this, 'saveComment') );
	}

	/**
	 * @return bool
	 */
	private function getAPI() {
		if (is_object($this->api)) {
			return true;
		}
		else {
			if (file_exists($this->directory) && file_exists($this->directory . 'run_cache.php')) {
				require_once $this->directory . 'inc/EworldAcceleratorAPI.php';

				$this->api = new EworldAcceleratorAPI($this->directory);

				return true;
			}
		}
		return false;
	}

	/**
	 * @param string $permalink
	 * @return bool
	 */
	private function deleteURL($permalink) {
		if (isset($this->api) && is_object($this->api)) {
			$this->api->deleteURL($permalink);
			$_SESSION['cacheDeleted'] = true;
			return true;
		}
		return false;
	}

	/**
	 * @param int $postId
	 */
	public function savePost($postId) {
		// Post
		if (wp_is_post_revision($postId)) {
			$permalink = get_permalink(wp_get_post_parent_id($postId));
		}
		else {
			$permalink = get_permalink($postId);
		}
		if ($permalink != '' && $this->getAPI()) {
			$this->deleteURL($permalink);
		}

		// its categories
		$categoriesList = wp_get_post_categories($postId);
		if (is_array($categoriesList) && sizeof($categoriesList) > 0) {
			foreach ($categoriesList as $currentCategoryId) {
				$this->saveCategory($currentCategoryId);
			}
		}
	}

	/**
	 * Seems to not work with deletion
	 * @param int $categoryId
	 */
	public function saveCategory($categoryId) {
		$permalink = get_category_link($categoryId);
		if ($permalink != '' && $this->getAPI()) {
			$this->deleteURL($permalink);
		}
	}

	/**
	 * @return bool
	 */
	public function deleteAllCache() {
		if ($this->getAPI()) {
			$this->api->deleteAllCachedFiles();
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function garbageCollector() {
		if ($this->getAPI()) {
			$this->api->gc();
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function purgeCDN() {
		if ($this->getAPI()) {
			$this->api->cdnPurge();
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function isCdnActive() {
		if ($this->getAPI()) {
			return $this->api->isCdnActive();
		}
		return false;
	}

	/**
	 * @return string
	 */
	public function getVersion() {
		if ($this->getAPI()) {
			return $this->api->getVersion();
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function isThereSystemUpdate() {
		if ($this->getAPI()) {
			return $this->api->isThereSystemUpdate();
		}
		return false;
	}

	/**
	 * @return string
	 */
	public function getSystemUpdateLink() {
		if ($this->getAPI()) {
			return $this->api->getSystemUpdateLink();
		}
		return false;
	}

	/**
	 * @return string
	 */
	public function getDashboardURL() {
		if ($this->getAPI()) {
			return $this->api->getDashboardURL();
		}
		return false;
	}

	/**
	 * @return string
	 */
	public function getConfigurationURL() {
		if ($this->getAPI()) {
			return $this->api->getConfigurationURL();
		}
		return false;
	}
}
$eworldAcceleratorHandler = new eworldAcceleratorHandler();