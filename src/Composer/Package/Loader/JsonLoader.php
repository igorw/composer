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

namespace Composer\Package\Loader;

use Composer\Json\JsonFile;
use Composer\Json\InvalidJsonException;

use JsonSchema\Validator;

/**
 * @author Konstantin Kudryashiv <ever.zet@gmail.com>
 */
class JsonLoader extends ArrayLoader
{
    protected $schemaValidator;

    public function __construct($parser = null, $schemaValidator = null)
    {
        parent::__construct($parser);

        $this->schemaValidator = $schemaValidator ?: new Validator();
    }

    public function load($json)
    {
        if ($json instanceof JsonFile) {
            $this->validateJson($json);
            $config = $json->read();
        } else {
            $config = $json;
        }

        return parent::load($config);
    }

    private function validateJson(JsonFile $jsonFile)
    {
        $config = $jsonFile->read(false);

        $schemaJson = file_get_contents(__DIR__.'/../../../../doc/composer-schema.json');
        $schema = JsonFile::parseJson($schemaJson, false);

        $result = $this->schemaValidator->validate($config, $schema);
        if (!$result->valid) {
            $errors = array_map(function ($error) { return $error['message']; }, $result->errors);
            throw new InvalidJsonException(
                sprintf("The JSON for '%s' failed schema validation.", $jsonFile->getPath()),
                $errors
            );
        }
    }
}
