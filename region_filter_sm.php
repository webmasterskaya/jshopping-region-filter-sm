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
		echo "<pre>";
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
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->delete($db->quoteName('#__jshopping_shipping_method_price_states'))
				->where($db->quoteName('sh_pr_method_id') . ' = ' . intval($method));
			$db->setQuery($query)->execute();

			$query->clear();

			if (!empty($states))
			{
				$query->insert($db->quoteName('#__jshopping_shipping_method_price_states'))
					->columns($db->quoteName(['state_id', 'country_id', 'sh_pr_method_id']));
				foreach ($states as $state)
				{
					$query->values(implode(',', [$state['state'], $state['country'], $method]));
				}
				$db->setQuery($query)->execute();
			}
		}
	}
}