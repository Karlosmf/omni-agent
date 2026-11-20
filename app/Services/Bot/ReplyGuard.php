<?php

namespace App\Services\Bot;

/**
 * Last line of defence for the bot's non-negotiable rules. The prompt asks the model to follow them;
 * this refuses any reply that still carries payment details, or an amount nobody gave it.
 * Confirmed availability cannot be told apart by pattern, so that rule rests on the prompt alone.
 */
class ReplyGuard
{
    private const PAYMENT_TERMS = '/\b(cbu|cvu|iban|swift|alias|mercado\s?pago|transferencias?|dep[oó]sitos?|tarjetas?|cuotas?)\b/iu';

    private const ACCOUNT_NUMBER = '/\d[\d\s-]{14,}\d/';

    private const MONEY = '/(?:US\$|U\$S|USD|ARS|\$)\s?(\d[\d.,]*)|(\d[\d.,]*)\s?(?:usd|d[oó]lares|pesos|mil\b)/iu';

    /**
     * @param  string  $promotions  Text of the promotions in force: the only source of prices and payment terms the bot may repeat.
     * @param  string  $customerText  What the customer wrote: the bot may repeat their own budget back.
     */
    public function isSafe(string $reply, string $promotions = '', string $customerText = ''): bool
    {
        if (preg_match(self::ACCOUNT_NUMBER, $reply)) {
            return false;
        }

        preg_match_all(self::PAYMENT_TERMS, $reply, $terms);

        foreach ($terms[1] as $term) {
            if (mb_stripos($promotions, $term) === false) {
                return false;
            }
        }

        preg_match_all(self::MONEY, $reply, $amounts, PREG_SET_ORDER);
        $known = preg_replace('/[.,]/', '', $promotions.' '.$customerText);

        foreach ($amounts as $amount) {
            $digits = preg_replace('/\D/', '', $amount[1] ?: ($amount[2] ?? ''));

            if ($digits !== '' && ! preg_match('/(?<!\d)'.$digits.'(?!\d)/', $known)) {
                return false;
            }
        }

        return true;
    }
}
