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
 * Class individual_report_WT_Module
 */
class individual_report_WT_Module extends Report implements ModuleReportInterface {
	/** {@inheritdoc} */
	public function getTitle() {
		return /* I18N: Name of a module/report */ I18N::translate('Individual');
	}

	/** {@inheritdoc} */
	public function getDescription() {
		return /* I18N: Description of the “Individual” module */ I18N::translate('A report of an individual’s details.');
	}

	/** {@inheritdoc} */
	public function defaultAccessLevel() {
		return WT_PRIV_PUBLIC;
	}

	/** {@inheritdoc} */
	public function getReportMenus() {
		global $controller;

		$menus = array();
		$menu = new Menu(
			$this->getTitle(),
			'report.php?ged=' . WT_GEDURL . '&amp;action=setup&amp;report=' . $this->getName() . '&amp;pid=' . $controller->getSignificantIndividual()->getXref(),
			'menu-report-' . $this->getName()
		);
		$menus[] = $menu;

		return $menus;
	}

	/** {@inheritdoc} */
	public function setup() {
		$this->addInput('pid', 'text', I18N::translate('Individual'), 'INDI');
		$this->addInput('sources', 'checkbox', I18N::translate('Show Sources?'), null, '1');
		$this->addInput('notes', 'checkbox', I18N::translate('Show Notes?'), null, '1');
		$this->addInput('photos', 'select', I18N::translate('Show Photos?'), null, 'highlighted');
		$this->addOption('photos', 'none', I18N::translate('None'));
		$this->addOption('photos', 'all', I18N::translate('All'));
		$this->addOption('photos', 'highlighted', I18N::translate('Highlighted Image'));
		$this->showInput('pageSize');
		$this->showInput('fonts');
	}

	/** {@inheritdoc} */
	public function run() {
		$ignore = 'CHAN,CHIL,FAMC,FAMS,HUSB,NAME,NOTE,OBJE,RESN,SEX,SOUR,TITL,WIFE,_UID,_WT_OBJE_SORT';

		$record = GedcomRecord::getInstance($this->get('pid'));
		$indifacts = $record->getFacts();
		$sex    = $record->getSex();
		if (!$record->canShowName()) {
			$name = I18N::translate('Private');
		} else {
			$name = $record->getFullName();
		}

		$img = null;
		switch($this->get('photos')) {
		case 'highlighted':
			$img = $record->findHighlightedMedia();
			break;
		case 'all':
			break;
		}

		$facts     = array();
		$notes     = array();
		$famc_s    = array();
		$fams_s    = array();
		$sources   = array();
		$footnotes = array(null);
		sort_facts($indifacts);
		foreach ($indifacts as $fact) {
			switch($fact->getTag()) {
			case 'SOUR':
				$footnote = $this->getFootnote($fact);
				if (!in_array($footnote, $footnotes)) {
					$footnotes[] = $footnote;
				}

				$sources[] = array_search($footnote, $footnotes);

				break;
			case 'NAME':
				$index = array();
				foreach ($fact->getCitations() as $cite) {
					$footnote = $this->getFootnote($fact, $cite, 2);
					if (!in_array($footnote, $footnotes)) {
						$footnotes[] = $footnote;
					}

					$sources[] = array_search($footnote, $footnotes);
				}
				break;
			case 'NOTE':
				$notes[] = str_replace("\n", '<br>', $fact->getValue());
				break;
			case 'FAMC':
				$famc_s[] = trim($fact->getValue(), '@');
				break;
			case 'FAMS':
				$fams_s[] = trim($fact->getValue(), '@');
				break;
			}

			if (!in_array($fact->getTag(), explode(',', $ignore))) {
				$index = array();
				foreach ($fact->getCitations() as $cite) {
					$footnote = $this->getFootnote($fact, $cite, 2);
					if (!in_array($footnote, $footnotes)) {
						$footnotes[] = $footnote;
					}

					$index[] = array_search($footnote, $footnotes);
				}

				$facts[] = array('label' => $fact->getLabel(),
				                 'date' => $fact->getDate()->display(),
				                 'place' => $fact->getPlace()->getFullName(),
				                 'footnote' => (empty($index) ? false : $index));
			}
		}

		$fam_c = array();
		if (!empty($famc_s)) {
			foreach ($famc_s as $famc) {
				$record = GedcomRecord::getInstance($famc);

				$father = $record->getHusband();
				if ($father) {
					if (!$father->canShowName()) {
						$fname = I18N::translate('Private');
					} else {
						$fname = $father->getFullName();
					}
				}
				$fbirth = array('date' => $father->getBirthDate()->display(), 'place' => $father->getBirthPlace());
				$fdeath = array('date' => $father->getDeathDate()->display(), 'place' => $father->getDeathPlace());

				$mother = $record->getWife();
				if ($mother) {
					if (!$mother->canShowName()) {
						$mname = I18N::translate('Private');
					} else {
						$mname = $mother->getFullName();
					}
				}
				$mbirth = array('date' => $mother->getBirthDate()->display(), 'place' => $mother->getBirthPlace());
				$mdeath = array('date' => $mother->getDeathDate()->display(), 'place' => $mother->getDeathPlace());

				$sibs = array();
				foreach ($record->getFacts('CHIL') as $sibling) {
					if ($sibling->getTarget()->getXref() != $this->get('pid')) {
						$sib   = array();
						$child = $sibling->getTarget();

						if (!$child->canShowName()) {
							$sib['name']   = I18N::translate('Private');
						} else {
							$sib['name']   = $child->getFullName();
						}
						$sib['sex']        = $child->getSex();
						$sib['birthdate']  = $child->getBirthDate()->display();
						$sib['birthplace'] = $child->getBirthPlace();
						$sib['deathdate']  = $child->getDeathDate()->display();
						$sib['deathplace'] = $child->getDeathPlace();

						$sibs[] = $sib;
					}
				}

				$fam_c[] = array('father' => array('name' => $fname, 'birth' => $fbirth, 'death' => $fdeath),
				                 'mother' => array('name' => $mname, 'birth' => $mbirth, 'death' => $mdeath),
				                 'siblings' => $sibs);
			}
		}

		$fam_s = array();
		if (!empty($fams_s)) {
			foreach ($fams_s as $fams) {
				$record = GedcomRecord::getInstance($fams);

				$spouses = array();
				foreach ($record->getSpouses() as $sp_rec) {
					if ($sp_rec->getXref() != $this->get('pid')) {
						if ($sp_rec) {
							if (!$sp_rec->canShowName()) {
								$sname = I18N::translate('Private');
							} else {
								$sname = $sp_rec->getFullName();
							}
						}
						$ssex = $sp_rec->getSex();
						$sbirth = array('date' => $sp_rec->getBirthDate()->display(), 'place' => $sp_rec->getBirthPlace());
						$sdeath = array('date' => $sp_rec->getDeathDate()->display(), 'place' => $sp_rec->getDeathPlace());
					}
				}

				$children = array();
				foreach ($record->getFacts('CHIL') as $child) {
					$child_data = array();
					$child_rec  = $child->getTarget();

					if (!$child_rec->canShowName()) {
						$child_data['name']   = I18N::translate('Private');
					} else {
						$child_data['name']   = $child_rec->getFullName();
					}
					$child_data['sex']        = $child_rec->getSex();
					$child_data['birthdate']  = $child_rec->getBirthDate()->display();
					$child_data['birthplace'] = $child_rec->getBirthPlace();
					$child_data['deathdate']  = $child_rec->getDeathDate()->display();
					$child_data['deathplace'] = $child_rec->getDeathPlace();

					$children[] = $child_data;
				}

				$fam_s[] = array('spouse' => array('name' => $sname, 'birth' => $sbirth, 'death' => $sdeath, 'sex' => $ssex),
				                 'children' => $children);
			}
		}

		$controller = new ReportController;
		$controller
			->setPageTitle($this->getTitle())
			->setStyles(WT_MODULES_DIR . $this->getName())
			->pageHeader();
		?>

		<!-- MARGIN -->
		<?php echo $this->getStyle('headermargin', $img) ?></div>

		<!-- HEADER -->
		<?php echo $this->getStyle('headerdiv', $img) ?>
			<div class="header">
				<span dir="<?php echo I18N::direction() ?>"><?php echo $this->getTitle() ?></span>
			</div>
		</div>

		<!-- BODY -->
		<?php echo $this->getStyle('bodydiv', $img) ?>
			<div>
				<span dir="<?php echo I18N::direction() ?>" class="pageheader">
					<?php echo $name ?>
					<?php if (!empty($sources)) { ?>
						<?php foreach ($sources as $fnote) { ?>
							<a href="#footnote<?php echo $fnote ?>">
								<sup>
									<span dir="<?php echo I18N::direction() ?>" class="footnotenum"><?php echo $fnote ?></span>
								</sup>
							</a>
						<?php } ?>
					<?php } ?>
				</span>
			</div>

			<table class="report_section">
				<tr>
					<td colspan="2" class="title stbgcolor<?php echo $this->get('colors') ?>">
						<span dir="<?php echo I18N::direction() ?>"><?php echo I18N::translate('Facts and events') ?></span>
					</td>
				</tr>
				<!-- Begin Loop - Events -->
				<?php foreach($facts as $fact) { ?>
				<tr>
					<td class="info event_block">
						<span dir="<?php echo I18N::direction() ?>" class="fact"><?php echo I18N::translate($fact['label']) ?>:</span>
						<span dir="<?php echo I18N::direction() ?>" class="text">
							<?php echo $fact['date'] ?>
							<?php if ($fact['footnote'] !== false) { ?>
								<?php foreach ($fact['footnote'] as $fnote) { ?>
									<a href="#footnote<?php echo $fnote ?>">
										<sup>
											<span dir="<?php echo I18N::direction() ?>" class="footnotenum"><?php echo $fnote ?></span>
										</sup>
									</a>
								<?php } ?>
							<?php } ?>
						</span>
					</td>
					<td class="info">
						<span dir="<?php echo I18N::direction() ?>" class="text"><?php echo $fact['place'] ?></span>
					</td>
				</tr>
				<?php } ?>
				<!-- End Loop - Events -->
			</table>

			<!-- If Show Notes -->
			<?php if ($this->get('notes') && !empty($notes)) { ?>
			<table class="report_section">
				<tr>
					<td class="title stbgcolor<?php echo $this->get('color') ?>">
						<span dir="<?php echo I18N::direction() ?>"><?php echo I18N::translate('Notes') ?></span>
					</td>
				</tr>
				<!-- Begin Loop - Notes -->
				<?php foreach ($notes as $note) { ?>
				<tr>
					<td class="info text" >
						<span dir="<?php echo I18N::direction() ?>"><?php echo $note ?></span>
					</td>
				</tr>
				<?php } ?>
				<!-- End Loop - Notes -->
			</table>
			<?php } ?>
			<!-- End if Show Notes -->

			<!-- If Family Exists - w/ Parents -->
			<?php
				if (!empty($famc_s)) {
					foreach ($fam_c as $fam) { ?>
						<table class="report_section">
							<tr>
								<td colspan="3" class="title stbgcolor<?php echo $this->get('color') ?>">
									<span dir="<?php echo I18N::direction() ?>"><?php echo I18N::translate('Family with parents') ?></span>
								</td>
							</tr>
							<!-- Begin Father -->
							<tr>
								<td rowspan="3" class="info fact_block">
									<span dir="<?php echo I18N::direction() ?>" class="fact"><?php echo I18N::translate('Father') ?></span>
								</td>
								<td colspan="2" class="info">
									<span class="name"><?php echo $fam['father']['name'] ?></span>
								</td>
							</tr>
							<tr>
								<td class="info inner_fact_block">
									<span class="fact"><?php echo I18N::translate('Birth') ?>:</span>
								</td>
								<td class="info">
									<span class="text">
										<?php echo $fam['father']['birth']['date'], ' - ', $fam['father']['birth']['place'] ?>
									</span>
								</td>
							</tr>
							<tr>
								<td class="info inner_fact_block">
									<span class="fact"><?php echo I18N::translate('Death') ?>:</span>
								</td>
								<td class="info">
									<span class="text">
										<?php echo $fam['father']['death']['date'], ' - ', $fam['father']['death']['place'] ?>
									</span>
								</td>
							</tr>
							<!-- End Father -->

							<!-- Begin Mother -->
							<tr>
								<td rowspan="3"class="info fact_block">
									<span class="fact"><?php echo I18N::translate('Mother') ?></span>
								</td>
								<td colspan="2" class="info">
									<span class="name"><?php echo $fam['mother']['name'] ?></span>
								</td>
							</tr>
							<tr>
								<td class="info inner_fact_block">
									<span class="fact"><?php echo I18N::translate('Birth') ?>:</span>
								</td>
								<td class="info">
									<span class="text">
										<?php echo $fam['mother']['birth']['date'], ' - ', $fam['mother']['birth']['place'] ?>
									</span>
								</td>
							</tr>
							<tr>
								<td class="info inner_fact_block">
									<span class="fact"><?php echo I18N::translate('Death') ?>:</span>
								</td>
								<td class="info">
									<span class="text">
										<?php echo $fam['mother']['death']['date'], ' - ', $fam['mother']['death']['place'] ?>
									</span>
								</td>
							</tr>
							<!-- End Mother -->

							<!-- Begin Loop - Siblings -->
							<?php foreach ($fam['siblings'] as $sib) { ?>
							<tr>
								<td rowspan="3" class="info fact_block">
									<span class="fact">
										<?php
											switch($sib['sex']) {
											case 'M':
												echo I18N::translate('Brother');
												break;
											case 'F':
												echo I18N::translate('Sister');
												break;
											case 'U':
											case '':
												echo I18N::translate('Gender'), ' ', I18N::translate_c('unknown gender', 'Unknown');
												break;
											}
										?>
									</span>
								</td>
								<td colspan="2" class="info">
									<span class="name"><?php echo $sib['name'] ?></span>
								</td>
							</tr>
							<tr>
								<td class="info inner_fact_block">
									<span class="fact"><?php echo I18N::translate('Birth') ?>:</span>
								</td>
								<td class="info">
									<span class="text"><?php echo $sib['birthdate'], ' - ', $sib['birthplace'] ?></span>
								</td>
							</tr>
							<tr>
								<td class="info inner_fact_block">
									<span class="fact"><?php echo I18N::translate('Death') ?>:</span>
								</td>
								<td class="info">
									<span class="text"><?php echo $sib['deathdate'], ' - ', $sib['deathplace'] ?></span>
								</td>
							</tr>
							<?php } ?>
							<!-- End Loop - Siblings -->

						</table>
						<?php
					}
				}
			?>
			<!-- End If Family Exists - w/ Parents -->

			<!-- If Family Exists - w/ Spouse -->
			<?php
				if(!empty($fams_s)) {
					foreach ($fam_s as $fam) { ?>
						<table class="report_section">
							<tr>
								<td colspan=3 class="title stbgcolor<?php echo $this->get('colors') ?>">
									<span dir="<?php echo I18N::direction() ?>">
										<?php
											switch($fam['spouse']['sex']) {
											case 'M':
												echo I18N::translate('Family with Husband');
												break;
											case 'F':
												echo I18N::translate('Family with Wife');
												break;
											}
										?>
									</span>
								</td>
							</tr>

							<!-- Begin Spouse -->
							<tr>
								<td rowspan="3" class="info fact_block">
									<span dir="<?php echo I18N::direction() ?>" class="fact">
										<?php
											switch($fam['spouse']['sex']) {
											case 'M':
												echo I18N::translate('Husband');
												break;
											case 'F':
												echo I18N::translate('Wife');
												break;
											}
										?>
									</span>
								</td>
								<td colspan="2" class="info">
									<span class="name"><?php echo $fam['spouse']['name'] ?></span>
								</td>
							</tr>
							<tr>
								<td class="info inner_fact_block">
									<span class="fact"><?php echo I18N::translate('Birth') ?>:</span>
								</td>
								<td class="info">
									<span class="text">
										<?php echo $fam['spouse']['birth']['date'], ' - ', $fam['spouse']['birth']['place'] ?>
									</span>
								</td>
							</tr>
							<tr>
								<td class="info inner_fact_block">
									<span class="fact"><?php echo I18N::translate('Death') ?>:</span>
								</td>
								<td class="info">
									<span class="text">
										<?php echo $fam['spouse']['death']['date'], ' - ', $fam['spouse']['death']['place'] ?>
									</span>
								</td>
							</tr>
							<!-- End Spouse -->

							<!-- Begin Loop - Children -->
							<?php foreach ($fam['children'] as $child) { ?>
							<tr>
								<td rowspan="3" class="info fact_block">
									<span dir="<?php echo I18N::direction() ?>" class="fact">
										<?php
											switch($child['sex']) {
											case 'M':
												echo I18N::translate('Son');
												break;
											case 'F':
												echo I18N::translate('Daughter');
												break;
											case 'U':
											case '':
												echo I18N::translate('Gender'), ' ', I18N::translate_c('unknown gender', 'Unknown');
												break;
											}
										?>
									</span>
								</td>
								<td colspan="2" class="info">
									<span class="name"><?php echo $child['name'] ?></span>
								</td>
							</tr>
							<tr>
								<td class="info inner_fact_block">
									<span class="fact"><?php echo I18N::translate('Birth') ?>:</span>
								</td>
								<td class="info">
									<span class="text">
										<?php echo $child['birthdate'], ' - ', $child['birthplace'] ?>
									</span>
								</td>
							</tr>
							<tr>
								<td class="info inner_fact_block">
									<span class="fact"><?php echo I18N::translate('Death') ?>:</span>
								</td>
								<td class="info">
									<span class="text">
										<?php echo $child['deathdate'], ' - ', $child['deathplace'] ?>
									</span>
								</td>
							</tr>
							<?php } ?>
							<!-- End Loop - Children -->
						</table>
						<?php
					}
				}
			?>
			<!-- End If Family Exists - w/ Spouse -->

			<!-- If Footnotes Exists -->
			<?php if (sizeof($footnotes) > 1) { ?>
			<table class="footnote_section">
				<tr>
					<td>
						<span dir="<?php echo I18N::direction() ?>" class="pageheader">
							<?php echo I18N::translate('Sources') ?>
							<br><br>
						</span>
					</td>
				</tr>
				<!-- Begin Loop - Footnotes -->
				<?php for ($i=1; $i<sizeof($footnotes); $i++) { ?>
				<tr>
					<td>
						<a name="footnote<?php echo $i ?>"></a>
						<span class="footnote">
							<?php echo $i, '.&nbsp; ', $footnotes[$i] ?>&nbsp;<br><br>
						</span>
					</td>
				</tr>
				<?php } ?>
				<!-- End Loop - Footnotes -->
			</table>
			<?php } ?>
			<!-- End If Footnotes Exists -->
		</div>

		<!-- MARGIN -->
		<?php echo $this->getStyle('bottommargin', $img) ?></div>

		<!-- FOOTER -->
		<?php echo $this->getStyle('footerdiv', $img) ?>
			<div class="genby" style="position:absolute;top:0pt; left:0pt; height:15pt;">
				<a href="http://www.webtrees.net/">
					<span dir="<?php echo I18N::direction() ?>">Generated by webtrees 1.7.0-dev</span>
				</a>
			</div>
			<div class="now" style="position:absolute;top:19pt; left:0pt; height:15pt;">
				<span dir="<?php echo I18N::direction() ?>">
					<?php echo timestamp_to_gedcom_date(WT_CLIENT_TIMESTAMP)->display() ?>
				</span>
			</div>
		</div>

		<!-- MARGIN -->
		<?php echo $this->getStyle('footermargin', $img) ?></div>

		<?php if ($this->get('photos') != 'none') { ?>
			<!-- Insert picture(s) here -->
			<?php if ($this->get('photos') == 'highlighted' && !empty($img)) { ?>
				<img style="<?php echo $this->getImageStyle('image', 1) ?>" src="<?php echo $img->getHtmlUrlDirect('thumb') ?>">
			<?php } elseif ($this->get('photos') == 'all') { ?>
				
			<?php } ?>
		<?php }
	}
}
