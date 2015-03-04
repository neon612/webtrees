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
 * Class Report - base class for reports
 */
abstract class Report extends Module {
	/** @var string[] Configuration options for the report */
	private $inputs;

	/** @var float Left Margin (expressed in points) Default: 17.99 mm, 0.7083 inch */
	private $leftmargin = 51.0;

	/** @var float Right Margin (expressed in points) Default: 9.87 mm, 0.389 inch */
	private $rightmargin = 28.0;

	/** @var float Top Margin (expressed in points) Default: 26.81 mm */
	private $topmargin = 76.0;

	/** @var float Bottom Margin (expressed in points) Default: 21.6 mm */
	private $bottommargin = 60.0;

	/** @var float Header Margin (expressed in points) Default: 4.93 mm */
	private $headermargin = 14.0;

	/** @var float Footer Margin (expressed in points) Default: 9.88 mm, 0.389 inch */
	private $footermargin = 28.0;

	/** @var string Page orientation (portrait, landscape) */
	private $orientation = 'portrait';

	/** @var string Page format name */
	private $pageSize = 'A4';

	/** @var float Height of page format in points */
	private $pageh = 0.0;

	/** @var float Width of page format in points */
	private $pagew = 0.0;

	/** @var mixed[][] Styles for report sections */
	private $styles = array();

	/** @var int Cell padding */
	private $padding = 2;

	/** @var int Top position of report element */
	private $top = 30;

	/** @var int Left position of report element */
	private $left = 0;

	/** @var int Height of report element */
	private $height = 0;

	/** @var int Final width of page */
	public $finalwidth;

	/** {@inheritdoc} */
	public function __construct($directory) {
		parent::__construct($directory);
		$this->pageSize = $this->get('pageSize');

		if ($this->inputs === null) {
			$this->inputs = array();
		}

		// For known size pages
		if ($this->pagew == 0 && $this->pageh == 0) {
			/**
			 * The current ISO 216 standard was introduced in 1975 and is a direct follow up to the german DIN 476 standard from 1922. ISO 216 is also called EN 20216 in Europe.
			 * The ISO paper sizes are based on the metric system so everything else is aproxiamte
			 *
			 * The Series A is used for Standard Printing and Stationary.
			 * The Series B is used for Posters, Wall-Charts etc.
			 * The C series is used for folders, post cards and envelopes. C series envelope is suitable to insert A series sizes.
			 * ISO also define format series RA and SRA for untrimmed raw paper, where SRA stands for 'supplementary raw format A'.
			 * Japan has adopted the ISO series A sizes, but its series B sizes are slightly different. These sizes are sometimes called JIS B or JB sizes.
			 *  sun was a unit of length used in Japan and is equal to about 3.03 cm or 1.193 inches
			 * The United States, Canada, and in part Mexico, are today the only industrialized nations in which the ISO standard paper sizes are not yet widely used.
			 *
			 * A0 & A1        Technical drawings, posters
			 * A1 & A2        Flip charts
			 * A2 & A3        Drawings, diagrams, large tables
			 * A4             Letters, magazines, forms, catalogs, laser printer and copying machine output
			 * A5             Note pads
			 * A6             Postcards
			 * B5, A5, B6  A6 Books
			 * C4, C5, C6     Envelopes for A4 letters: unfolded (C4), folded once (C5), folded twice (C6)
			 * B4 & A3        Newspapers, supported by most copying machines in addition to A4
			 * B8 & A8        Playing cards
			 *
			 * 1 inch = 72 points
			 * 1 mm = 2.8346457 points
			 * 1 inch = 25.4 mm
			 * 1 point = 0,35278 mm
			 */
			switch (strtoupper($this->pageSize)) {
			// ISO A series
			case '4A0': // ISO 216, 1682 mm x 2378 mm
				$sizes = array(4767.86, 6740.79);
				break;
			case '2A0': // ISO 216, 1189 mm x 1682 mm
				$sizes = array(3370.39, 4767.86);
				break;
			case 'A0': // ISO 216, 841 mm x 1189mm
				$sizes = array(2383.94, 3370.39);
				break;
			case 'A1': // ISO 216, 594 mm x 841 mm
				$sizes = array(1683.78, 2383.94);
				break;
			case 'A2': // ISO 216, 420 mm x 594 mm
				$sizes = array(1190.55, 1683.78);
				break;
			case 'A3': // ISO 216, 297 mm x 420 mm
				$sizes = array(841.89, 1190.55);
				break;
			case 'A4': // ISO 216, 210 mm 297 mm
				$sizes = array(595.28, 841.89);
				break;
			case 'A5': // ISO 216, 148 mm x 210 mm
				$sizes = array(419.53, 595.28);
				break;
			case 'A6': // ISO 216, 105 mm x 148 mm
				$sizes = array(297.64, 419.53);
				break;
			case 'A7': // ISO 216, 74 mm x 105 mm
				$sizes = array(209.76, 297.64);
				break;
			case 'A8': // ISO 216, 52 mm x 74 mm
				$sizes = array(147.40, 209.76);
				break;
			case 'A9': // ISO 216, 37 mm x 52 mm
				$sizes = array(104.88, 147.40);
				break;
			case 'A10': // ISO 216, 26 mm x 37 mm
				$sizes = array(73.70, 104.88);
				break;

			// ISO B series
			case 'B0': // ISO 216, 1000 mm x 1414 mm
				$sizes = array(2834.65, 4008.19);
				break;
			case 'B1': // ISO 216, 707 mm x 1000 mm
				$sizes = array(2004.09, 2834.65);
				break;
			case 'B2': // ISO 216, 500 mm x 707 mm
				$sizes = array(1417.32, 2004.09);
				break;
			case 'B3': // ISO 216, 353 mm x 500 mm
				$sizes = array(1000.63, 1417.32);
				break;
			case 'B4': // ISO 216, 250 mm x 353 mm
				$sizes = array(708.66, 1000.63);
				break;
			case 'B5': // ISO 216, 176 mm x 250 mm
				$sizes = array(498.90, 708.66);
				break;
			case 'B6': // ISO 216, 125 mm x 176 mm
				$sizes = array(354.33, 498.90);
				break;
			case 'B7': // ISO 216, 88 mm x 125 mm
				$sizes = array(249.45, 354.33);
				break;
			case 'B8': // ISO 216, 62 mm x 88 mm
				$sizes = array(175.75, 249.45);
				break;
			case 'B9': // ISO 216, 44 mm x 62 mm
				$sizes = array(124.72, 175.75);
				break;
			case 'B10': // ISO 216, 31 mm x 44 mm
				$sizes = array(87.87, 124.72);
				break;

			// ISO C series, Envelope
			case 'C0': // ISO 269, 917 mm x 1297 mm, For flat A0 sheet
				$sizes = array(2599.37, 3676.54);
				break;
			case 'C1': // ISO 269, 648 mm x 917 mm, For flat A1 sheet
				$sizes = array(1836.85, 2599.37);
				break;
			case 'C2': // ISO 269, 458 mm x 648 mm, For flat A2 sheet, A1 folded in half
				$sizes = array(1298.27, 1836.85);
				break;
			case 'C3': // ISO 269, 324 mm x 458 mm, For flat A3 sheet, A2 folded in half
				$sizes = array(918.43, 1298.27);
				break;
			case 'C4': // ISO 269, 229 mm x 324 mm, For flat A4 sheet, A3 folded in half
				$sizes = array(649.13, 918.43);
				break;
			case 'C5': // ISO 269, 162 mm x 229 mm, For flat A5 sheet, A4 folded in half
				$sizes = array(459.21, 649.13);
				break;
			case 'C6/5': // ISO 269, 114 mm x 229 mm. A5 folded twice = 1/3 A4. Alternative for the DL envelope
				$sizes = array(323.15, 649.13);
				break;
			case 'C6': // ISO 269, 114 mm x 162 mm, For A5 folded in half
				$sizes = array(323.15, 459.21);
				break;
			case 'C7/6': // ISO 269, 81 mm x 162 mm, For A5 sheet folded in thirds
				$sizes = array(229.61, 459.21);
				break;
			case 'C7': // ISO 269, 81 mm x 114 mm, For A5 folded in quarters
				$sizes = array(229.61, 323.15);
				break;
			case 'C8': // ISO 269, 57 mm x 81 mm
				$sizes = array(161.57, 229.61);
				break;
			case 'C9': // ISO 269, 40 mm x 57 mm
				$sizes = array(113.39, 161.57);
				break;
			case 'C10': // ISO 269, 28 mm x 40 mm
				$sizes = array(79.37, 113.39);
				break;
			case 'DL': // Original DIN 678 but ISO 269 now has this C6/5 , 110 mm x 220 mm, For A4 sheet folded in thirds, A5 in half
				$sizes = array(311.81, 623.62);
				break;

			// Untrimmed stock sizes for the ISO-A Series - ISO primary range
			case 'RA0': // ISO 478, 860 mm x 1220 mm
				$sizes = array(2437.80, 3458.27);
				break;
			case 'RA1': // ISO 478, 610 mm x 860 mm
				$sizes = array(1729.13, 2437.80);
				break;
			case 'RA2': // ISO 478, 430 mm x 610 mm
				$sizes = array(1218.90, 1729.13);
				break;
			case 'RA3': // ISO 478, 305 mm x 430 mm
				$sizes = array(864.57, 1218.90);
				break;
			case 'RA4': // ISO 478, 215 mm x 305 mm
				$sizes = array(609.45, 864.57);
				break;

			// Untrimmed stock sizes for the ISO-A Series - ISO supplementary range
			case 'SRA0': // ISO 593, 900 mm x 1280 mm
				$sizes = array(2551.18, 3628.35);
				break;
			case 'SRA1': // ISO 593, 640 mm x 900 mm
				$sizes = array(1814.17, 2551.18);
				break;
			case 'SRA2': // ISO 593, 450 mm x 640 mm
				$sizes = array(1275.59, 1814.17);
				break;
			case 'SRA3': // ISO 593, 320 mm x 450 mm
				$sizes = array(907.09, 1275.59);
				break;
			case 'SRA4': // ISO 593, 225 mm x 320 mm
				$sizes = array(637.80, 907.09);
				break;

			// ISO size variations
			case 'A2EXTRA': // ISO 216, 445 mm x 619 mm
				$sizes = array(1261.42, 1754.65);
				break;
			case 'A2SUPER': // ISO 216, 305 mm x 508 mm
				$sizes = array(864.57, 1440.00);
				break;
			case 'A3EXTRA': // ISO 216, 322 mm x 445 mm
				$sizes = array(912.76, 1261.42);
				break;
			case 'SUPERA3': // ISO 216, 305 mm x 487 mm
				$sizes = array(864.57, 1380.47);
				break;
			case 'A4EXTRA': // ISO 216, 235 mm x 322 mm
				$sizes = array(666.14, 912.76);
				break;
			case 'A4LONG': // ISO 216, 210 mm x 348 mm
				$sizes = array(595.28, 986.46);
				break;
			case 'A4SUPER': // ISO 216, 229 mm x 322 mm
				$sizes = array(649.13, 912.76);
				break;
			case 'SUPERA4': // ISO 216, 227 mm x 356 mm
				$sizes = array(643.46, 1009.13);
				break;
			case 'A5EXTRA': // ISO 216, 173 mm x 235 mm
				$sizes = array(490.39, 666.14);
				break;
			case 'SOB5EXTRA': // ISO 216, 202 mm x 276 mm
				$sizes = array(572.60, 782.36);
				break;

			// Japanese version of the ISO 216 B series
			case 'JB0': // JIS P 0138-61, 1030 mm x 1456 mm
				$sizes = array(2919.69, 4127.24);
				break;
			case 'JB1': // JIS P 0138-61, 728 mm x 1030 mm
				$sizes = array(2063.62, 2919.69);
				break;
			case 'JB2': // JIS P 0138-61, 515 mm x 728 mm
				$sizes = array(1459.84, 2063.62);
				break;
			case 'JB3': // JIS P 0138-61, 364 mm x 515 mm
				$sizes = array(1031.81, 1459.84);
				break;
			case 'JB4': // JIS P 0138-61, 257 mm x 364 mm
				$sizes = array(728.50, 1031.81);
				break;
			case 'JB5': // JIS P 0138-61, 182 mm x 257 mm
				$sizes = array(515.91, 728.50);
				break;
			case 'JB6': // JIS P 0138-61, 128 mm x 182 mm
				$sizes = array(362.83, 515.91);
				break;
			case 'JB7': // JIS P 0138-61, 91 mm x 128 mm
				$sizes = array(257.95, 362.83);
				break;
			case 'JB8': // JIS P 0138-61, 64 mm x 91 mm
				$sizes = array(181.42, 257.95);
				break;
			case 'JB9': // JIS P 0138-61, 45 mm x 64 mm
				$sizes = array(127.56, 181.42);
				break;
			case 'JB10': // JIS P 0138-61, 32 mm x 45 mm
				$sizes = array(90.71, 127.56);
				break;

			// US pages
			case 'EXECUTIVE': // 7.25 in x 10.5 in
				$sizes = array(522.00, 756.00);
				break;
			case 'FOLIO': // 8.5 in x 13 in
				$sizes = array(612.00, 936.00);
				break;
			case 'FOOLSCAP': // 13.5 in x 17 in
				$sizes = array(972.00, 1224.00);
				break;
			case 'LEDGER': // 11 in x 17 in
				$sizes = array(792.00, 1224.00);
				break;
			case 'LEGAL': // 8.5 in x 14 in
				$sizes = array(612.00, 1008.00);
				break;
			case 'LETTER': // 8.5 in x 11 in
				$sizes = array(612.00, 792.00);
				break;
			case 'QUARTO': // 8.46 in x 10.8 in
				$sizes = array(609.12, 777.50);
				break;
			case 'STATEMENT': // 5.5 in x 8.5 in
				$sizes = array(396.00, 612.00);
				break;
			case 'USGOVT': // 8 in x 11 in
				$sizes = array(576.00, 792.00);
				break;
			default:
				$this->pageSize = 'A4';
				$sizes = array(595.28, 841.89);
				break;
			}
			$this->pagew = $sizes[0];
			$this->pageh = $sizes[1];
		} else {
			if ($this->pagew < 10) {
				throw new \DomainException('REPORT ERROR ReportBase::setup(): For custom size pages you must set "customwidth" larger then this in the XML file');
			}
			if ($this->pageh < 10) {
				throw new \DomainException('REPORT ERROR ReportBase::setup(): For custom size pages you must set "customheight" larger then this in the XML file');
			}
		}

		// Store the pagewidth without margins
		$this->finalwidth = (int) ($this->pagew - $this->leftmargin - $this->rightmargin);
		if (empty($this->styles)) {
			$this->styles['headermargin'] = array('height'   => $this->headermargin,
												  'width'    => $this->finalwidth);
			$this->styles['headerdiv']    = array('height'   => $this->topmargin - $this->headermargin - 6,
												  'width'    => $this->finalwidth);
			$this->styles['bodydiv']      = array('width'    => $this->finalwidth);
			$this->styles['bottommargin'] = array('height'   => $this->bottommargin - $this->footermargin,
												  'width'    => $this->finalwidth);
			$this->styles['footerdiv']    = array('position' => 'relative',
												  'width'    => $this->finalwidth);
			$this->styles['footermargin'] = array('position' => 'relative',
												  'top'      => 35,
												  'height'   => $this->footermargin,
												  'width'    => $this->finalwidth);
			$this->styles['image']        = array('position' => 'absolute',
												  'top'      => 110,
												  'left'     => $this->finalwidth + 8,
												  'height'   => 110,
												  'width'    => 80);
		}
	}

	/**
	 * This will return the option passed to the report
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function get($value) {
		if (array_key_exists($value, $_GET)) {
			return $_GET[$value];
		} else {
			return '0';
		}
	}

	/**
	 * This is a general purpose hook, allowing report modules to respond to routes
	 * of the form module.php?report=FOO&action=BAR
	 *
	 * @param string $mod_action
	 */
	public function modAction($mod_action) {
		switch($mod_action) {
		case 'setup':
			$this->setup();
			$this->printInputs();
			break;
		case 'run':
			$this->run();
			break;
		default:
			http_response_code(404);
			break;
		}
	}

	/**
	 * Adds an input to the report configuration page
	 *
	 * @param string $name
	 * @param string $type
	 * @param string $var
	 * @param string $lookup
	 * @param string $default
	 */
	public function addInput($name, $type, $var, $lookup = null, $default = null) {
		$this->inputs[$name] = array(	'var' => I18N::translate($var),
										'name' => $name,
										'type' => $type,
										'lookup' => $lookup,
										'default' => $default,
										'options' => null,);
	}

	/**
	 * Add an option to a select input
	 */
	public function addOption($name, $key, $value) {
		if(array_key_exists($name, $this->inputs)) {
			$this->inputs[$name]['options'][$key] = $value;
		}
	}

	/**
	 * A specific input control
	 *
	 * @param string $name
	 * @param string $default
	 *
	 * @return array|null
	 */
	public function getInput($name, $default = null) {
		if (array_key_exists($name, $this->inputs)) {
			return $this->inputs[$name];
		}
		else {
			return $default;
		}
	}

	/**
	 * Use a default input
	 *
	 * Some inputs are common, so they are already configured.
	 * Such as: pageSize, fonts, and colors.
	 *
	 * @param string $var
	 */
	 public function showInput($var) {
		switch($var) {
		case 'pageSize':
			$this->inputs[$var] =
				array(	'var' => I18N::translate('Page size'),
						'name' => $var,
						'type' => 'select',
						'lookup' => null,
						'default' => 'A4',
						'options' => array( 'letter' => I18N::translateContext('paper size','Letter'),
											'A3' => I18N::translateContext('paper size', 'A3'),
											'A4' => I18N::translateContext('paper size','A4'),
											'legal' => I18N::translateContext('paper size','Legal')));
			break;

		case 'fonts':
			$this->inputs[$var] =
				array(  'var' => I18N::translate('Font'),
						'name' => $var,
						'type' => 'select',
						'lookup' => null,
						'default' => 'dejavusans',
						'options' => array( 'arialunicid0' => I18N::translateContext('font name', 'Arial'),
											'dejavusans' => I18N::translateContext('font name', 'DejaVu'),
											'helvetica' => I18N::translateContext('font name', 'Helvetica')));
			break;

		case 'colors':
			$this->inputs[$var] =
				array(  'var' => I18N::translate('Use colors'),
						'name' => $var,
						'type' => 'checkbox',
						'lookup' => null,
						'default' => '1',
						'options' => null);
			break;
		}
	}

	/**
	 * Create page with the configured inputs
	 */
	private function printInputs() {
		global $controller;

		$controller
			->setPageTitle($this->getTitle())
			->pageHeader()
			->addExternalJavascript(WT_AUTOCOMPLETE_JS_URL)
			->addInlineJavascript('autocomplete();');

		init_calendar_popup();

		echo '<div id="report-page">
		<form name="setupreport" method="get" action="report.php" onsubmit="if (this.output[1].checked) {this.target=\'_blank\';}">
		<input type="hidden" name="action" value="run">
		<input type="hidden" name="report" value="', $this->getName(), '">
		<table class="facts_table width50">
		<tr><td class="topbottombar" colspan="2">', I18N::translate('Enter report values'), '</td></tr>
		<tr><td class="descriptionbox width30 wrap">', I18N::translate('Report'), '</td>
		<td class="optionbox">', $this->getTitle(), '<br>', $this->getDescription(), '</td></tr>';

		foreach (array_keys($this->inputs) as $key) {
			$this->printInput($key);
		}

		echo '<tr><td class="topbottombar" colspan="2">
		<input type="submit" value="', I18N::translate('continue'), '">
		</td></tr></table></form></div>';
	}

	/**
	 * Print configured input on report configuration page
	 *
	 * @param string $name
	 */
	private function printInput($name) {
		global $controller;

		if (array_key_exists($name, $this->inputs)) {
			$input = $this->inputs[$name];
		}

		echo '<tr><td class="descriptionbox wrap">';
		echo I18N::translate($input['var']), '</td><td class="optionbox">';

		switch($input['type']) {
		case 'text':
			echo '<input';

			switch ($input['lookup']) {
			case 'INDI':
				echo ' data-autocomplete-type="INDI"';
				if (!empty($pid)) {
					$input['default'] = $pid;
				} else {
					$input['default'] = $controller->getSignificantIndividual()->getXref();
				}
				break;
			case 'FAM':
				echo ' data-autocomplete-type="FAM"';
				if (!empty($famid)) {
					$input['default'] = $famid;
				} else {
					$input['default'] = $controller->getSignificantFamily()->getXref();
				}
				break;
			case 'SOUR':
				echo ' data-autocomplete-type="SOUR"';
				if (!empty($sid)) {
					$input['default'] = $sid;
				}
				break;
			case 'DATE':
				if (isset($input['default'])) {
					$input['default'] = strtoupper($input['default']);
				}
				break;
			}

			echo ' type="text" name="', Filter::escapeHtml($input['name']), '" id="', Filter::escapeHtml($input['name']), '" value="', Filter::escapeHtml($input['default']), '" style="direction: ltr;">';
			break;

		case 'checkbox':
			echo '<input type="checkbox" name="', Filter::escapeHtml($input['name']), '" id="', Filter::escapeHtml($input['name']), '" value="1" ';
			echo $input['default'] == '1' ? 'checked' : '';
			echo '>';
			break;

		case 'select':
			echo '<select name="', Filter::escapeHtml($input['name']), '" id="', Filter::escapeHtml($input['name']), '_var">';
			foreach($input['options'] as $name => $value) {
				echo '<option value="' , Filter::escapeHtml($name), '" ';
				if ($input['default'] === $name) {
					echo 'selected';
				}
				echo '>' , Filter::escapeHtml($value), '</option>';
			}
			echo '</select>';
			break;
		}

		if (isset($input['lookup'])) {
			echo '<input type="hidden" name="', Filter::escapeHtml($input['name']), '_type" value="', Filter::escapeHtml($input['lookup']), '">';
			if ($input['lookup'] == 'INDI') {
				echo print_findindi_link('pid');
			} elseif ($input['lookup'] == 'PLAC') {
				echo print_findplace_link($input['name']);
			} elseif ($input['lookup'] == 'FAM') {
				echo print_findfamily_link('famid');
			} elseif ($input['lookup'] == 'SOUR') {
				echo print_findsource_link($input['name']);
			} elseif ($input['lookup'] == 'DATE') {
				echo ' <a href="#" onclick="cal_toggleDate(\'div_', Filter::EscapeJs($input['name']), '\', \'', Filter::EscapeJs($input['name']), '\'); return false;" class="icon-button_calendar" title="', I18N::translate('Select a date'), '"></a>';
				echo '<div id="div_', Filter::EscapeHtml($input['name']), '" style="position:absolute;visibility:hidden;background-color:white;"></div>';
			}
		}
		echo '</td></tr>';
	}

	/**
	 * Get the value of chosen level data in the fact
	 *
	 * @param Fact $fact
	 * @param string $tag
	 * @param int $level
	 *
	 * @return string|null
	 */
	private function getAttribute($fact, $tag, $level=2) {
		foreach (explode("\n", $fact) as $factline) {
			if (preg_match('/' . $level . ' (?:' . $tag . ') ?(.*(?:(?:\n3 CONT ?.*)*)*)/', $factline, $match)) {
				return preg_replace("/\n3 CONT ?/", "\n", $match[1]);
			}
		}

		return null;
	}

	/**
	 * Style for displaying certain parts of the report
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public function getStyle($type) {
		$html = '';

		if (array_key_exists($type, $this->styles)) {
			$html .= '<div id="' . $type . '" style="';

			foreach ($this->styles[$type] as $key => $value) {
				$html .= $key . ':';
				if (is_numeric($value)) {
					if ($key == 'width' && !empty($img)) {
						$value -= 90;
					}
					$html .= $value . 'pt';
				} else {
					$html .= $value;
				}
				$html .= ';';
			}

			$html .= '">';
		}

		return $html;
	}

	/**
	 * Style for images in report
	 *
	 * @param string $type
	 * @param int $img
	 *
	 * @return string
	 */
	public function getImageStyle($type, $img) {
		$html   = '';

		if (array_key_exists($type, $this->styles)) {
			foreach ($this->styles[$type] as $key => $value) {
				$html .= $key . ':';
				if (is_numeric($value)) {
					if ($key == 'top') {
						$html .= ($value * $img) . 'pt';
					} else {
						$html .= $value . 'pt';
					}
				} else {
					$html .= $value;
				}
				$html .= ';';
			}
		}

		return $html;
	}

	/**
	 * Footnote information
	 *
	 * @param Fact $fact
	 * @param string $citation
	 * @param int $level
	 *
	 * @return string
	 */
	public function getFootnote($fact, $citation=null, $level=1) {
		$srcstr = '';

		if (empty($citation)) {
			$citation = $fact->getGedcom();
		}

		if (preg_match_all("/$level SOUR @(.*)@/", $citation, $match, PREG_SET_ORDER)) {
			$srcrec = GedcomRecord::getInstance($match[0][1]);
			if (!empty($srcrec)) {
				$page = $this->getAttribute($citation, 'PAGE', $level + 1);
				$text = $this->getAttribute($citation, 'TEXT', $level + 2);

				foreach ($srcrec->getFacts() as $source) {
					if ($source->getTag() == 'CHAN') {
						continue;
					}

					switch($source->getTag()) {
					case 'AUTH':
						$srcstr .= $source->getValue() . ', ';
						break;
					case 'TITL':
						$srcstr .= '<u>' . $source->getValue() . '</u>';
						break;
					case 'PUBL':
						$srcstr .= ' (' . $source->getValue() . ')';
						break;
					default:
						break;
					}

					if (!empty($page)) {
						$srcstr .= ": $page";
						unset($page);
					}
					if (!empty($text)) {
						$srcstr .= " $text";
						unset($text);
					}
				}
			}
		}
		return $srcstr;
	}
}
