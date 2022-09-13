<?php
/**
 * TwigPaymentGateway
 *
 * @description Twig Payment Gateway
 * @author   Twig Team < twigpay@twig-group.com >
 * @license  MIT
 * @copyright Copyright © 2022 https://twigcard.com/
 *
 */

namespace Twigpay\TwigPaymentGateway\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;

class RefundCodeValidator extends AbstractValidator
{

    const RESULT_CODE = 'RESULT_CODE';

    /**
     * Performs validation of result code
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
        if (!isset($validationSubject['response']) || !is_array($validationSubject['response'])) {
            return $this->createResult(false, [__('Response does not exist')]);
        }
        $response = $validationSubject['response'];
        return $this->createResult(true, []);
    }

}
