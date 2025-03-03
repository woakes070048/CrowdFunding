<?php
/**
 * @package      Crowdfunding
 * @subpackage   Currencies
 * @author       Todor Iliev
 * @copyright    Copyright (C) 2015 Todor Iliev <todor@itprism.com>. All rights reserved.
 * @license      GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace Crowdfunding;

use Prism;
use Joomla\Utilities\ArrayHelper;

defined('JPATH_PLATFORM') or die;

/**
 * This class provides functionality that manage currencies.
 *
 * @package      Crowdfunding
 * @subpackage   Currencies
 */
class Currencies extends Prism\Database\ArrayObject
{
    /**
     * Load currencies data by ID from database.
     *
     * <code>
     * $options = array(
     *     "ids" => array(1,2,3,4,5),  // Use this option to load the currencies by IDs.
     *     "codes" => array("USD", "GBP") // Use this option to load the currencies by code.
     * );
     * $currencies   = new Crowdfunding\Currencies(\JFactory::getDbo());
     * $currencies->load($options);
     *
     * foreach($currencies as $currency) {
     *   echo $currency["title"];
     *   echo $currency["code"];
     * }
     * </code>
     *
     * @param array $options
     */
    public function load($options = array())
    {
        // Get IDs.
        $ids = (!array_key_exists('ids', $options)) ? array() : $options['ids'];
        $ids = ArrayHelper::toInteger($ids);

        // Get codes.
        $codes = (!array_key_exists('codes', $options)) ? array() : $options['codes'];

        $query = $this->db->getQuery(true);

        $query
            ->select('a.id, a.title, a.code, a.symbol, a.position')
            ->from($this->db->quoteName('#__crowdf_currencies', 'a'));

        if (count($ids) > 0) { // Load by IDs
            $query->where('a.id IN ( ' . implode(',', $ids) . ' )');
        } elseif (count($codes) > 0) { // Load by codes
            foreach ($codes as $key => $value) {
                $codes[$key] = $this->db->quote($value);
            }

            $query->where('a.code IN ( ' . implode(',', $codes) . ' )');
        }

        $this->db->setQuery($query);
        $this->items = (array)$this->db->loadAssocList();
    }

    /**
     * Create a currency object by abbreviation and return it.
     *
     * <code>
     * $options = array(
     *     "ids" => array(1,2,3,4,5),
     *     "codes" => array("USD", "GBP")
     * );
     *
     * $currencies   = new Crowdfunding\Currencies(\JFactory::getDbo());
     * $currencies->load($options);
     *
     * $currency = $currencies->getCurrencyByCode("EUR");
     * </code>
     *
     * @param string $code
     *
     * @throws \UnexpectedValueException
     *
     * @return null|Currency
     */
    public function getCurrencyByCode($code)
    {
        if (!$code) {
            throw new \UnexpectedValueException(\JText::_('LIB_CROWDFUNDING_INVALID_CURRENCY_ABBREVIATION'));
        }

        $currency = null;

        foreach ($this->items as $item) {
            if (strcmp($code, $item['code']) === 0) {
                $currency = new Currency();
                $currency->bind($item);
                break;
            }
        }

        return $currency;
    }

    /**
     * Create a currency object and return it.
     *
     * <code>
     * $options = array(
     *     "ids" => array(1,2,3,4,5),
     *     "codes" => array("USD", "GBP")
     * );
     *
     * $currencies   = new Crowdfunding\Currencies(\JFactory::getDbo());
     * $currencies->load($options);
     *
     * $currencyId = 1;
     * $currency = $currencies->getCurrency($currencyId);
     * </code>
     *
     * @param int $id
     *
     * @throws \UnexpectedValueException
     *
     * @return null|Currency
     */
    public function getCurrency($id)
    {
        $id = (int)$id;

        if (!$id) {
            throw new \UnexpectedValueException(\JText::_('LIB_CROWDFUNDING_INVALID_CURRENCY_ID'));
        }

        $currency = null;

        foreach ($this->items as $item) {
            if ($id === (int)$item['id']) {
                $currency = new Currency();
                $currency->bind($item);
                break;
            }
        }

        return $currency;
    }
}
