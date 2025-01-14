<?php
/**
 * @package      Crowdfunding
 * @subpackage   Transactions
 * @author       Todor Iliev
 * @copyright    Copyright (C) 2015 Todor Iliev <todor@itprism.com>. All rights reserved.
 * @license      GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace Crowdfunding;

use Imagine\Image\Palette\RGB;
use Prism;
use Joomla\Registry\Registry;

defined('JPATH_PLATFORM') or die;

/**
 * This class provides functionality that manage transactions.
 *
 * @package      Crowdfunding
 * @subpackage   Transactions
 */
class Transaction extends Prism\Database\Table
{
    protected $id;
    protected $txn_date;
    protected $txn_amount;
    protected $txn_currency;
    protected $txn_status;
    protected $txn_id;
    protected $parent_txn_id;
    protected $extra_data;
    protected $status_reason;
    protected $project_id;
    protected $reward_id;
    protected $investor_id;
    protected $receiver_id;
    protected $service_provider;
    protected $service_alias;
    protected $service_data;
    protected $reward_state;
    protected $fee;

    protected $allowedStatuses = array('pending', 'completed', 'canceled', 'refunded', 'failed');

    /**
     * Set the database object.
     *
     * <code>
     * $transaction    = new Crowdfunding\Transaction();
     * $transaction->setDb(\JFactory::getDbo());
     * </code>
     *
     * @param \JDatabaseDriver $db
     *
     * @return self
     */
    public function setDb(\JDatabaseDriver $db)
    {
        $this->db = $db;

        return $this;
    }

    /**
     * Load transaction data from database.
     *
     * <code>
     * $txnId = 1;
     *
     * $transaction    = new Crowdfunding\Transaction();
     * $transaction->setDb(\JFactory::getDbo());
     * $transaction->load($txnId);
     * </code>
     *
     * @param int|array $keys Transaction ID or keys used to find a record.
     * @param array $options
     */
    public function load($keys, $options = array())
    {
        $query = $this->db->getQuery(true);

        $query
            ->select(
                'a.id, a.txn_date, a.txn_amount, a.txn_currency, a.txn_status, a.txn_id, a.parent_txn_id, ' .
                'a.extra_data, a.status_reason, a.project_id, a.reward_id, a.investor_id, a.receiver_id, ' .
                'a.service_provider, a.service_alias, a.reward_state, a.fee'
            )
            ->from($this->db->quoteName('#__crowdf_transactions', 'a'));

        if ($keys !== null and is_array($keys)) {
            foreach ($keys as $key => $value) {
                $query->where($this->db->quoteName('a.'.$key) . '=' . $this->db->quote($value));
            }
        } else {
            $query->where('a.id = ' . (int)$keys);
        }

        $this->db->setQuery($query);
        $result = (array)$this->db->loadAssoc();

        $this->bind($result);
    }

    /**
     * Set data to object properties.
     *
     * <code>
     * $data = array(
     *  "txn_amount" => "10.00",
     *  "txn_currency" => "GBP"
     * );
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->bind($data);
     * </code>
     *
     * @param array $data
     * @param array $ignored
     */
    public function bind($data, $ignored = array())
    {
        // Encode extra data to JSON format.
        foreach ($data as $key => $value) {

            if (!in_array($key, $ignored, true)) {

                $this->$key = $value;

                // If it is extra data ( array or object ), encode the data to JSON string.
                if ((strcmp('extra_data', $key) === 0) and (is_array($value) or is_object($value))) {
                    $this->$key = json_encode($value);
                }
            }
        }
    }

    /**
     * Store data to database.
     *
     * <code>
     * $data = array(
     *  "txn_amount" => "10.00",
     *  "txn_currency" => "GBP"
     * );
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->bind($data);
     * $transaction->store();
     * </code>
     */
    public function store()
    {
        if (!$this->id) { // Insert
            $this->insertObject();
        } else { // Update
            $this->updateObject();
        }
    }

    protected function updateObject()
    {
        // Prepare extra data value.
        $extraData = (!$this->extra_data) ? 'NULL' : $this->db->quote($this->extra_data);

        $query = $this->db->getQuery(true);

        $query
            ->update($this->db->quoteName('#__crowdf_transactions'))
            ->set($this->db->quoteName('txn_date') . '=' . $this->db->quote($this->txn_date))
            ->set($this->db->quoteName('txn_amount') . '=' . $this->db->quote($this->txn_amount))
            ->set($this->db->quoteName('txn_currency') . '=' . $this->db->quote($this->txn_currency))
            ->set($this->db->quoteName('txn_status') . '=' . $this->db->quote($this->txn_status))
            ->set($this->db->quoteName('txn_id') . '=' . $this->db->quote($this->txn_id))
            ->set($this->db->quoteName('parent_txn_id') . '=' . $this->db->quote($this->parent_txn_id))
            ->set($this->db->quoteName('extra_data') . '=' . $extraData)
            ->set($this->db->quoteName('status_reason') . '=' . $this->db->quote($this->status_reason))
            ->set($this->db->quoteName('project_id') . '=' . $this->db->quote($this->project_id))
            ->set($this->db->quoteName('reward_id') . '=' . $this->db->quote($this->reward_id))
            ->set($this->db->quoteName('investor_id') . '=' . $this->db->quote($this->investor_id))
            ->set($this->db->quoteName('receiver_id') . '=' . $this->db->quote($this->receiver_id))
            ->set($this->db->quoteName('service_provider') . '=' . $this->db->quote($this->service_provider))
            ->set($this->db->quoteName('service_alias') . '=' . $this->db->quote($this->service_alias))
            ->set($this->db->quoteName('reward_state') . '=' . $this->db->quote($this->reward_state))
            ->set($this->db->quoteName('fee') . '=' . $this->db->quote($this->fee))
            ->where($this->db->quoteName('id') .'='. (int)$this->id);

        $this->db->setQuery($query);
        $this->db->execute();
    }

    protected function insertObject()
    {
        // Prepare extra data value.
        $extraData = (!$this->extra_data) ? 'NULL' : $this->db->quote($this->extra_data);
        $txnDate   = (!$this->txn_date) ? 'NULL' : $this->db->quote($this->txn_date);

        $query = $this->db->getQuery(true);

        $query
            ->insert($this->db->quoteName('#__crowdf_transactions'))
            ->set($this->db->quoteName('txn_date') . '=' . $txnDate)
            ->set($this->db->quoteName('txn_amount') . '=' . $this->db->quote($this->txn_amount))
            ->set($this->db->quoteName('txn_currency') . '=' . $this->db->quote($this->txn_currency))
            ->set($this->db->quoteName('txn_status') . '=' . $this->db->quote($this->txn_status))
            ->set($this->db->quoteName('txn_id') . '=' . $this->db->quote($this->txn_id))
            ->set($this->db->quoteName('parent_txn_id') . '=' . $this->db->quote($this->parent_txn_id))
            ->set($this->db->quoteName('extra_data') . '=' . $extraData)
            ->set($this->db->quoteName('status_reason') . '=' . $this->db->quote($this->status_reason))
            ->set($this->db->quoteName('project_id') . '=' . $this->db->quote($this->project_id))
            ->set($this->db->quoteName('reward_id') . '=' . $this->db->quote($this->reward_id))
            ->set($this->db->quoteName('investor_id') . '=' . $this->db->quote($this->investor_id))
            ->set($this->db->quoteName('receiver_id') . '=' . $this->db->quote($this->receiver_id))
            ->set($this->db->quoteName('service_provider') . '=' . $this->db->quote($this->service_provider))
            ->set($this->db->quoteName('service_alias') . '=' . $this->db->quote($this->service_alias))
            ->set($this->db->quoteName('reward_state') . '=' . $this->db->quote($this->reward_state))
            ->set($this->db->quoteName('fee') . '=' . $this->db->quote($this->fee));

        $this->db->setQuery($query);
        $this->db->execute();

        $this->id = $this->db->insertid();
    }

    /**
     * Return transaction ID.
     *
     * <code>
     * $transactionId  = 1;
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * if (!$transaction->getId()) {
     * ....
     * }
     * </code>
     *
     * @return int
     */
    public function getId()
    {
        return (int)$this->id;
    }

    /**
     * Check if transaction is completed.
     *
     * <code>
     * $transactionId  = 1;
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * if (!$transaction->isCompleted()) {
     * ....
     * }
     * </code>
     *
     * @return bool
     */
    public function isCompleted()
    {
        return (bool)(strcmp('completed', $this->txn_status) === 0);
    }

    /**
     * Check if transaction is pending.
     *
     * <code>
     * $transactionId  = 1;
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * if (!$transaction->isPending()) {
     * ....
     * }
     * </code>
     *
     * @return bool
     */
    public function isPending()
    {
        return (bool)(strcmp('pending', $this->txn_status) === 0);
    }

    /**
     * Return transaction status.
     *
     * <code>
     * $transactionId  = 1;
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * $status = $transaction->getStatus();
     * </code>
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->txn_status;
    }

    /**
     * Set a transaction status.
     *
     * <code>
     * $transactionId  = 1;
     * $status  = "completed";
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * $transaction->setStatus($status);
     * </code>
     *
     * @param string $status A transaction status - 'pending', 'completed', 'canceled', 'refunded', 'failed'.
     *
     * @return self
     */
    public function setStatus($status)
    {
        if (in_array($status, $this->allowedStatuses, true)) {
            $this->txn_status = $status;
        }

        return $this;
    }

    /**
     * Set a status reason.
     *
     * <code>
     * $transactionId  = 1;
     * $reason  = "preapproval";
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * $transaction->setStatusReason($reason);
     * </code>
     *
     * @param string $reason
     *
     * @return self
     */
    public function setStatusReason($reason)
    {
        $this->status_reason = (string)$reason;

        return $this;
    }

    /**
     * Return transaction amount.
     *
     * <code>
     * $transactionId  = 1;
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * $amount = $transaction->getAmount();
     * </code>
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->txn_amount;
    }

    /**
     * Return currency code of transaction.
     *
     * <code>
     * $transactionId  = 1;
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * $string = $transaction->getCurrency();
     * </code>
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->txn_currency;
    }

    /**
     * Return transaction ID that comes from payment gataway.
     *
     * <code>
     * $transactionId  = 1;
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * $txnId = $transaction->getTransactionId();
     * </code>
     *
     * @return mixed
     */
    public function getTransactionId()
    {
        return $this->txn_id;
    }

    /**
     * Return ID of user who send an amount.
     *
     * <code>
     * $transactionId  = 1;
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * $investorId = $transaction->getInvestorId();
     * </code>
     *
     * @return int
     */
    public function getInvestorId()
    {
        return (int)$this->investor_id;
    }

    /**
     * Return ID of user who receive the amount.
     *
     * <code>
     * $transactionId  = 1;
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * $receiverId = $transaction->getReceiverId();
     * </code>
     *
     * @return int
     */
    public function getReceiverId()
    {
        return (int)$this->receiver_id;
    }

    /**
     * Return project ID.
     *
     * <code>
     * $transactionId  = 1;
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * $projectId = $transaction->getProjectId();
     * </code>
     *
     * @return int
     */
    public function getProjectId()
    {
        return (int)$this->project_id;
    }

    /**
     * Return reward ID.
     *
     * <code>
     * $transactionId  = 1;
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * $rewardId = $transaction->getRewardId();
     * </code>
     *
     * @return int
     */
    public function getRewardId()
    {
        return (int)$this->reward_id;
    }

    /**
     * Return a fee that has been receiver from the site owner.
     *
     * <code>
     * $transactionId  = 1;
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * $fee = $transaction->getFee();
     * </code>
     *
     * @return float
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * Set a fee that has been receiver from the site owner.
     *
     * <code>
     * $transactionId  = 1;
     * $fee  = 4.5;
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * $transaction->setFee($fee);
     * $transaction->store();
     * </code>
     *
     * @param float $fee
     * @return self
     */
    public function setFee($fee)
    {
        $this->fee = $fee;

        return $this;
    }

    /**
     * Set the state of reward - sent or not sent.
     *
     * <code>
     * $transactionId  = 1;
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * $transaction->setRewardState(Prism\Constants::NOT_SENT);
     * $transaction->store();
     * </code>
     *
     * @param int $state
     * @return self
     */
    public function setRewardState($state)
    {
        $this->reward_state = $state;

        return $this;
    }

    /**
     * Set transaction ID.
     *
     * <code>
     * $transactionId  = 1;
     * $txnId  = "txn_asdf1234";
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * $transaction->setTransactionId($txnId);
     * $transaction->store();
     * </code>
     *
     * @param string $id
     * @return self
     */
    public function setTransactionId($id)
    {
        $this->txn_id = $id;

        return $this;
    }

    /**
     * Set parent transaction ID. This is ID that has been used before capture of transaction.
     *
     * <code>
     * $transactionId  = 1;
     * $txnId  = "txn_asdf1234";
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * $transaction->setParentId($transaction->getTransactionId());
     *
     * $transaction->setTransactionId($txnId);
     * $transaction->store();
     * </code>
     *
     * @param string $id
     * @return self
     */
    public function setParentId($id)
    {
        $this->parent_txn_id = $id;

        return $this;
    }

    /**
     * Return extra data about transaction that comes from payment gateway.
     *
     * <code>
     * $transactionId  = 1;
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * $extraData = $transaction->getExtraData();
     * </code>
     *
     * @return array
     */
    public function getExtraData()
    {
        $extraData = array();

        if (is_string($this->extra_data)) {
            $extraData = json_decode($this->extra_data, true);
        }

        if ($extraData === null or !is_array($extraData)) {
            $extraData = array();
        }

        return $extraData;
    }

    /**
     * Include some extra data to existing one.
     *
     * <code>
     * $date = new JDate();
     * $trackingKey = $date->toUnix();
     *
     * $extraData = array(
     *    $trackingKey => array(
     *        "Acknowledgement Status" => "....",
     *        "Timestamp" => "....",
     *        "Correlation ID" => "....",
     *        "NOTE" => "...."
     *     )
     * );
     *
     * $transactionId  = 1;
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * $extraData = $transaction->addExtraData($extraData);
     * </code>
     *
     * @param array $data
     *
     * @return array
     */
    public function addExtraData($data)
    {
        if (is_array($data)) {
            $extraData = $this->getExtraData();

            foreach ($data as $key => $value) {
                $extraData[$key] = $value;
            }

            $this->extra_data = json_encode($extraData);
        }
    }

    /**
     * Update an extra data record in the database.
     *
     * <code>
     * $date = new JDate();
     * $trackingKey = $date->toUnix();
     *
     * $extraData = array(
     *    $trackingKey => array(
     *        "Acknowledgement Status" => "....",
     *        "Timestamp" => "....",
     *        "Correlation ID" => "....",
     *        "NOTE" => "...."
     *     )
     * );
     *
     * $transactionId  = 1;
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * $transaction->addExtraData($extraData);
     * $transaction->updateExtraData();
     * </code>
     */
    public function updateExtraData()
    {
        // Prepare extra data value.
        $extraData = (!$this->extra_data) ? 'NULL' : $this->db->quote($this->extra_data);

        $query = $this->db->getQuery(true);

        $query
            ->update($this->db->quoteName('#__crowdf_transactions'))
            ->set($this->db->quoteName('extra_data') . ' = ' . $extraData)
            ->where($this->db->quoteName('id') . ' = ' . (int)$this->id);

        $this->db->setQuery($query);
        $this->db->execute();
    }

    /**
     * Update reward state to SENT or NOT SENT.
     *
     * <code>
     * $keys = array(
     *  "id" = 1,
     *  "receiver_id" => 2
     * );
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($keys);
     *
     * // 0 = NOT SENT, 1 = SENT
     * $transaction->updateRewardState(Crowdfunding\Constants::SENT);
     * </code>
     *
     * @param integer $state
     */
    public function updateRewardState($state)
    {
        $state = (!$state) ? 0 : 1;

        $query = $this->db->getQuery(true);

        $query
            ->update($this->db->quoteName('#__crowdf_transactions'))
            ->set($this->db->quoteName('reward_state') . ' = ' . (int)$state)
            ->where($this->db->quoteName('id') . ' = ' . (int)$this->id)
            ->where($this->db->quoteName('receiver_id') . ' = ' . (int)$this->receiver_id);

        $this->db->setQuery($query);
        $this->db->execute();
    }

    /**
     * Update a transaction status in the database record.
     *
     * <code>
     * $transactionId  = 1;
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * $transaction->setStatus("completed");
     * $transaction->updateStatus();
     * </code>
     */
    public function updateStatus()
    {
        $query = $this->db->getQuery(true);

        $query
            ->update($this->db->quoteName('#__crowdf_transactions'))
            ->set($this->db->quoteName('txn_status') . ' = ' . $this->db->quote($this->txn_status))
            ->where($this->db->quoteName('id') . ' = ' . (int)$this->id);

        $this->db->setQuery($query);
        $this->db->execute();
    }

    /**
     * Return a data that will be used for processing transaction on the payment service.
     *
     * <code>
     * $transactionId  = 1;
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * $data = $transaction->getServiceData();
     * </code>
     *
     * @param string $secret
     *
     * @return Registry
     */
    public function getServiceData($secret)
    {
        if (!$secret) {
            throw new \InvalidArgumentException(\JText::_('LIB_CROWDFUNDING_NO_SECRET_KEY'));
        }

        if (!$this->id) {
            throw new \InvalidArgumentException(\JText::_('LIB_CROWDFUNDING_INVALID_TRANSACTION_ID'));
        }

        if ($this->service_data === null) {
            $query = $this->db->getQuery(true);

            $query
                ->select('AES_DECRYPT(a.service_data, ' . $this->db->quote($secret) . ')')
                ->from($this->db->quoteName('#__crowdf_transactions', 'a'))
                ->where($this->db->quoteName('id') . ' = ' . (int)$this->id);

            $this->db->setQuery($query);
            $result = $this->db->loadResult();

            if ($result !== null and is_string($result) and \JString::strlen($result) > 0) {
                $this->service_data = new Registry($result);
            } else {
                $this->service_data = new Registry;
            }
        }

        return $this->service_data;
    }

    /**
     * Store specific data that will be used for processing transaction by the payment service.
     *
     * <code>
     * $transactionId  = 1;
     * $data = new Registry;
     *
     * $data->set('customer_id', '12345');
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * $transaction->setServiceData($data);
     * </code>
     *
     * @param Registry $serviceData
     *
     * @return self
     */
    public function setServiceData(Registry $serviceData)
    {
        $this->service_data = $serviceData;

        return $this;
    }

    /**
     * Store specific data that will be used for processing transaction by the payment service.
     *
     * <code>
     * $transactionId  = 1;
     * $data = new Registry;
     *
     * $data->set('customer_id', '12345');
     *
     * $transaction    = new Crowdfunding\Transaction(\JFactory::getDbo());
     * $transaction->load($transactionId);
     *
     * $transaction->setServiceData($data);
     *
     * $transaction->storeServiceData('secret_phrase');
     * </code>
     *
     * @param string $secret
     *
     * @return self
     */
    public function storeServiceData($secret)
    {
        if (!$secret) {
            throw new \InvalidArgumentException(\JText::_('LIB_CROWDFUNDING_NO_SECRET_KEY'));
        }

        if (!$this->id) {
            throw new \InvalidArgumentException(\JText::_('LIB_CROWDFUNDING_INVALID_TRANSACTION_ID'));
        }

        // Prepare data value.
        $data = 'NULL';
        if (($this->service_data instanceof Registry) and ($this->service_data->count() > 0)) {
            $data = ' AES_ENCRYPT('.$this->db->quote($this->service_data->toString()) .', '. $this->db->quote($secret) . ')';
        }

        $query = $this->db->getQuery(true);

        $query
            ->update($this->db->quoteName('#__crowdf_transactions', 'a'))
            ->set($this->db->quoteName('service_data') . '=' . $data)
            ->where($this->db->quoteName('id') . ' = ' . (int)$this->id);

        $this->db->setQuery($query);
        $this->db->execute();

        return $this;
    }
}
