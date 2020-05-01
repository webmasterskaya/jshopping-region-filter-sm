<?php

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\CMSPlugin;

defined('_JEXEC') or die;

class PlgJshoppingRegion_filter_sm extends CMSPlugin
{

	/**
	 * The Application object
	 *
	 * @var    \Joomla\CMS\Application\CMSApplication
	 * @since  1.0.0
	 */
	protected $app;

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  1.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * @param $view
	 *
	 *
	 * @since 1.0.0
	 */
	public function onBeforeEditShippingsPrices(&$view)
	{
		$countries = [];

		HTMLHelper::_('script', 'plg_jshopping_region_filter_sm/script.js', ['relative' => true, 'version' => 'auto']);

		if (!empty($sh_pr_method_id = intval($view->sh_method_price->sh_pr_method_id)))
		{
			$sh_method_price = JSFactory::getTable('shippingMethodPrice', 'jshop');
			$sh_method_price->load($sh_pr_method_id);
			$sh_method_countries = $sh_method_price->getCountries();
			if (!empty($sh_method_countries))
			{
				$countries = array_column($sh_method_countries, 'country_id');
			}
		}

		if (!empty($output = $this->prepareOutput($countries, $sh_pr_method_id)))
		{
			$view->lists['countries'] .= PHP_EOL . '<button role="button" class="btn btn-small" data-select="country" data-mode="1" onclick="changeOptions(this); return false;"><span class="icon-save" aria-hidden="true"></span>Выбрать все страны</button>';
			$view->lists['countries'] .= PHP_EOL . '<button role="button" class="btn btn-small" data-select="country" data-mode="0" onclick="changeOptions(this); return false;"><span class="icon-cancel" aria-hidden="true"></span>Очистить все страны</button>';
			$view->lists['countries'] .= PHP_EOL . '</td></tr><tr><td class="key">Область</td><td>';

			$view->lists['countries'] .= PHP_EOL . $output;
			$view->lists['countries'] .= PHP_EOL . '<button role="button" class="btn btn-small" data-select="states" data-mode="1" onclick="changeOptions(this); return false;"><span class="icon-save" aria-hidden="true"></span>Выбрать все регионы</button>';
			$view->lists['countries'] .= PHP_EOL . '<button role="button" class="btn btn-small" data-select="states" data-mode="0" onclick="changeOptions(this); return false;"><span class="icon-cancel" aria-hidden="true"></span>Очистить все регионы</button>';
		}
	}

	protected function prepareOutput($countries, $method)
	{
		if (!empty($countries))
		{
			if (!empty($states = $this->getStates($countries, $method)))
			{
				$options  = [];
				$selected = [];
				foreach ($states as $state)
				{
					$options[] = HTMLHelper::_('select.option', '[' . $state->country . '][' . $state->id . ']',
						$state->name, 'state_id', 'name');
					if (!empty($state->select))
					{
						$selected[] = '[' . $state->country . '][' . $state->id . ']';
					}
				}
				$output = HTMLHelper::_('select.genericlist', $options, 'shipping_states_id[]',
					['multiple' => true, 'class' => 'inputbox', 'size' => 10], 'state_id', 'name',
					$selected);
			}
			else
			{
				$output = '<input type="text" id="shipping_states_id" name="shipping_states_id" value="" placeholder="Не удалось подобрать регион" disabled />';
			}
		}
		else
		{
			$output = '<input type="text" id="shipping_states_id" name="shipping_states_id" value="" placeholder="Сначала выберите страну" disabled />';
		}

		return $output;
	}

	/**
	 * @param $countries
	 *
	 * @return array
	 *
	 *
	 * @since 1.0.0
	 */
	protected function getStates($countries, $method)
	{
		$states = [];

		if (!empty($countries))
		{
			$db    = Factory::getDbo();
			$lang  = JSFactory::getLang();
			$query = $db->getQuery(true);
			$query->select($db->quoteName(['s.state_id', 's.' . $lang->get('name'), 's.country_id', 'sm.state_id'],
				['id', 'name', 'country', 'select']))
				->from($db->quoteName('#__jshopping_states', 's'))
				->where($db->quoteName('s.state_publish') . '= 1')
				->where($db->quoteName('s.country_id') . ' IN (' . implode(',', $countries) . ')')
				->order($db->quoteName('s.ordering'));
			if (!empty($method))
			{
				$query->join('LEFT', $db->quoteName('#__jshopping_shipping_method_price_states', 'sm') .
					' ON ' . $db->quoteName('s.state_id') . ' = ' . $db->quoteName('sm.state_id') .
					' AND ' . $db->quoteName('s.country_id') . ' = ' . $db->quoteName('sm.country_id') .
					' AND ' . $db->quoteName('sm.sh_pr_method_id') . ' = ' . intval($method));
			}
			else
			{
				$query->join('LEFT', $db->quoteName('#__jshopping_shipping_method_price_states', 'sm') .
					' ON ' . $db->quoteName('s.state_id') . ' = ' . $db->quoteName('sm.state_id') .
					' AND ' . $db->quoteName('s.country_id') . ' = ' . $db->quoteName('sm.country_id'));
			}
			$db->setQuery($query);
			$states = $db->loadObjectList('id');
		}

		return $states;
	}

	public function onAjaxRegion_filter_sm()
	{
		$input     = $this->app->input->getArray();
		$method    = intval($input['method']);
		$countries = json_decode($input['countries']);
		ob_start();
		echo $this->prepareOutput($countries, $method);

		return ob_get_clean();
	}

	public function onAfterSaveShippingPrice(&$shipping_pr)
	{
		$input  = $this->app->input->getArray();
		$states = [];
		$method = $shipping_pr->sh_pr_method_id;
		if (!empty($input['shipping_states_id']))
		{
			foreach ($input['shipping_states_id'] as $state_id)
			{
				$matches = [];
				preg_match('/\[([0-9]+)\]\[([0-9]+)\]/', $state_id, $matches);
				if (!empty($matches))
				{
					$states[] = [
						'country' => $matches[1],
						'state'   => $matches[2],
					];
				}
			}
		}
		$this->saveMethodStates($method, $states);
	}

	protected function saveMethodStates($method, $states = [])
	{

		if (!empty($method))
		{
			if (!is_array($method))
			{
				$method = [$method];
			}
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->delete($db->quoteName('#__jshopping_shipping_method_price_states'))
				->where($db->quoteName('sh_pr_method_id') . ' IN (' . implode(',', $method) . ')');
			$db->setQuery($query)->execute();

			if (!empty($states))
			{
				$query->insert($db->quoteName('#__jshopping_shipping_method_price_states'))
					->columns($db->quoteName(['state_id', 'country_id', 'sh_pr_method_id']));
				foreach ($states as $state)
				{
					$query->values(implode(',', [$state['state'], $state['country'], $method[0]]));
				}
				$db->setQuery($query)->execute();
			}
		}
	}

	public function onAfterRemoveShippingPrice(&$cid)
	{
		if (!empty($cid))
		{
			$this->saveMethodStates($cid);
		}
	}

	public function onBeforeDisplayCheckoutStep4View(&$view)
	{
		$user = Factory::getUser();
		if ($user->id)
		{
			$user_info = JSFactory::getUserShop();
		}
		else
		{
			$user_info = JSFactory::getUserShopGuest();
		}

		// Get selected state name
		if (isset($user_info->d_state) && !empty($user_info->d_state))
		{
			$state_name = $user_info->d_state;
		}
		else
		{
			if (isset($user_info->state) && !empty($user_info->state))
			{
				$state_name = $user_info->state;
			}
		}

		// Get selected country id
		if (isset($user_info->d_country) && !empty($user_info->d_country))
		{
			$country_id = $user_info->d_country;
		}
		else
		{
			if (isset($user_info->country) && !empty($user_info->country))
			{
				$country_id = $user_info->country;
			}
		}

		// Filter delivery methods if the country and region are selected
		if (!empty($state_name) && !empty($country_id))
		{
			foreach ($view->shipping_methods as $key => $shipping_method)
			{
				$selectedStates = $this->getSelectedStates($shipping_method->sh_pr_method_id, $country_id);
				if (!empty($selectedStates))
				{
					$states = array_column($selectedStates, 'name');
					if (!in_array($state_name, $states))
					{
						unset($view->shipping_methods[$key]);
					}
				}
			}
		}
	}

	protected function getSelectedStates($method, $country)
	{
		$states = [];

		if (!empty($country))
		{
			$db    = Factory::getDbo();
			$lang  = JSFactory::getLang();
			$query = $db->getQuery(true);
			$query->select($db->quoteName(['sm.state_id', 's.' . $lang->get('name'), 's.country_id'],
				['id', 'name', 'country']))
				->from($db->quoteName('#__jshopping_shipping_method_price_states', 'sm'))
				->join('LEFT', $db->quoteName('#__jshopping_states', 's') .
					' ON ' . $db->quoteName('sm.state_id') . ' = ' . $db->quoteName('s.state_id') .
					' AND ' . $db->quoteName('sm.country_id') . ' = ' . $db->quoteName('s.country_id'))
				->where($db->quoteName('s.state_publish') . '= 1')
				->where($db->quoteName('s.country_id') . ' = ' . $country)
				->where($db->quoteName('sm.sh_pr_method_id') . ' = ' . intval($method));
			$db->setQuery($query);
			$states = $db->loadObjectList('id');
		}

		return $states;
	}
}