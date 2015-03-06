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
 * Class FamilyGroupReportModule
 */
class FamilyGroupReportModule extends Report implements ModuleReportInterface {
	/** {@inheritdoc} */
	public function getTitle() {
		// This text also appears in the .XML file - update both together
		return /* I18N: Name of a module/report */ I18N::translate('Family');
	}

	/** {@inheritdoc} */
	public function getDescription() {
		// This text also appears in the .XML file - update both together
		return /* I18N: Description of the “Family” module */ I18N::translate('A report of family members and their details.');
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
			'report.php?ged=' . WT_GEDURL . '&amp;action=setup&amp;report=' . $this->getName() . '&amp;famid=' . $controller->getSignificantFamily()->getXref(),
			'menu-report-' . $this->getName()
		);
		$menus[] = $menu;

		return $menus;
	}

	/** {@inheritdoc} */
	public function setup() {
		$this->addInput('famid', 'text', I18N::translate('Family'), 'FAM');
		$this->addInput('sources', 'checkbox', I18N::translate('Show Sources?'), null, '1');
		$this->addInput('notes', 'checkbox', I18N::translate('Show Notes?'), null, '1');
		$this->addInput('photos', 'checkbox', I18N::translate('Show Photos?'), null, '1');
		$this->addInput('blanks', 'checkbox', I18N::translate('Print basic events when blank?'), null, '0');
		$this->showInput('pageSize');
		$this->showInput('fonts');
		$this->showInput('colors');
	}

	/** {@inheritdoc} */
	public function run() {
		$footnotes = array(null);
		if ($this->get('blanks')) {
			$ignore  = 'CHAN,NAME,SEX,SOUR,NOTE,OBJE,RESN,FAMC,FAMS,TITL,CHIL,HUSB,WIFE,MARR,BIRT,CHR,BAPM,DEAT,CREM,BURI,_UID,_WT_OBJE_SORT';
			$showall = 'BIRT,CHR,BAPM,DEAT,CREM,BURI';
		} else {
			$ignore  = 'CHAN,NAME,SEX,SOUR,NOTE,OBJE,RESN,FAMC,FAMS,TITL,CHIL,HUSB,WIFE,MARR,_UID,_WT_OBJE_SORT';
			$showall = null;
		}

		$record = GedcomRecord::getInstance($this->get('famid'));
		$famfacts = $record->getFacts();
		if (!$record->canShowName()) {
			$name = I18N::translate('Private');
		} else {
			$name = $record->getFullName();
		}

		if ($this->get('photos')) {
			$img = null;
		}

		//Husband's facts
		$hfacts = array();
		$hrec = $record->getHusband();
		if ($hrec) {
			$hfacts['name']  = (!$hrec->canShowName() ? I18N::translate('Private') : $hrec->getFullName());
			$hfacts['facts'] = array();

			if (!empty($showall)) {
				foreach (explode(',', $showall) as $type) {
					$fact = $hrec->getFirstFact($type);
					$index = array();
					foreach ($fact->getCitations() as $cite) {
						$footnote = $this->getFootnote($fact, $cite, 2);
						if (!in_array($footnote, $footnotes)) {
							$footnotes[] = $footnote;
						}

						$index[] = array_search($footnote, $footnotes);
					}

					if (!empty($fact)) {
						$hfacts['facts'][] = array('label' => $fact->getLabel(),
						                           'date' => $fact->getDate()->display(),
						                           'place' => $fact->getPlace()->getFullName(),
							                       'footnote' => (empty($index) ? false : $index));
					} else {
						$hfacts['facts'][] = GedcomTag::getLabel($type);
					}
				}
			}

			$ifacts = $hrec->getFacts();
			sort_facts($ifacts);
			foreach ($ifacts as $fact) {
				if (!in_array($fact->getTag(), explode(',', $ignore))) {
					$index = array();
					foreach ($fact->getCitations() as $cite) {
						$footnote = $this->getFootnote($fact, $cite, 2);
						if (!in_array($footnote, $footnotes)) {
							$footnotes[] = $footnote;
						}

						$index[] = array_search($footnote, $footnotes);
					}

					$hfacts['facts'][] = array('label' => $fact->getLabel(),
					                           'date' => $fact->getDate()->display(),
					                           'place' => $fact->getPlace()->getFullName(),
					                           'footnote' => (empty($index) ? false : $index));
				}

				if ($fact->getTag() == 'FAMC') {
					$famc = trim($fact->getValue(), '@');
					$rec = GedcomRecord::getInstance($famc);
					$father = $rec->getHusband();
					if ($father) {
						$hfacts['father'] = array('name'       => (!$father->canShowName() ? I18N::translate('Private') : $father->getFullName()),
						                          'birthdate'  => $father->getBirthDate()->display(),
						                          'birthplace' => $father->getBirthPlace(),
						                          'deathdate'  => $father->getDeathDate()->display(),
						                          'deathplace' => $father->getDeathPlace());
					}

					$mother = $rec->getWife();
					if ($mother) {
						$hfacts['mother'] = array('name'       => (!$mother->canShowName() ? I18N::translate('Private') : $mother->getFullName()),
						                          'birthdate'  => $mother->getBirthDate()->display(),
						                          'birthplace' => $mother->getBirthPlace(),
						                          'deathdate'  => $mother->getDeathDate()->display(),
						                          'deathplace' => $mother->getDeathPlace());
					}
				}

				if ($fact->getTag() == 'NOTE') {
				 $hfacts['notes'][] = str_replace("\n", '<br>', $fact->getValue());
				}
			}
		}

		//Wife's facts
		$wfacts = array();
		$wrec = $record->getWife();
		if ($wrec) {
			$wfacts['name']  = (!$wrec->canShowName() ? I18N::translate('Private') : $wrec->getFullName());
			$wfacts['facts'] = array();

			if (!empty($showall)) {
				foreach (explode(',', $showall) as $type) {
					$fact = $wrec->getFirstFact($type);
					$index = array();
					foreach ($fact->getCitations() as $cite) {
						$footnote = $this->getFootnote($fact, $cite, 2);
						if (!in_array($footnote, $footnotes)) {
							$footnotes[] = $footnote;
						}

						$index[] = array_search($footnote, $footnotes);
					}

					if (!empty($fact)) {
						$wfacts['facts'][] = array('label' => $fact->getLabel(),
						                           'date' => $fact->getDate()->display(),
						                           'place' => $fact->getPlace()->getFullName(),
						                           'footnote' => (empty($index) ? false : $index));
					} else {
						$wfacts['facts'][] = GedcomTag::getLabel($type);
					}
				}
			}

			$ifacts = $wrec->getFacts();
			sort_facts($ifacts);
			foreach ($ifacts as $fact) {
				if (!in_array($fact->getTag(), explode(',', $ignore))) {
					$index = array();
					foreach ($fact->getCitations() as $cite) {
						$footnote = $this->getFootnote($fact, $cite, 2);
						if (!in_array($footnote, $footnotes)) {
							$footnotes[] = $footnote;
						}

						$index[] = array_search($footnote, $footnotes);
					}

					$wfacts['facts'][] = array('label' => $fact->getLabel(),
					                           'date' => $fact->getDate()->display(),
					                           'place' => $fact->getPlace()->getFullName(),
					                           'footnote' => (empty($index) ? false : $index));
				}

				if ($fact->getTag() == 'FAMC') {
					$famc = trim($fact->getValue(), '@');
					$rec = GedcomRecord::getInstance($famc);
					$father = $rec->getHusband();
					if ($father) {
						$wfacts['father'] = array('name'       => (!$father->canShowName() ? I18N::translate('Private') : $father->getFullName()),
						                          'birthdate'  => $father->getBirthDate()->display(),
						                          'birthplace' => $father->getBirthPlace(),
						                          'deathdate'  => $father->getDeathDate()->display(),
						                          'deathplace' => $father->getDeathPlace());
					}

					$mother = $rec->getWife();
					if ($mother) {
						$wfacts['mother'] = array('name'       => (!$mother->canShowName() ? I18N::translate('Private') : $mother->getFullName()),
						                          'birthdate'  => $mother->getBirthDate()->display(),
						                          'birthplace' => $mother->getBirthPlace(),
						                          'deathdate'  => $mother->getDeathDate()->display(),
						                          'deathplace' => $mother->getDeathPlace());
					}
				}

				if ($fact->getTag() == 'NOTE') {
				 $wfacts['notes'][] = str_replace("\n", '<br>', $fact->getValue());
				}
			}
		}

		//Children
		foreach ($famfacts as $fact) {
			if ($fact->getTag() == 'CHIL') {
				$crec = $fact->getTarget();
				
				$child = array('name' => (!$crec->canShowName() ? I18N::translate('Private') : $crec->getFullName()),
				               'sex'  => $crec->getSex());

				if (!empty($showall)) {
					foreach (explode(',', $showall) as $type) {
						$fact = $crec->getFirstFact($type);
						$index = array();
						foreach ($fact->getCitations() as $cite) {
							$footnote = $this->getFootnote($fact, $cite, 2);
							if (!in_array($footnote, $footnotes)) {
								$footnotes[] = $footnote;
							}
		
							$index[] = array_search($footnote, $footnotes);
						}
				
						if (!empty($fact)) {
							$child['facts'][] = array('label' => $fact->getLabel(),
							                          'date' => $fact->getDate()->display(),
							                          'place' => $fact->getPlace()->getFullName(),
							                          'footnote' => (empty($index) ? false : $index));
						} else {
							$child['facts'][] = GedcomTag::getLabel($type);
						}
					}
				}
				
				$ifacts = $crec->getFacts();
				sort_facts($ifacts);
				foreach ($ifacts as $fact) {
					$index = array();
					foreach ($fact->getCitations() as $cite) {
						$footnote = $this->getFootnote($fact, $cite, 2);
						if (!in_array($footnote, $footnotes)) {
							$footnotes[] = $footnote;
						}

						$index[] = array_search($footnote, $footnotes);
					}
						
					if (!in_array($fact->getTag(), explode(',', $ignore))) {
						$child['facts'][] = array('label' => $fact->getLabel(), 
						                          'date' => $fact->getDate()->display(),
						                          'place' => $fact->getPlace()->getFullName(),
							                      'footnote' => (empty($index) ? false : $index));
					}
					
					if ($fact->getTag() == 'NOTE') {
						$child['notes'][] = str_replace("\n", '<br>', $fact->getValue());
					}
				}
				
				$children[] = $child;
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
									<span dir="<?php echo I18N::direction() ?>" class="footnotenum">
										<?php echo $fnote ?>
									</span>
								</sup>
							</a>
						<?php } ?>
					<?php } ?>
				</span>
			</div>

			<!-- Begin Husband -->
			<table class="report_section">
				<tr>
					<td colspan="3" class="title malebox_bgcolor<?php echo $this->get('colors') ?>">
						<span dir="<?php echo I18N::direction() ?>" class="fact">
							<?php echo I18N::translate('Husband') ?>:
						</span>
						<span dir="<?php echo I18N::direction() ?>" class="name">
							<?php echo $hfacts['name']?>
						</span>
					</td>
				</tr>
				<!-- Begin Loop - Events -->
				<?php foreach($hfacts['facts'] as $fact) { ?>
				<tr>
					<?php if (is_array($fact)) { ?>
						<td colspan="2" class="info event_block factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span dir="<?php echo I18N::direction() ?>" class="fact">
								<?php echo $fact['label'] ?>:
							</span>
							<span dir="<?php echo I18N::direction() ?>" class="text">
								<?php echo $fact['date'] ?>
								<?php if ($fact['footnote'] !== false) { ?>
									<?php foreach ($fact['footnote'] as $fnote) { ?>
										<a href="#footnote<?php echo $fnote ?>">
											<sup>
												<span dir="<?php echo I18N::direction() ?>" class="footnotenum">
													<?php echo $fnote ?>
												</span>
											</sup>
										</a>
									<?php } ?>
								<?php } ?>
							</span>
						</td>
						<td class="info factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span dir="<?php echo I18N::direction() ?>" class="text"><?php echo $fact['place'] ?></span>
						</td>
					<?php } elseif (!is_array($fact) && $showall) { ?>
						<td class="info event_block factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span dir="<?php echo I18N::direction() ?>" class="fact"><?php echo $fact ?>:</span>
							<span dir="<?php echo I18N::direction() ?>" class="text"></span>
						</td>
						<td class="info factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span dir="<?php echo I18N::direction() ?>" class="text"></span>
						</td>
					<?php } ?>
				</tr>
				<?php } ?>
				<!-- End Loop - Events -->

				<!-- If Parents Exists -->
				<?php if (!empty($hfacts['father'])) { ?>
					<!-- Begin Father -->
					<tr>
						<td rowspan="3" class="info fact_block factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span dir="<?php echo I18N::direction() ?>" class="fact">
								<?php echo I18N::translate('Father') ?>
							</span>
						</td>
						<td colspan="2" class="info malebox_bgcolor<?php echo $this->get('colors') ?>">
							<span class="name"><?php echo $hfacts['father']['name'] ?></span>
						</td>
					</tr>
					<tr>
						<td class="info inner_fact_block factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span class="fact"><?php echo I18N::translate('Birth') ?>:</span>
						</td>
						<td class="info factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span class="text">
								<?php echo $hfacts['father']['birthdate'], ' - ', $hfacts['father']['birthplace'] ?>
							</span>
						</td>
					</tr>
					<tr>
						<td class="info inner_fact_block factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span class="fact"><?php echo I18N::translate('Death') ?>:</span>
						</td>
						<td class="info factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span class="text">
								<?php echo $hfacts['father']['deathdate'], ' - ', $hfacts['father']['deathplace'] ?>
							</span>
						</td>
					</tr>
					<!-- End Father -->
				<?php } ?>

				<?php if(!empty($hfacts['mother'])) { ?>
					<!-- Begin Mother -->
					<tr>
						<td rowspan="3" class="info fact_block factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span class="fact">
								<?php echo I18N::translate('Mother') ?>
							</span>
						</td>
						<td colspan="2" class="info femalebox_bgcolor<?php echo $this->get('colors') ?>">
							<span class="name"><?php echo $hfacts['mother']['name'] ?></span>
						</td>
					</tr>
					<tr>
						<td class="info inner_fact_block factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span class="fact"><?php echo I18N::translate('Birth') ?>:</span>
						</td>
						<td class="info factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span class="text">
								<?php echo $hfacts['mother']['birthdate'], ' - ', $hfacts['mother']['birthplace'] ?>
							</span>
						</td>
					</tr>
					<tr>
						<td class="info inner_fact_block factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span class="fact"><?php echo I18N::translate('Death') ?>:</span>
						</td>
						<td class="info factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span class="text">
								<?php echo $hfacts['mother']['deathdate'], ' - ', $hfacts['mother']['deathplace'] ?>
							</span>
						</td>
					</tr>
					<!-- End Mother -->
				<?php } ?>
				<!-- End If Parents Exists -->

				<!-- If Show Notes -->
				<?php if(!empty($hfacts['notes'])) { ?>
					<?php foreach ($hfacts['notes'] as $note ) { ?>
						<tr>
							<td colspan="3" class="info factbox_bgcolor<?php echo $this->get('colors') ?>">
								<span class="fact"><?php echo I18N::translate('Note') ?>:</span>
								<span class="text">
									<?php echo $note ?>
								</span>
							</td>
						</tr>
					<?php } ?>
				<?php } ?>
				<!-- End If Show Notes -->
			</table>
			<!-- End Husband -->

			<!-- Begin Wife -->
			<table class="report_section">
				<tr>
					<td colspan="3" class="title femalebox_bgcolor<?php echo $this->get('colors') ?>">
						<span dir="<?php echo I18N::direction() ?>" class="fact">
							<?php echo I18N::translate('Wife') ?>:
						</span>
						<span dir="<?php echo I18N::direction() ?>" class="name"><?php echo $wfacts['name']?></span>
					</td>
				</tr>
				<!-- Begin Loop - Events -->
				<?php foreach($wfacts['facts'] as $fact) { ?>
				<tr>
					<?php if (is_array($fact)) { ?>
						<td colspan="2" class="info event_block factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span dir="<?php echo I18N::direction() ?>" class="fact">
								<?php echo $fact['label'] ?>:
							</span>
							<span dir="<?php echo I18N::direction() ?>" class="text">
								<?php echo $fact['date'] ?>
								<?php if ($fact['footnote'] !== false) { ?>
									<?php foreach ($fact['footnote'] as $fnote) { ?>
										<a href="#footnote<?php echo $fnote ?>">
											<sup>
												<span dir="<?php echo I18N::direction() ?>" class="footnotenum">
													<?php echo $fnote ?>
												</span>
											</sup>
										</a>
									<?php } ?>
								<?php } ?>
							</span>
						</td>
						<td class="info factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span dir="<?php echo I18N::direction() ?>" class="text"><?php echo $fact['place'] ?></span>
						</td>
					<?php } elseif (!is_array($fact) && $showall) { ?>
						<td class="info event_block factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span dir="<?php echo I18N::direction() ?>" class="fact"><?php echo $fact ?>:</span>
							<span dir="<?php echo I18N::direction() ?>" class="text"></span>
						</td>
						<td class="info factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span dir="<?php echo I18N::direction() ?>" class="text"></span>
						</td>
					<?php } ?>
				</tr>
				<?php } ?>
				<!-- End Loop - Events -->

				<!-- If Parents Exists -->
				<?php if (!empty($wfacts['father'])) { ?>
					<!-- Begin Father -->
					<tr>
						<td rowspan="3" class="info fact_block factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span dir="<?php echo I18N::direction() ?>" class="fact">
								<?php echo I18N::translate('Father') ?>
							</span>
						</td>
						<td colspan="2" class="info malebox_bgcolor<?php echo $this->get('colors') ?>">
							<span class="name"><?php echo $wfacts['father']['name'] ?></span>
						</td>
					</tr>
					<tr>
						<td class="info inner_fact_block factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span class="fact"><?php echo I18N::translate('Birth') ?>:</span>
						</td>
						<td class="info factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span class="text">
								<?php echo $wfacts['father']['birthdate'], ' - ', $wfacts['father']['birthplace'] ?>
							</span>
						</td>
					</tr>
					<tr>
						<td class="info inner_fact_block factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span class="fact"><?php echo I18N::translate('Death') ?>:</span>
						</td>
						<td class="info factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span class="text">
								<?php echo $wfacts['father']['deathdate'], ' - ', $wfacts['father']['deathplace'] ?>
							</span>
						</td>
					</tr>
					<!-- End Father -->
				<?php } ?>

				<?php if(!empty($wfacts['mother'])) { ?>
					<!-- Begin Mother -->
					<tr>
						<td rowspan="3" class="info fact_block factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span class="fact">
								<?php echo I18N::translate('Mother') ?>
							</span>
						</td>
						<td colspan="2" class="info femalebox_bgcolor<?php echo $this->get('colors') ?>">
							<span class="name"><?php echo $wfacts['mother']['name'] ?></span>
						</td>
					</tr>
					<tr>
						<td class="info inner_fact_block factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span class="fact"><?php echo I18N::translate('Birth') ?>:</span>
						</td>
						<td class="info factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span class="text">
								<?php echo $wfacts['mother']['birthdate'], ' - ', $wfacts['mother']['birthplace'] ?>
							</span>
						</td>
					</tr>
					<tr>
						<td class="info inner_fact_block factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span class="fact"><?php echo I18N::translate('Death') ?>:</span>
						</td>
						<td class="info factbox_bgcolor<?php echo $this->get('colors') ?>">
							<span class="text">
								<?php echo $wfacts['mother']['deathdate'], ' - ', $wfacts['mother']['deathplace'] ?>
							</span>
						</td>
					</tr>
					<!-- End Mother -->
				<?php } ?>
				<!-- End If Parents Exists -->

				<!-- If Show Notes -->
				<?php if(!empty($wfacts['notes'])) { ?>
					<?php foreach ($wfacts['notes'] as $note ) { ?>
						<tr>
							<td colspan="3" class="info factbox_bgcolor<?php echo $this->get('colors') ?>">
								<span class="fact"><?php echo I18N::translate('Note') ?>:</span>
								<span class="text">
									<?php echo $note ?>
								</span>
							</td>
						</tr>
					<?php } ?>
				<?php } ?>
				<!-- End If Show Notes -->
			</table>
			<!-- End Wife -->

			<!-- If Children Exists -->
			<?php if(!empty($children)) { ?>
				<div>
					<span dir="<?php echo I18N::direction() ?>" class="pageheader">
						<br>
						<?php echo I18N::translate('Children') ?>
					</span>
				</div>
				<!-- Begin Loop - Children -->
				<?php foreach ($children as $child) { ?>
					<table class="report_section">
						<tr>
							<td colspan="2" class="title <?php echo $child['sex'], 'box_bgcolor', $this->get('colors') ?>">
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
											echo I18N::translate('Gender'), ' ', I18N::translateContext('unknown gender', 'Unknown');
											break;
										}
									?>:
								</span>
								<span dir="<?php echo I18N::direction() ?>" class="name">
									<?php echo $child['name']?>
								</span>
							</td>
						</tr>
						<!-- Begin Loop - Events -->
						<?php foreach($child['facts'] as $fact) { ?>
						<tr>
							<?php if (is_array($fact)) { ?>
								<td class="info event_block factbox_bgcolor<?php echo $this->get('colors') ?>">	
									<span dir="<?php echo I18N::direction() ?>" class="fact">
										<?php echo $fact['label'] ?>:
									</span>
									<span dir="<?php echo I18N::direction() ?>" class="text">
										<?php echo $fact['date'] ?>
										<?php if ($fact['footnote'] !== false) { ?>
											<?php foreach ($fact['footnote'] as $fnote) { ?>
												<a href="#footnote<?php echo $fnote ?>">
													<sup>
														<span dir="<?php echo I18N::direction() ?>" class="footnotenum">
															<?php echo $fnote ?>
														</span>
													</sup>
												</a>
											<?php } ?>
										<?php } ?>
									</span>
								</td>
								<td class="info factbox_bgcolor<?php echo $this->get('colors') ?>">
									<span dir="<?php echo I18N::direction() ?>" class="text"><?php echo $fact['place'] ?></span>
								</td>
							<?php } elseif (!is_array($fact) && $showall) { ?>
								<td class="info event_block factbox_bgcolor<?php echo $this->get('colors') ?>">
									<span dir="<?php echo I18N::direction() ?>" class="fact"><?php echo $fact ?>:</span>
									<span dir="<?php echo I18N::direction() ?>" class="text"></span>
								</td>
								<td class="info factbox_bgcolor<?php echo $this->get('colors') ?>">
									<span dir="<?php echo I18N::direction() ?>" class="text"></span>
								</td>
							<?php } ?>
						</tr>
						<?php } ?>
						<!-- End Loop - Events -->
		
						<!-- If Show Notes -->
						<?php if(!empty($child['notes'])) { ?>
							<?php foreach ($child['notes'] as $note ) { ?>
								<tr>
									<td colspan="2" class="info factbox_bgcolor<?php echo $this->get('colors') ?>">
										<span class="fact"><?php echo I18N::translate('Note') ?>:</span>
										<span class="text">
											<?php echo $note ?>
										</span>
									</td>
								</tr>
							<?php } ?>
						<?php } ?>
						<!-- End If Show Notes -->
					</table>
				<?php } ?>
				<!-- End Loop - Children -->
			<?php } ?>
			<!-- End If Children Exists -->
		
		
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
		
		<?php if (!empty($img)) { ?>
			<!-- Insert picture(s) here -->
			<img style="<?php echo $this->getImageStyle('image', count($img)) ?>" src="<?php echo $img->getHtmlUrlDirect('thumb') ?>">
		<?php }
	}
}
