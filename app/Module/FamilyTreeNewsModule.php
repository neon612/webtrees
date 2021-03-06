<?php
namespace Fisharebest\Webtrees;

/**
 * webtrees: online genealogy
 * Copyright (C) 2015 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Class FamilyTreeNewsModule
 */
class FamilyTreeNewsModule extends Module implements ModuleBlockInterface {
	/** {@inheritdoc} */
	public function getTitle() {
		return /* I18N: Name of a module */ I18N::translate('News');
	}

	/** {@inheritdoc} */
	public function getDescription() {
		return /* I18N: Description of the “GEDCOM News” module */ I18N::translate('Family news and site announcements.');
	}

	/** {@inheritdoc} */
	public function getBlock($block_id, $template = true, $cfg = null) {
		global $ctype, $WT_TREE;

		switch (Filter::get('action')) {
		case 'deletenews':
			$news_id = Filter::get('news_id');
			if ($news_id) {
				Database::prepare("DELETE FROM `##news` WHERE news_id = ?")->execute(array($news_id));
			}
			break;
		}

		if (isset($_REQUEST['gedcom_news_archive'])) {
			$limit = 'nolimit';
			$flag  = '0';
		} else {
			$flag = get_block_setting($block_id, 'flag', 0);
			if ($flag === '0') {
				$limit = 'nolimit';
			} else {
				$limit = get_block_setting($block_id, 'limit', 'nolimit');
			}
		}
		if ($cfg) {
			foreach (array('limit', 'flag') as $name) {
				if (array_key_exists($name, $cfg)) {
					$$name = $cfg[$name];
				}
			}
		}
		$usernews = Database::prepare(
			"SELECT SQL_CACHE news_id, user_id, gedcom_id, UNIX_TIMESTAMP(updated) AS updated, subject, body FROM `##news` WHERE gedcom_id=? ORDER BY updated DESC"
		)->execute(array($WT_TREE->getTreeId()))->fetchAll();

		$id    = $this->getName() . $block_id;
		$class = $this->getName() . '_block';
		if ($ctype === 'gedcom' && Auth::isManager($WT_TREE) || $ctype === 'user' && Auth::check()) {
			$title = '<i class="icon-admin" title="' . I18N::translate('Configure') . '" onclick="modalDialog(\'block_edit.php?block_id=' . $block_id . '\', \'' . $this->getTitle() . '\');"></i>';
		} else {
			$title = '';
		}
		$title .= $this->getTitle();

		$content = '';
		if (count($usernews) == 0) {
			$content .= I18N::translate('No news articles have been submitted.') . '<br>';
		}
		$c = 0;
		foreach ($usernews as $news) {
			if ($limit == 'count') {
				if ($c >= $flag) {
					break;
				}
				$c++;
			}
			if ($limit == 'date') {
				if ((int) ((WT_TIMESTAMP - $news->updated) / 86400) > $flag) {
					break;
				}
			}
			$content .= '<div class="news_box" id="article' . $news->news_id . '">';
			$content .= '<div class="news_title">' . Filter::escapeHtml($news->subject) . '</div>';
			$content .= '<div class="news_date">' . format_timestamp($news->updated) . '</div>';
			if ($news->body == strip_tags($news->body)) {
				$news->body = nl2br($news->body, false);
			}
			$content .= $news->body;
			// Print Admin options for this News item
			if (Auth::isManager($WT_TREE)) {
				$content .= '<hr>' . '<a href="#" onclick="window.open(\'editnews.php?news_id=\'+' . $news->news_id . ', \'_blank\', news_window_specs); return false;">' . I18N::translate('Edit') . '</a> | ' . '<a href="index.php?action=deletenews&amp;news_id=' . $news->news_id . '&amp;ctype=' . $ctype . '" onclick="return confirm(\'' . I18N::translate('Are you sure you want to delete this news article?') . "');\">" . I18N::translate('Delete') . '</a><br>';
			}
			$content .= '</div>';
		}
		$printedAddLink = false;
		if (Auth::isManager($WT_TREE)) {
			$content .= "<a href=\"#\" onclick=\"window.open('editnews.php?gedcom_id=" . $WT_TREE->getTreeId() . "', '_blank', news_window_specs); return false;\">" . I18N::translate('Add a news article') . "</a>";
			$printedAddLink = true;
		}
		if ($limit == 'date' || $limit == 'count') {
			if ($printedAddLink) {
				$content .= '&nbsp;&nbsp;|&nbsp;&nbsp;';
			}
			$content .= '<a href="index.php?gedcom_news_archive=yes&amp;ctype=' . $ctype . '">' . I18N::translate('View archive') . "</a>";
			$content .= help_link('gedcom_news_archive') . '<br>';
		}

		if ($template) {
			return Theme::theme()->formatBlock($id, $title, $class, $content);
		} else {
			return $content;
		}
	}

	/** {@inheritdoc} */
	public function loadAjax() {
		return false;
	}

	/** {@inheritdoc} */
	public function isUserBlock() {
		return false;
	}

	/** {@inheritdoc} */
	public function isGedcomBlock() {
		return true;
	}

	/** {@inheritdoc} */
	public function configureBlock($block_id) {
		if (Filter::postBool('save') && Filter::checkCsrf()) {
			set_block_setting($block_id, 'limit', Filter::post('limit'));
			set_block_setting($block_id, 'flag', Filter::post('flag'));
		}

		$limit = get_block_setting($block_id, 'limit', 'nolimit');
		$flag  = get_block_setting($block_id, 'flag', 0);

		echo
			'<tr><td class="descriptionbox wrap width33">',
			I18N::translate('Limit display by:'), help_link('gedcom_news_limit'),
			'</td><td class="optionbox"><select name="limit"><option value="nolimit" ',
			($limit == 'nolimit' ? 'selected' : '') . ">",
			I18N::translate('No limit') . "</option>",
			'<option value="date" ' . ($limit == 'date' ? 'selected' : '') . ">" . I18N::translate('Age of item') . "</option>",
			'<option value="count" ' . ($limit == 'count' ? 'selected' : '') . ">" . I18N::translate('Number of items') . "</option>",
			'</select></td></tr>';

		echo '<tr><td class="descriptionbox wrap width33">';
		echo I18N::translate('Limit:'), help_link('gedcom_news_flag');
		echo '</td><td class="optionbox"><input type="text" name="flag" size="4" maxlength="4" value="' . $flag . '"></td></tr>';
	}
}
