<?php
/**
 * Created by PhpStorm.
 * User: Ben
 * Date: 18/09/2015
 * Time: 15:20
 */

class eworldAcceleratorAdmin {
	function __construct() {
		// Session hooks
		add_action( 'init', array($this, 'startSession'));
		add_action( 'wp_logout', array($this, 'stopSession'));

		// Hooks
		add_action( 'admin_menu', array($this, 'registerMenus') );
		add_action( 'admin_notices', array($this, 'noticeCacheDeleted') );
	}

	/**
	 *
	 */
	public function startSession() {
		session_start();
	}

	/**
	 *
	 */
	public function stopSession() {
		if (isset($_SESSION['cacheDeleted'])) {
			unset($_SESSION['cacheDeleted']);
		}
	}

	public function registerMenus() {
		add_menu_page( 'eworld Accelerator', 'eworldAccelerator', 'manage_options', 'eworld-accelerator', array($this, 'adminActions'), 'dashicons-controls-forward', 30);
		add_submenu_page('eworld-accelerator', 'Settings', 'Settings', 'manage_options', 'eworld-accelerator-config', array($this, 'adminConfiguration'));
	}

	private function adminHeader() {
		Global $eworldAcceleratorHandler;
		$version = $eworldAcceleratorHandler->getVersion();
		?>
		<p>eworld Accelerator<?php if ($version != '') echo ' '.$version; ?> speed up your website, following Google PageSpeed and YSlow recommandations.</p>
		<p>For more information, visit <a href="http://www.eworld-accelerator.com" target="_blank">http://www.eworld-accelerator.com</a> or your <a href="http://customer.eworld-accelerator.com/" target="_blank">eworld Accelerator's account</a>.</p>
		<?php
	}

	public function adminActions() {
		Global $eworldAcceleratorHandler;
		$errorList = array();
		$updatedList = array();

		$eacc_dir= get_option('eacc-dir', '');
		if (!is_dir($eacc_dir)) {
			$errorList[] = 'The eworld Accelerator directory is not valid. Please, change it on <a href="?page=eworld-accelerator-config">Settings</a>.';
		}
		else if (!file_exists($eacc_dir.'run_cache.php')) {
			$errorList[] = 'The eworld Accelerator directory entered is not the eworld Accelerator\'s directory. Please, change it on <a href="?page=eworld-accelerator-config">Settings</a>.';
		}
		else if (!file_exists($eacc_dir.'license.txt')) {
			$errorList[] = 'The license.txt file is missing. Connect to <a href="http://customer.eworld-accelerator.com/" target="_blank">your eworld Accelerator\'s account</a> to get yours.';
		}

		if (isset($_POST['submitAction']) && $_POST['submitAction'] == 1) {
			$action = isset($_POST['action']) && $_POST['action'] != '' ? trim($_POST['action']) : '';

			switch($action) {
				case 'deleteAllCache' :
					$eworldAcceleratorHandler->deleteAllCache();
					$updatedList[] = 'All cache has been deleted.';
					break;
				case 'cdnPurge' :
					$eworldAcceleratorHandler->purgeCDN();
					$updatedList[] = 'Purge tasks has been sent to CDN servers. It will take several minutes.';
					break;
				case 'gc' :
					$eworldAcceleratorHandler->garbageCollector();
					$updatedList[] = 'All expired cache has been deleted.';
					break;
			}
		}
		?>
		<div class="wrap">
			<h2>eworld Accelerator</h2>

			<?php if (sizeof($errorList) > 0) : ?>
				<div class="error">
					<p><strong><?php echo join('</strong><br /><strong>', $errorList); ?></strong></p></div>

				<?php $this->adminHeader(); ?>

			<?php else : ?>

				<?php if (sizeof($updatedList) > 0) : ?>
					<div class="updated">
						<p><strong><?php echo join('</strong><br /><strong>', $updatedList); ?></strong></p></div>
				<?php endif; ?>

				<?php $this->adminHeader(); ?>
			<p>
				<a class="button button-primary" href="<?php echo $eworldAcceleratorHandler->getDashboardURL(); ?>" target="_blank">Dashboard</a>&nbsp;
				<a class="button button-primary" href="<?php echo $eworldAcceleratorHandler->getConfigurationURL(); ?>" target="_blank">Configuration</a>
			</p><br />

			<h3>Actions</h3>

			<form novalidate="novalidate" action="" method="post" style="float:left;margin:5px 16px 5px 0;">
				<input type="hidden" value="1" name="submitAction">
				<input type="hidden" value="deleteAllCache" name="action">
				<input type="submit" value="Delete All Cache files" class="button button-primary button-hero" name="submit">
			</form>

			<form novalidate="novalidate" action="" method="post" style="float:left;margin:5px 16px 5px 0;">
				<input type="hidden" value="1" name="submitAction">
				<input type="hidden" value="gc" name="action">
				<input type="submit" value="Garbage Collector" class="button button-primary button-hero" name="gc">
			</form>

			<?php if ($eworldAcceleratorHandler->isCdnActive()) : ?>
			<form novalidate="novalidate" action="" method="post" style="float:left;margin:5px 16px 5px 0;">
				<input type="hidden" value="1" name="submitAction">
				<input type="hidden" value="cdnPurge" name="action">
				<input type="submit" value="Purge CDN files" class="button button-primary button-hero" name="purgeCDN">
			</form>
			<?php endif; ?>

			<?php endif; ?>

		</div>
	<?php
	}

	public function adminConfiguration() {
		$errorList = array();
		$eacc_dir= get_option('eacc-dir', '');

		if (isset($_POST['submitConfig']) && $_POST['submitConfig'] == 1) {
			$directory = isset($_POST['eacc_dir']) && $_POST['eacc_dir'] != '' ? trim($_POST['eacc_dir']) : '';


			if ($directory == '') {
				$errorList[] = 'Directory field is empty';
			}
			else {
				// Add final slash
				if (substr($directory, -1) != '/' && substr($directory, -1) != '\\') {
					$directory .= '/';
				}
				if (!is_dir($directory)) {
					$errorList[] = 'The directory entered is not a valid directory';
				}
				else if (!file_exists($directory.'run_cache.php')) {
					$errorList[] = 'The directory entered is not the eworld Accelerator\'s directory';
				}
				else if (!file_exists($directory.'license.txt')) {
					$errorList[] = 'The license.txt file is missing. Connect to your account to get yours';
				}
				else {
					update_option('eacc-dir', $directory);
					$_GET['settings-updated'] = 'true';
				}
			}
		}
		?>
<div class="wrap">
	<h2>eworld Accelerator Settings</h2>

	<?php if (sizeof($errorList) > 0) : ?>
		<div class="error">
			<p><strong><?php echo join('</strong><br /><strong>', $errorList); ?></strong></p></div>
	<?php endif; ?>
	<?php if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') : ?>
		<div class="updated settings-error" id="setting-error-settings_updated">
			<p><strong>Settings saved.</strong></p></div>
	<?php endif; ?>

	<?php $this->adminHeader(); ?>

	<form novalidate="novalidate" action="" method="post">
		<input type="hidden" value="1" name="submitConfig">
		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row"><label for="blogname">eworld Accelerator Directory</label></th>
				<td><input type="text" class="large-text" value="<?php echo $eacc_dir; ?>" id="eacc_dir" name="eacc_dir">
					<p class="description">for example : <?php echo ABSPATH; ?>eworld-accelerator/</p></td>
			</tr>
			</tbody>
		</table>

		<p class="submit"><input type="submit" value="Save Changes" class="button button-primary" id="submit" name="submit"></p>
	</form>

</div>
		<?php
	}

	public function noticeCacheDeleted() {
		if (isset($_SESSION['cacheDeleted']) && $_SESSION['cacheDeleted']) {
			echo '<div class="updated"> <p>eworld Accelerator cache content deleted</p></div>';
			$_SESSION['cacheDeleted'] = false;
		}
	}
}
$eworldAcceleratorAdmin = new eworldAcceleratorAdmin();