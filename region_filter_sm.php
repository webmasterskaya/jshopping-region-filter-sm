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
			$view->lists['countries'] .= PHP_EOL . $output;
		}
	}

	protected function prepareOutput($countries, $method)
	{
		if (!empty($countries))
		{
			if (!empty($states = $this->getStates($countries, $method)))
			{
				$options   = [];
				$selected  = [];
				$options[] = JHTML::_('select.option', '0', _JSHOP_REG_SELECT, 'state_id', 'name');
				foreach ($states as $state)
				{
					$options[] = HTMLHelper::_('select.option', $state->id, $state->name, 'state_id', 'name');
					if (!empty($state->select))
					{
						$selected[] = $state->id;
					}
				}
				$output = HTMLHelper::_('select.genericlist', $options, 'shipping_states_id[]',
					['multiple' => true, 'class' => 'inputbox', 'size' => 10], 'state_id', 'name',
					$selected);
			}
			else
			{
				$output = '<input type="text" name="shipping_states_id" value="" placeholder="Не удалось подобрать регион" disabled />';
			}
		}
		else
		{
			$output = '<input type="text" name="shipping_states_id" value="" placeholder="Сначала выберите страну" disabled />';
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

		if (!empty($countries) && !empty($method))
		{
			$db    = Factory::getDbo();
			$lang  = JSFactory::getLang();
			$query = $db->getQuery(true);
			$query->select($db->quoteName(['s.state_id', 's.' . $lang->get('name'), 'sm.state_id'],
				['id', 'name', 'select']))
				->from($db->quoteName('#__jshopping_states', 's'))
				->join('LEFT', $db->quoteName('#__jshopping_shipping_method_price_states', 'sm') .
					' ON ' . $db->quoteName('s.state_id') . ' = ' . $db->quoteName('sm.state_id') .
					' AND ' . $db->quoteName('s.country_id') . ' = ' . $db->quoteName('sm.country_id') .
					' AND ' . $db->quoteName('sm.sh_pr_method_id') . ' = ' . intval($method))
				->where($db->quoteName('s.state_publish') . '= 1')
				->where($db->quoteName('s.country_id') . ' IN (' . implode(',', $countries) . ')')
				->order($db->quoteName('s.ordering'));
			$db->setQuery($query);
			$states = $db->loadObjectList('id');
		}

		return $states;
	}

	protected function saveMethodStates($method, $states)
	{

	}

	public function onAjaxRegion_filter_sm()
	{
		$input = $this->app->input->getArray();
		ob_start();
		var_dump($input);
		return ob_get_clean();
	}
}