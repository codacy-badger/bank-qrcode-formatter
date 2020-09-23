<?php

namespace MarcinOrlowski\QrcodeFormatter;

/**
 * Bank QrCode Formatter
 *
 * @package   MarcinOrlowski\QrcodeFormatter
 *
 * @author    Marcin Orlowski <mail (#) marcinOrlowski (.) com>
 * @copyright 2020 Marcin Orlowski
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      https://github.com/MarcinOrlowski/bank-qrcode-formatter
 */

use InvalidArgumentException;
use OutOfRangeException;
use RuntimeException;

class Builder
{
	/** @var int */
	const TYPE_COMPANY = 1;

	/** @var int */
	const TYPE_PERSON = 2;

	/** @var string */
	protected $separator = '|';

	/** @var int Maks. allowed length of result string */
	protected $max_length = 160;


	/**
	 * Builder constructor.
	 *
	 * @param int $recipient_type
	 */
	public function __construct($recipient_type = self::TYPE_PERSON)
	{
		if ($recipient_type !== self::TYPE_COMPANY && $recipient_type !== self::TYPE_PERSON) {
			throw new RuntimeException('Invalid recipient type specified.');
		}

		$this->recipient_type = $recipient_type;
	}

	/** @var int */
	protected $recipient_type = self::TYPE_PERSON;

	/** @var string VAT ID (10 chars). required for TYPE_COMPANY (VAT ID), optional otherwise, digits */
	protected $vat_id = '';

	/**
	 * Sets recipient Vat ID
	 *
	 * @param string|int|null $vat_id
	 *
	 * @return $this
	 */
	public function vatId($vat_id)
	{
		$this->vat_id = $this->validateVatId($vat_id);

		return $this;
	}

	/**
	 * @param string|int|null $vat_id
	 *
	 * @return string
	 *
	 * @throws \InvalidArgumentException
	 * @throws \RuntimeException
	 */
	protected function validateVatId($vat_id)
	{
		if (is_string($vat_id)) {
			$vat_id = trim(str_replace('-', '', $vat_id));
		} elseif ($vat_id === null) {
			$vat_id = '';
		} elseif (is_int($vat_id)) {
			$vat_id = sprintf('%010d', $vat_id);
		} else {
			throw new InvalidArgumentException('VatId can either be a string, int or null.');
		}

		if ($this->recipient_type === self::TYPE_COMPANY && $vat_id === '') {
			throw new RuntimeException('Company recipient must have VAT ID set.');
		}

		if ($vat_id !== '') {
			if (preg_match('/^\d{10}$/', $vat_id) !== 1) {
				throw new InvalidArgumentException("Invalid VAT ID set. Must be contain 10 chars, digits only. '{$vat_id}' provided.");
			}
		}

		return $vat_id;
	}

	/** @var string Recipient bank account number (26 digits), mandatory */
	protected $bank_account = '';

	/**
	 * Sets mandatory recipient routing bank account number. Account number must contain 26 digits.
	 * Digits can be grouped and separated by spaces however all spaces will be removed.
	 *
	 * @param string $account Recipient bank account number (26 digits)
	 *
	 * @return $this
	 */
	public function bankAccount($account)
	{
		$this->bank_account = $this->validateBankAccount($account);

		return $this;
	}

	/**
	 * @param string $account
	 *
	 * @return string
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function validateBankAccount($account)
	{
		if (!is_string($account)) {
			throw new InvalidArgumentException('Bank account number must be a string.');
		}
		$account = str_replace(' ', '', $account);

		if (preg_match('/^\d{26}$/', $account) !== 1) {
			throw new InvalidArgumentException("Bank account number must be 26 chars long, digits only. '{$account}' provided.");
		}

		return $account;
	}

	/** @var string 20 chars max, recipient name, mandatory */
	protected $recipient_name = '';

	/**
	 * Sets recipient name. Up to 20 chars (longer strings are allowed and will be trimmed).
	 *
	 * @param string $name recipient name
	 *
	 * @return $this
	 */
	public function name($name)
	{
		$this->recipient_name = $this->validateName($name);

		return $this;
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 *
	 * @throws \InvalidArgumentException
	 * @throws \RuntimeException
	 */
	protected function validateName($name)
	{
		if (!is_string($name)) {
			throw new InvalidArgumentException('Recipient name must be a string.');
		}

		$name = mb_substr($name, 0, 20);

		if ($name === '') {
			throw new RuntimeException('Recipient name cannot be empty.');
		}

		return $name;
	}

	/** @var string 2 chars, country code (i.e. 'PL'), optional, letters */
	protected $country_code = '';

	/**
	 * @param string|null $country_code 2 chars, country code (i.e. 'PL'), optional, letters
	 *
	 * @return $this
	 *
	 * @throws \InvalidArgumentException
	 */
	public function country($country_code)
	{
		if ($country_code === null) {
			$country_code = '';
		}

		if (!is_string($country_code)) {
			throw new InvalidArgumentException('Country code must be a string.');
		}

		$country_code = mb_strtoupper($country_code);
		if ($country_code !== '') {
			if (preg_match('/^[A-Z]{2}$/', $country_code) !== 1) {
				throw new InvalidArgumentException("Country code must be a 2 character long, letters only. '{$country_code}' provided.");
			}
		}

		$this->country_code = strtoupper($country_code);

		return $this;
	}

	/** @var string */
	protected $payment_title = '';

	/**
	 * @param string $title 32 chars, payment title, mandatory, letters+digits
	 *
	 * @return $this
	 */
	public function title($title)
	{
		$this->payment_title = $this->validateTitle($title);

		return $this;
	}

	/**
	 * @param string $title
	 *
	 * @return string
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function validateTitle($title)
	{
		if (!is_string($title)) {
			throw new InvalidArgumentException('Payment title must be a string.');
		}

		$title = mb_substr(trim($title), 0, 32);

		if ($title === '') {
			throw new RuntimeException('Payment title cannot be empty.');
		}

		return $title;
	}

	/** @var int|null */
	protected $amount = null;

	/**
	 * @param float|int $amount 6 chars, amount in Polish grosz, digits, mandatory
	 *
	 * @return $this
	 */
	public function amount($amount)
	{
		$this->amount = $this->validateAmount($amount);

		return $this;
	}

	/**
	 * @param float|int $amount
	 *
	 * @return int
	 *
	 * @throws \OutOfRangeException
	 * @throws \InvalidArgumentException
	 * @throws \RuntimeException
	 */
	protected function validateAmount($amount)
	{
		if ($amount === null) {
			throw new RuntimeException('Amount not specified.');
		}

		if (is_float($amount)) {
			$amount = (int)($amount * 100);
		} elseif (!is_int($amount)) {
			throw new InvalidArgumentException('Amount must be either float or int');
		}

		if ($amount < 0) {
			throw new OutOfRangeException('Amount cannot be negative.');
		}

		if ($amount > 999999) {
			throw new OutOfRangeException('Amount representation cannot exceed 6 digits. Current value: {$amount}');
		}

		return $amount;
	}

	/** @var string */
	protected $reserved1 = '';

	/**
	 * @param string $id 20 chars, reserved i.e. for payment reference id, optional, digits (but we use letters+digits as some banks do too)
	 *
	 * @return $this
	 *
	 * @throws \InvalidArgumentException
	 */
	public function reserved1($id)
	{
		if (!is_string($id)) {
			throw new InvalidArgumentException('Reserved1/RefId value must be a string.');
		}

		$this->reserved1 = mb_substr((string)$id, 0, 20);

		return $this;
	}

	/**
	 * Alias for reserved1()
	 *
	 * @param string $id
	 *
	 * @return $this
	 */
	public function refId($id)
	{
		return $this->reserved1($id);
	}

	/** @var string */
	protected $reserved2 = '';

	/**
	 * 12 chars, reserved i.e. for Invobill reference id, optional, digits (but we allow letters+digits as some banks do too)
	 *
	 * @param string $id
	 *
	 * @return $this
	 *
	 * @throws \InvalidArgumentException
	 */
	public function reserved2($id)
	{
		if (!is_string($id)) {
			throw new InvalidArgumentException('Reserved2/Invobill value must be a string.');
		}

		$this->reserved2 = mb_substr((string)$id, 0, 12);

		return $this;
	}

	/**
	 * Alias for reserved2()
	 *
	 * @param string $id
	 *
	 * @return $this
	 */
	public function invobill($id)
	{
		return $this->reserved2($id);
	}

	/** @var string */
	protected $reserved3 = '';

	/**
	 * 24 chars, reserved, optional, letters+digits
	 *
	 * @param string $id
	 *
	 * @return $this
	 *
	 * @throws \InvalidArgumentException
	 */
	public function reserved3($id)
	{
		if (!is_string($id)) {
			throw new InvalidArgumentException('Reserved3 value must be a string.');
		}

		$this->reserved3 = mb_substr((string)$id, 0, 24);

		return $this;
	}

	/**
	 * @return string
	 *
	 * @throws \RuntimeException
	 */
	public function build()
	{
		// validate
		$this->validateBankAccount($this->bank_account);
		$this->validateName($this->recipient_name);
		$this->validateVatId($this->vat_id);
		$this->validateTitle($this->payment_title);
		$this->validateAmount($this->amount);

		// build
		$fields = array(
			$this->vat_id,
			$this->country_code,
			$this->bank_account,
			sprintf('%06d', $this->amount),
			$this->recipient_name,
			$this->payment_title,
			$this->reserved1,
			$this->reserved2,
			$this->reserved3,
		);

		$result = implode($this->separator, $fields);
		if (mb_strlen($result) > $this->max_length) {
			throw new RuntimeException(
				sprintf('Oops, this should not happen! Result string is %d chars long (max allowed %d). Please report this!', mb_strlen($result), $this->max_length));
		}

		return $result;
	}
}
