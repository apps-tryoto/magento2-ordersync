<?php

/**
 * Oto OrderSync
 *
 * Synchronizes orders to OTO platform.
 *
 * Copyright (C) 2024 Oto <info@tryoto.com>
 *
 * @package Oto_OrderSync
 * @copyright Copyright (c) 2024 Oto (http://www.tryoto.com/)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author Oto <info@tryoto.com>
 */

namespace Oto\OrderSync\Model\Config\Source;

use Magento\Payment\Model\Config\Source\Allmethods;

/**
 * Class Payment
 */
class PaymentMethods extends Allmethods
{
    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $options = parent::toOptionArray();

        array_unshift($options, ['value' => '', 'label' => ' ']);

        foreach ($options as $key => $option) {
            if (!isset($options[$key]['value'])) {
                $options[$key]['value'] = null;
            }
        }

        return $options;
    }
}
