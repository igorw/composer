<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\Json;

/**
 * Exception for invalid JSON syntax or schema.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 */
class InvalidJsonException extends \UnexpectedValueException
{
    private $schemaErrors;

    public function __construct($message, $schemaErrors = null)
    {
        parent::__construct($message);

        $this->schemaErrors = $schemaErrors;
    }

    public function getSchemaErrors()
    {
        return $this->schemaErrors;
    }
}
