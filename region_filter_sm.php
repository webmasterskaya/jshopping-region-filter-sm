<?php
/**
 * @package    Jshopping - Region filter SM
 * @version    1.1.2
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2020 Webmasterskaya. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * @link       https://webmasterskaya.xyz/
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
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

		$view->lists['countries'] .= PHP_EOL . '<button role="button" class="btn btn-small" data-select="country" data-mode="1" onclick="changeOptions(this); return false;"><span class="icon-save" aria-hidden="true"></span>' . Text::_('PLG_JSHOPPING_REGION_FILTER_SM_BTN_COUNTRIES_SELECT') . '</button>';
		$view->lists['countries'] .= PHP_EOL . '<button role="button" class="btn btn-small" data-select="country" data-mode="0" onclick="changeOptions(this); return false;"><span class="icon-cancel" aria-hidden="true"></span>' . Text::_('PLG_JSHOPPING_REGION_FILTER_SM_BTN_COUNTRIES_UNSELECT') . '</button>';

		if (!empty($output = $this->prepareOutput($countries, $sh_pr_method_id)))
		{
			// Separate country and state
			$view->lists['countries'] .= PHP_EOL . '</td></tr><tr><td class="key">Область</td><td>';

			$view->lists['countries'] .= PHP_EOL . $output;
			$view->lists['countries'] .= PHP_EOL . '<button role="button" class="btn btn-small" data-select="states" data-mode="1" onclick="changeOptions(this); return false;"><span class="icon-save" aria-hidden="true"></span>' . Text::_('PLG_JSHOPPING_REGION_FILTER_SM_BTN_STATES_SELECT') . '</button>';
			$view->lists['countries'] .= PHP_EOL . '<button role="button" class="btn btn-small" data-select="states" data-mode="0" onclick="changeOptions(this); return false;"><span class="icon-cancel" aria-hidden="true"></span>' . Text::_('PLG_JSHOPPING_REGION_FILTER_SM_BTN_STATES_UNSELECT') . '</button>';
		}
	}

	/**
	 * @param $countries
	 * @param $method
	 *
	 * @return mixed|string
	 *
	 *
	 * @since 1.0.0
	 */
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
				$output = '<input type="text" id="shipping_states_id" name="shipping_states_id" value="" placeholder="' . Text::_('PLG_JSHOPPING_REGION_FILTER_SM_STATES_NOT_FOUND') . '" disabled />';
			}
		}
		else
		{
			$output = '<input type="text" id="shipping_states_id" name="shipping_states_id" value="" placeholder="' . Text::_('PLG_JSHOPPING_REGION_FILTER_SM_COUNTRIES_NEED_SELECT') . '" disabled />';
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

	/**
	 * @return false|string
	 *
	 *
	 * @since 1.0.0
	 */
	public function onAjaxRegion_filter_sm()
	{
		$input     = $this->app->input->getArray();
		$method    = intval($input['method']);
		$countries = json_decode($input['countries']);
		ob_start();
		echo $this->prepareOutput($countries, $method);

		return ob_get_clean();
	}

	/**
	 * @param $shipping_pr
	 *
	 *
	 * @since 1.0.0
	 */
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

	/**
	 * @param          $method
	 * @param   array  $states
	 *
	 *
	 * @since 1.0.0
	 */
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

	/**
	 * @param $cid
	 *
	 *
	 * @since 1.0.0
	 */
	public function onAfterRemoveShippingPrice(&$cid)
	{
		if (!empty($cid))
		{
			$this->saveMethodStates($cid);
		}
	}

	public function onJSFactoryGetTable(&$type, &$prefix, &$config)
	{
		if ($type == 'shippingMethod')
		{
			JTable::addIncludePath(JPATH_PLUGINS . DIRECTORY_SEPARATOR . $this->_type . DIRECTORY_SEPARATOR . $this->_name . DIRECTORY_SEPARATOR . 'tables');
		}
	}

	/**
	 * @param $view
	 *
	 * @since 1.0.0
	 */
	public function onBeforeDisplayShippngsPrices(&$view)
	{
		foreach ($view->rows as $key => $row)
		{
			if (!empty($row->countries))
			{
				$row->countries = '<div><b>' . Text::_('PLG_JSHOPPING_REGION_FILTER_SM_LIST_COUNTRIES') . ':</b> ' . $row->countries . '</div>';
			}

			$selectedStates = $this->getSelectedStates($row->sh_pr_method_id);
			if (!empty($selectedStates))
			{
				if (count($selectedStates) > 10)
				{
					$states = implode(',', array_slice(array_column($selectedStates, 'name'), 0, 10)) . '...';
				}
				else
				{
					$states = implode(',', array_column($selectedStates, 'name'));
				}
				$row->countries .= '<div><b>' . Text::_('PLG_JSHOPPING_REGION_FILTER_SM_LIST_STATES') . ':</b> ' . $states . '</div>';
			}

			$view->rows[$key] = $row;
		}
	}

	/**
	 * @param $method
	 * @param $country
	 *
	 * @return array|mixed
	 *
	 *
	 * @since 1.0.0
	 */
	protected function getSelectedStates($method, $country = 0)
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
			->where($db->quoteName('sm.sh_pr_method_id') . ' = ' . intval($method));
		if (!empty($country))
		{
			$query->where($db->quoteName('s.country_id') . ' = ' . $country);
		}
		$db->setQuery($query);
		$states = $db->loadObjectList('id');

		return $states;
	}

	/**
	 * @param $config
	 *
	 * @since 1.1.0
	 */
	public function onBeforeLoadJshopConfig($config)
	{
		$this->overrideClass('jshopShippingMethod');
	}

	/**
	 * @param   string|null  $class
	 *
	 * @since 1.1.0
	 */
	protected function overrideClass($class = null)
	{
		$classes = array(
			'jshopShippingMethod' => array(
				'original' => JPATH_ROOT . '/components/com_jshopping/tables/shippingmethod.php',
				'override' => __DIR__ . '/tables/shippingmethodoriginal.php',
			),
		);

		if (!empty($classes[$class]) && !class_exists($class))
		{
			$originalClass = $class . 'Original';
			if (!class_exists($originalClass))
			{
				$original = JPath::clean($classes[$class]['original']);
				$override = JPath::clean($classes[$class]['override']);

				if (!file_exists($override))
				{
					file_put_contents($override, '');
				}

				$context = file_get_contents($original);
				$context = str_replace('class ' . $class, 'class ' . $originalClass, $context);
				if (file_get_contents($override) !== $context)
				{
					file_put_contents($override, $context);
				}
			}
		}
	}

	/**
	 *
	 *
	 * @since 1.1.0
	 */
	public function onAfterLoadShopParamsAdmin()
	{
		$this->overrideClass('jshopShippingMethod');
	}
}