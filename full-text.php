<?php
/**
 * Full Text Content
 *
 * Loads the full text of a feed for feeds that use short summaries.
 * @version 0.1
 */

define('FULLTEXTCONTENT_DIR', dirname(__FILE__));
class FullTextContent {
	protected $feeds = array();
	
	public function __construct() {
		require_once(FULLTEXTCONTENT_DIR . '/Readability.php');
		
		$this->feeds = get_option('fulltext_feeds', array());
		
		add_action('admin_page_fulltext', array(&$this, 'admin'));
		add_filter('subnavigation', array(&$this, 'admin_menu'));
		add_filter('item_data_precache', array(&$this, 'mangle_content'));
	}
	public function admin_menu($menu) {
		$menu['settings.php'][] = array(
			_r('Full Text'), 'admin.php?page=fulltext', ''
		);
		return $menu;
	}
	public function admin() {
		if (isset($_POST['feeds'])) {
			$feeds = array();
			foreach ($_POST['feeds'] as $feed) {
				// Verify them.
				if (Feeds::get_instance()->get($feed) !== false) {
					$feeds[] = $feed;
				}
			}
			update_option('fulltext_feeds', $feeds);
			header('HTTP/1.1 302 Found');
			header('Location: ' . get_option('baseurl') . 'admin/admin.php?page=fulltext&updated=true');
			die();
		}
		
		admin_header(_r('Full Text Content'));
		if (isset($_GET['updated'])) {
			echo '<div class="message"><p>' . _r('Updated feeds!') . '</p></div>';
		}
?>
	<h1><?php _e('Full Text Content'); ?></h1>
	<form action="" method="POST">
		<p>Select which feeds to scrape for full content.</p>
		<table>
<?php
		foreach (Feeds::get_instance()->getAll() as $id => $feed) {
			$attrs = '';
			if (array_search($id, $this->feeds) !== false) {
				$attrs .= ' checked="checked"';
			}
?>
			<tr>
				<td class="checkbox"><input type="checkbox" name="feeds[]" value="<?php echo $id ?>"<?php echo $attrs ?> /></td>
				<td><?php echo $feed['name'] ?></td>
			</tr>
<?php
		}
?>
		</table>
		<p class="buttons"><button type="submit" class="positive"><?php _e('Save'); ?></button></p>
	</form>
<?php
		admin_footer();
	}
	/**
	 * Todo: move to one-time on update instead, to avoid. Also, maybe cache.
	 */
	public function mangle_content($item) {
		if (array_search($item->feed_id, $this->feeds) !== false) {
			$request = new HTTPRequest();
			$result = $request->get($item->permalink);
			if ($result->success) {
				$readability = new Readability($result->body);
				if ($result = $readability->init()) {
					$item->content = $readability->getContent()->innerHTML;
				}
			}
		}
		return $item;
	}
}

$fulltextcontent = new FullTextContent();