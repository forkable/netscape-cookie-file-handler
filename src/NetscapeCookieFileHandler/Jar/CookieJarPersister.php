<?php

namespace KeGi\NetscapeCookieFileHandler\Jar;

use DateTime;
use KeGi\NetscapeCookieFileHandler\Configuration\ConfigurationInterface;
use KeGi\NetscapeCookieFileHandler\Configuration\MandatoryConfigurationTrait;
use KeGi\NetscapeCookieFileHandler\Cookie\CookieCollectionInterface;
use KeGi\NetscapeCookieFileHandler\Cookie\CookieInterface;
use KeGi\NetscapeCookieFileHandler\Jar\Exception\CookieJarPersisterException;
use KeGi\NetscapeCookieFileHandler\Parser\Exception\ParserException;
use KeGi\NetscapeCookieFileHandler\Parser\Parser;

class CookieJarPersister implements CookieJarPersisterInterface
{

    use MandatoryConfigurationTrait;

    /**
     * Cookie file header
     */
    const FILE_HEADERS
        = [
            'Netscape HTTP Cookie File',
            'This file was generated by "netscape-cookie-file-handler" free PHP7 tool',
            'https://github.com/kegi/netscape-cookie-file-handler',
        ];

    /**
     * @param ConfigurationInterface $configuration
     */
    public function __construct(
        ConfigurationInterface $configuration
    ) {
        $this->setConfiguration($configuration);
    }

    /**
     * @param CookieCollectionInterface $cookies
     * @param string                    $filename
     *
     * @return CookieJarPersisterInterface
     * @throws CookieJarPersisterException
     * @throws ParserException
     */
    public function persist(
        CookieCollectionInterface $cookies,
        string $filename
    ) : CookieJarPersisterInterface {
        if (empty($this->getConfiguration()->getCookieDir())) {
            throw new CookieJarPersisterException(
                'You need to specify the cookieDir parameter in configurations in order to persist a file'
            );
        }

        $cookieDir = rtrim(
                $this->getConfiguration()->getCookieDir(),
                DIRECTORY_SEPARATOR
            ) . DIRECTORY_SEPARATOR;

        $filename = $cookieDir . $filename;

        $fileContent = $this->generateFileOutput($cookies);

        // @codeCoverageIgnoreStart

        if (empty($fileContent)) {
            if (is_file($filename)) {
                if (!unlink($filename)) {
                    throw new CookieJarPersisterException(
                        sprintf(
                            'Unable to delete the cookies file : %1$s',
                            $filename
                        )
                    );
                }
            }
        } else {
            if (file_put_contents($filename, $fileContent) === false) {
                if (file_exists($filename)) {
                    throw new CookieJarPersisterException(
                        sprintf(
                            'Unable to edit the cookies file : %1$s',
                            $filename
                        )
                    );
                } else {
                    throw new CookieJarPersisterException(
                        sprintf(
                            'Unable to create the cookies file : %1$s',
                            $filename
                        )
                    );
                }
            }
        }

        // @codeCoverageIgnoreEnd

        return $this;
    }

    /**
     * Returns the cookies file content or false if any cookies
     *
     * @param CookieCollectionInterface $cookies
     *
     * @return string|bool
     */
    private function generateFileOutput(
        CookieCollectionInterface $cookies
    ) {
        $output = '';

        foreach ($cookies->getCookies() as $domainCookies) {
            foreach ($domainCookies as $cookie) {

                /** @var CookieInterface $cookie */

                $domain = $cookie->getDomain();
                $httpOnly = $cookie->isHttpOnly();
                $flag = $cookie->isFlag();
                $path = $cookie->getPath();
                $secure = $cookie->isSecure();
                $expire = $cookie->getExpire();
                $name = $cookie->getName();
                $value = $cookie->getValue();

                /*format data for output*/

                if ($httpOnly) {
                    $domain = Parser::HTTP_ONLY_PREFIX . $domain;
                }

                $flag = $flag ? 'TRUE' : 'FALSE';
                $secure = $secure ? 'TRUE' : 'FALSE';

                if (empty($path)) {
                    $path = '/';
                }

                if ($expire instanceof DateTime) {
                    $expire = (string)$expire->getTimestamp();
                } else {
                    $expire = '0';
                }

                /*add cookie to file*/

                $output .= implode("\t", array_map('trim', [
                        $domain,
                        $flag,
                        $path,
                        $secure,
                        $expire,
                        $name,
                        $value,
                    ])) . PHP_EOL;
            }
        }

        if (empty($output)) {
            return false;
        }

        return implode(PHP_EOL, array_map(function ($line) {
            return '# ' . $line;
        }, self::FILE_HEADERS)) . PHP_EOL . PHP_EOL . $output;
    }
}
