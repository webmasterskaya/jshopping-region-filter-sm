<?php
/**
 * @package    Jshopping - Region filter SM
 * @version    1.1.2
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2020 Webmasterskaya. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * @link       https://webmasterskaya.xyz/
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/shippingmethodoriginal.php';

class jshopShippingMethod extends jshopShippingMethodOriginal
{
	function getAllShippingMethodsCountry($country_id, $payment_id, $publish = 1)
	{
		$db          = JFactory::getDBO();
		$lang        = JSFactory::getLang();
		$jshopConfig = JSFactory::getConfig();
		$state_name  = $this->getAnyStates();

		$query = $db->getQuery(true);

		$select = array('sh_method.' . $lang->get("name"), $lang->get("description"));
		$as     = array('name', 'description');

		$query->select(array_merge(['*'], $db->quoteName($select, $as)))
			->from($db->quoteName('#__jshopping_shipping_method', 'sh_method'))
			->join('INNER', $db->quoteName('#__jshopping_shipping_method_price',
					'sh_pr_method') . ' ON ' . $db->quoteName('sh_method.shipping_id') . ' = ' . $db->quoteName('sh_pr_method.shipping_method_id'))
			->join('INNER', $db->quoteName('#__jshopping_shipping_method_price_countries',
					'sh_pr_method_country') . ' ON ' . $db->quoteName('sh_pr_method_country.sh_pr_method_id') . ' = ' . $db->quoteName('sh_pr_method.sh_pr_method_id'))
			->join('INNER', $db->quoteName('#__jshopping_countries',
					'countries') . ' ON ' . $db->quoteName('sh_pr_method_country.country_id') . ' = ' . $db->quoteName('countries.country_id'))
			->where($db->quoteName('countries.country_id') . ' = ' . $db->quote($country_id))
			->order($db->quoteName('sh_method.ordering'));

		if ($publish)
		{
			$query->where($db->quoteName('sh_method.published') . ' = 1');
		}

		if ($payment_id && $jshopConfig->step_4_3 == 0)
		{
			$query->where('(' . $db->quoteName('sh_method.payments') . ' = \'\'' . ' OR ' .
				'FIND_IN_SET(' . $db->quote($payment_id) . ', ' . $db->quoteName('sh_method.payments') . ') )');
		}

		if ($state_name)
		{
			$query->join('INNER', $db->quoteName('#__jshopping_shipping_method_price_states',
					'sh_pr_method_state') . ' ON ' . $db->quoteName('sh_pr_method_state.sh_pr_method_id') . '=' . $db->quoteName('sh_pr_method.sh_pr_method_id'))
				->join('INNER', $db->quoteName('#__jshopping_states',
						'states') . ' ON ' . $db->quoteName('sh_pr_method_state.state_id') . '=' . $db->quoteName('states.state_id') . ' AND ' . $db->quoteName('countries.country_id') . ' = ' . $db->quoteName('states.country_id'));
			$query->where($db->quoteName('states.' . $lang->get('name')) . ' = ' . $db->quote($state_name));
		}

		extract(js_add_trigger(get_defined_vars(), "query"));

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	function getAnyStates()
	{
		$adv_user = JSFactory::getUser();
		if ($adv_user->delivery_adress)
		{
			$state = $adv_user->d_state;
		}
		else
		{
			$state = $adv_user->state;
		}

		return trim($state);
	}
}