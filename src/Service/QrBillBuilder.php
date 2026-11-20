<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;
use Sprain\SwissQrBill as QrBill;
use Sprain\SwissQrBill\DataGroup\AddressInterface;
use Sprain\SwissQrBill\QrCode\QrCode;
use Throwable;

/**
 * Swiss QR bill code (without reference), shared by the invoices and the coach payments
 */
class QrBillBuilder
{
    /**
     * Payment without reference: the IBAN must be a classic CH/LI IBAN (not a QR-IBAN)
     * @param \Sprain\SwissQrBill\DataGroup\AddressInterface $creditor who receives the payment
     * @param string $iban account of the creditor
     * @param float $amount CHF
     * @param \Sprain\SwissQrBill\DataGroup\AddressInterface|null $debtor who pays, optional
     * @param string $information human-readable information about the payment
     * @throws \RuntimeException when the data is not valid for a QR bill
     */
    public static function build(AddressInterface $creditor, string $iban, float $amount, ?AddressInterface $debtor, string $information): QrCode
    {
        $qrBill = QrBill\QrBill::create();
        $qrBill->setCreditor($creditor);
        $qrBill->setCreditorInformation(QrBill\DataGroup\Element\CreditorInformation::create($iban));
        if ($debtor !== null) {
            $qrBill->setUltimateDebtor($debtor);
        }
        $qrBill->setPaymentAmountInformation(QrBill\DataGroup\Element\PaymentAmountInformation::create('CHF', $amount));
        $qrBill->setPaymentReference(QrBill\DataGroup\Element\PaymentReference::create(QrBill\DataGroup\Element\PaymentReference::TYPE_NON));
        $qrBill->setAdditionalInformation(QrBill\DataGroup\Element\AdditionalInformation::create($information));

        try {
            return $qrBill->getQrCode();
        } catch (Throwable $e) {
            $messages = [];
            foreach ($qrBill->getViolations() as $violation) {
                $messages[] = $violation->getMessage();
            }
            throw new RuntimeException(implode(' ', $messages) ?: $e->getMessage(), 0, $e);
        }
    }

    /**
     * Address on one or two lines (street and number, then postcode and city)
     */
    public static function combinedAddress(string $name, ?string $street, string $postcodeCity): AddressInterface
    {
        return QrBill\DataGroup\Element\CombinedAddress::create($name, (string)$street, $postcodeCity, 'CH');
    }

    /**
     * Swiss address, the house number stays in the street
     */
    public static function structuredAddress(string $name, ?string $street, string $postcode, string $city): AddressInterface
    {
        return QrBill\DataGroup\Element\StructuredAddress::createWithStreet($name, (string)$street, '', $postcode, $city, 'CH');
    }

    /**
     * IBAN without spaces, in upper case
     */
    public static function normalizeIban(string $iban): string
    {
        return strtoupper((string)preg_replace('/\s+/', '', $iban));
    }

    /**
     * IBAN usable in a QR bill without reference: valid CH or LI IBAN, not a QR-IBAN
     */
    public static function isValidIban(string $iban): bool
    {
        $info = QrBill\DataGroup\Element\CreditorInformation::create(self::normalizeIban($iban));

        return $info->isValid() && !$info->containsQrIban();
    }
}
