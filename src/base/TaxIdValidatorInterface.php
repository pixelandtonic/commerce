<?php

namespace craft\commerce\base;

interface TaxIdValidatorInterface
{
    /**
     * The display name of this tax ID type.
     *
     * @return string
     */
    public static function displayName(): string;

    /**
     * Tests if the ID looks generally correct. This would usually be something like a regex check.
     *
     * @return bool
     * @param string $idNumber
     */
    public function validateFormat(string $idNumber): bool;

    /**
     * Tests if the ID exists as valid in the country's tax system. This would usually be an API call.
     *
     * @param string $idNumber
     * @return bool
     */
    public function validateExistence(string $idNumber): bool;

    /**
     * This would usually just call validateFormat() and then validateExistence() and return the result.
     *
     * @param string $idNumber
     * @return bool
     */
    public function validate(string $idNumber): bool;

    /**
     * Tests if the validator is available for use by tax rates.
     * This would usually be a check against the existence or settings or API keys so that the validator can be used.
     *
     * @return bool
     */
    public static function isEnabled(): bool;
}
