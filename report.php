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

define('WT_SCRIPT_NAME', 'report.php');
require './includes/session.php';

$controller = new PageController;

$all_modules = Module::getActiveModules();
$rep         = Filter::get('report');
$rep_action  = Filter::get('action', 'choose|setup|run', 'choose');
$output      = 'PDF';

$reports = array();
foreach (Module::getActiveReports() as $r) {
	$reports[$r->getName()] = $r->getTitle();
}

//-- choose a report to run
switch ($rep_action) {
case 'choose':
	$controller
		->setPageTitle(I18N::translate('Choose a report to run'))
		->pageHeader();

	echo '<div id="report-page">
		<form name="choosereport" method="get" action="report.php">
		<input type="hidden" name="action" value="setup">
		<input type="hidden" name="output" value="', Filter::escapeHtml($output), '">
		<table class="facts_table width40">
		<tr><td class="topbottombar" colspan="2">', I18N::translate('Choose a report to run'), '</td></tr>
		<tr><td class="descriptionbox wrap width33 vmiddle">', I18N::translate('Report'), '</td>
		<td class="optionbox"><select name="report">';
	foreach ($reports as $file => $report) {
		echo '<option value="', Filter::escapeHtml($file), '">', Filter::escapeHtml($report), '</option>';
	}
	echo '</select></td></tr>
		<tr><td class="topbottombar" colspan="2"><input type="submit" value="', I18N::translate('continue'), '"></td></tr>
		</table></form></div>';
	break;

default:
	if ($rep && array_key_exists($rep, $all_modules)) {
		$module = $all_modules[$rep];
		$module->modAction($rep_action);
	} else {
		header('Location: ' . WT_BASE_URL);
	}
}
