<?php
/**
 * @package     VikRentCar
 * @subpackage  com_vikrentcar
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2021 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class handler for admin widget "collecting today". This widget has settings.
 * 
 * @since 	1.2.0
 */
class VikRentCarAdminWidgetCollectingToday extends VikRentCarAdminWidget
{
	/**
	 * Class constructor will define the widget name and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->widgetName = JText::translate('VRCDASHTODAYPICKUP');
		$this->widgetDescr = JText::translate('VRC_W_COLLECTTOD_DESCR');
		$this->widgetId = basename(__FILE__);
	}

	public function render($data = null)
	{
		$vrc_auth_orders = JFactory::getUser()->authorise('core.vrc.orders', 'com_vikrentcar');
		$today_start_ts = mktime(0, 0, 0, date("n"), date("j"), date("Y"));
		$today_end_ts = mktime(23, 59, 59, date("n"), date("j"), date("Y"));
		$pickup_today = array();

		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`custdata`,`status`,`idcar`,`ritiro`,`consegna`,`idplace`,`idreturnplace`,`country`,`nominative`,`reg` FROM `#__vikrentcar_orders` WHERE `ritiro`>=" . $today_start_ts . " AND `ritiro`<=" . $today_end_ts . " ORDER BY `#__vikrentcar_orders`.`ritiro` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$pickup_today = $dbo->loadAssocList();
		}

		$tot_arrivals = 0;
		foreach ($pickup_today as $in_today) {
			if ($in_today['status'] == 'confirmed') {
				$tot_arrivals++;
			}
		}

		// render the necessary PHP/JS code for the modal window only once
		if (!defined('VRC_JMODAL_ORDER_REGISTRATION')) {
			define('VRC_JMODAL_ORDER_REGISTRATION', 1);
			echo $this->vrc_app->getJmodalScript();
			echo $this->vrc_app->getJmodalHtml('vrc-order-registration', JText::translate('VRC_ORDER_REGISTRATION'));
		}

		?>
		<div class="vrc-admin-widget-wrapper">
			<div class="vrc-admin-widget-head vrc-dashboard-today-pickup-head">
				<h4><i class="vrcicn-enter"></i><?php echo JText::translate('VRCDASHTODAYPICKUP'); ?> <span class="arrivals-tot"><?php echo $tot_arrivals; ?></span></h4>
				<div class="btn-toolbar pull-right vrc-dashboard-search-pickup">
					<div class="btn-wrapper input-append pull-right">
						<input type="text" class="pickup-search form-control" placeholder="<?php echo JText::translate('VRCDASHSEARCHKEYS'); ?>">
						<button type="button" class="btn" onclick="jQuery('.pickup-search').val('').trigger('keyup');"><i class="icon-remove"></i></button>
					</div>
				</div>
			</div>
			<div class="vrc-dashboard-today-pickup table-responsive">
				<table class="table vrc-table-search-cin">
					<thead>
						<tr class="vrc-dashboard-today-pickup-firstrow">
							<th class="center"><?php echo JText::translate('VRCDASHUPRESONE'); ?></th>
							<th class="left"><?php echo JText::translate('VRCDASHUPRESTWO'); ?></th>
							<th class="left"><?php echo JText::translate('VRPVIEWORDERSTWO'); ?></th>
							<th class="center"><?php echo JText::translate('VRCDASHUPRESTHREE'); ?></th>
							<th class="center"><?php echo JText::translate('VRCDASHUPRESFOUR'); ?></th>
							<th class="center"><?php echo JText::translate('VRCDASHUPRESFIVE'); ?></th>
							<th class="center"><?php echo JText::translate('VRC_ORDER_REGISTRATION'); ?></th>
						</tr>
						<tr class="warning no-results">
							<td colspan="7"><i class="vrcicn-warning"></i> <?php echo JText::translate('VRNOLOCFEES'); ?></td>
						</tr>
					</thead>
					<tbody>
					<?php
					if (!count($pickup_today)) {
						?>
						<tr class="warning">
							<td colspan="7"><i class="vrcicn-warning"></i> <?php echo JText::translate('VRCNOPICKUPSTODAY'); ?></td>
						</tr>
						<?php
					}
					foreach ($pickup_today as $next) {
						$car = VikRentCar::getCarInfo($next['idcar']);
						$nominative = strlen($next['nominative']) > 1 ? $next['nominative'] : VikRentCar::getFirstCustDataField($next['custdata']);
						$country_flag = '';
						if (file_exists(VRC_ADMIN_PATH.DS.'resources'.DS.'countries'.DS.$next['country'].'.png')) {
							$country_flag = '<img src="'.VRC_ADMIN_URI.'resources/countries/'.$next['country'].'.png'.'" title="'.$next['country'].'" class="vrc-country-flag vrc-country-flag-left"/>';
						}
						$status_lbl = '';
						if ($next['status'] == 'confirmed') {
							$status_lbl = '<span class="label label-success vrc-status-label">'.JText::translate('VRCONFIRMED').'</span>';
						} elseif ($next['status'] == 'standby') {
							$status_lbl = '<span class="label label-warning vrc-status-label">'.JText::translate('VRSTANDBY').'</span>';
						} elseif ($next['status'] == 'cancelled') {
							$status_lbl = '<span class="label label-error vrc-status-label" style="background-color: #d9534f;">'.JText::translate('VRCANCELLED').'</span>';
						}
						?>
						<tr class="vrc-dashboard-today-pickup-rows">
							<td class="searchable center">
							<?php
							if ($vrc_auth_orders) {
								?>
								<a class="vrc-orderid" href="index.php?option=com_vikrentcar&amp;task=editorder&amp;cid[]=<?php echo $next['id']; ?>"><?php echo $next['id']; ?></a>
								<?php
							} else {
								?>
								<a class="vrc-orderid" href="javascript: void(0);"><?php echo $next['id']; ?></a>
								<?php
							}
							?>
							</td>
							<td class="searchable left"><?php echo $car['name']; ?></td>
							<td class="searchable left"><?php echo $country_flag.$nominative; ?></td>
							<td class="searchable center"><?php echo (!empty($next['idplace']) ? VikRentCar::getPlaceName($next['idplace'])." " : "").date($this->tf, $next['ritiro']); ?></td>
							<td class="searchable center"><?php echo (!empty($next['idreturnplace']) ? VikRentCar::getPlaceName($next['idreturnplace'])." " : "").date($this->df.' '.$this->tf, $next['consegna']); ?></td>
							<td class="center"><?php echo $status_lbl; ?></td>
							<td class="center">
							<?php
							// default registration status = none
							$next['reg'] = (int)$next['reg'];
							$reg_status = JText::translate('VRC_ORDER_REGISTRATION_NONE');
							$reg_class  = 'btn-secondary';
							if ($next['reg'] < 0) {
								// no show
								$reg_status = JText::translate('VRC_ORDER_REGISTRATION_NOSHOW');
								$reg_class  = 'btn-danger';
							} elseif ($next['reg'] === 1) {
								// started
								$reg_status = JText::translate('VRC_ORDER_REGISTRATION_STARTED');
								$reg_class  = 'btn-primary';
							} elseif ($next['reg'] === 2) {
								// terminated
								$reg_status = JText::translate('VRC_ORDER_REGISTRATION_TERMINATED');
								$reg_class  = 'btn-primary';
							}
							if ($next['status'] == 'confirmed') {
								?>
								<button type="button" class="btn btn-small <?php echo $reg_class; ?>" data-regstatusoid="<?php echo $next['id']; ?>" onclick="vrcOpenJModal('vrc-order-registration', 'index.php?option=com_vikrentcar&task=orderregistration&cid[]=<?php echo $next['id']; ?>&tmpl=component');"><?php echo $reg_status; ?></button>
								<?php
							}
							?>
							</td>
						</tr>
						<?php
					}
					?>
					</tbody>
				</table>
			</div>
		</div>

		<script type="text/javascript">
		jQuery(document).ready(function() {
			/* Pick-up Search */
			jQuery(".pickup-search").keyup(function() {
				var inp_elem = jQuery(this);
				var instance_elem = inp_elem.closest('.vrc-admin-widget-wrapper');
				var searchTerm = inp_elem.val();
				var listItem = instance_elem.find('.vrc-table-search-cin tbody').children('tr');
				var searchSplit = searchTerm.replace(/ /g, "'):containsi('");
				jQuery.extend(jQuery.expr[':'], {'containsi': 
					function(elem, i, match, array) {
						return (elem.textContent || elem.innerText || '').toLowerCase().indexOf((match[3] || "").toLowerCase()) >= 0;
					}
				});
				instance_elem.find(".vrc-table-search-cin tbody tr td.searchable").not(":containsi('" + searchSplit + "')").each(function(e) {
					jQuery(this).parent('tr').attr('visible', 'false');
				});
				instance_elem.find(".vrc-table-search-cin tbody tr td.searchable:containsi('" + searchSplit + "')").each(function(e) {
					jQuery(this).parent('tr').attr('visible', 'true');
				});
				var jobCount = parseInt(instance_elem.find('.vrc-table-search-cin tbody tr[visible="true"]').length);
				instance_elem.find('.arrivals-tot').text(jobCount);
				if (jobCount > 0) {
					instance_elem.find('.vrc-table-search-cin').find('.no-results').hide();
				} else {
					instance_elem.find('.vrc-table-search-cin').find('.no-results').show();
				}
			});
		});
		</script>
		<?php
	}
}
