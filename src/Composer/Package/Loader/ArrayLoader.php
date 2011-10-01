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

use Composer\Package;

use JsonSchema\Validator;

/**
 * @author Konstantin Kudryashiv <ever.zet@gmail.com>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class ArrayLoader
{
    protected $supportedLinkTypes = array(
        'require'   => 'requires',
        'conflict'  => 'conflicts',
        'provide'   => 'provides',
        'replace'   => 'replaces',
        'recommend' => 'recommends',
        'suggest'   => 'suggests',
    );

    protected $versionParser;
    protected $schemaValidator;

    public function __construct($parser = null, $schemaValidator = null)
    {
        $this->versionParser = $parser;
        if (!$parser) {
            $this->versionParser = new Package\Version\VersionParser;
        }

        $this->schemaValidator = $schemaValidator;
        if (!$schemaValidator) {
            $this->schemaValidator = new Validator;
        }
    }

    public function load($config)
    {
        $this->validateConfig($config);

        $version = $this->versionParser->normalize($config['version']);
        $package = new Package\MemoryPackage($config['name'], $version);

        $package->setType(isset($config['type']) ? $config['type'] : 'library');

        if (isset($config['extra'])) {
            $package->setExtra($config['extra']);
        }

        if (isset($config['license'])) {
            $package->setLicense($config['license']);
        }

        if (isset($config['source'])) {
            if (!isset($config['source']['type']) || !isset($config['source']['url'])) {
                throw new \UnexpectedValueException(sprintf(
                    "package source should be specified as {\"type\": ..., \"url\": ...},\n%s given",
                    json_encode($config['source'])
                ));
            }
            $package->setSourceType($config['source']['type']);
            $package->setSourceUrl($config['source']['url']);
            $package->setSourceReference($config['source']['reference']);
        }

        if (isset($config['dist'])) {
            if (!isset($config['dist']['type'])
             || !isset($config['dist']['url'])
             || !isset($config['dist']['shasum'])) {
                throw new \UnexpectedValueException(sprintf(
                    "package dist should be specified as ".
                    "{\"type\": ..., \"url\": ..., \"shasum\": ...},\n%s given",
                    json_encode($config['source'])
                ));
            }
            $package->setDistType($config['dist']['type']);
            $package->setDistUrl($config['dist']['url']);
            $package->setDistReference($config['dist']['reference']);
            $package->setDistSha1Checksum($config['dist']['shasum']);
        }

        foreach ($this->supportedLinkTypes as $type => $description) {
            if (isset($config[$type])) {
                $method = 'set'.ucfirst($description);
                $package->{$method}(
                    $this->loadLinksFromConfig($package->getName(), $description, $config[$type])
                );
            }
        }

        return $package;
    }

    private function loadLinksFromConfig($srcPackageName, $description, array $linksSpecs)
    {
        $links = array();
        foreach ($linksSpecs as $packageName => $constraint) {
            $name = strtolower($packageName);

            $constraint = $this->versionParser->parseConstraints($constraint);
            $links[]    = new Package\Link($srcPackageName, $packageName, $constraint, $description);
        }

        return $links;
    }

    private function validateConfig(array $config)
    {
        $configObj = $this->recursiveArrayToObject($config);
        $schemaObj = json_decode(file_get_contents(__DIR__.'/../../../../doc/composer-schema.json'));
        var_dump($this->schemaValidator->validate($config, $schemaObj));
    }

    private function recursiveArrayToObject($input)
    {
        if (is_array($input) && !isset($input[0])) {
            $object = new \stdClass();
            foreach ($input as $key => $value) {
                $object->$key = $this->recursiveArrayToObject($value);
            }
            return $object;
        } else {
            return $input;
        }
    }
}
