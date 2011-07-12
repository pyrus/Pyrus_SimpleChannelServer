<?php
/**
 * Class for managing Maintainer files within the SimpleChannelServer.
 *
 * @category Developer
 * @package  Pyrus_SimpleChannelServer
 * @author   Greg Beaver <cellog@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link     https://github.com/pyrus/Pyrus_SimpleChannelServer
 */
namespace Pyrus\SimpleChannelServer\REST;
use Pyrus\SimpleChannelServer\Exception;
class Maintainer extends Manager
{
    function save(\Pyrus\Package $new)
    {
        foreach ($new->allmaintainers as $role => $maintainers) {
            foreach ($maintainers as $dev) {
                $this->saveInfo($dev->user, $dev->name);
            }
        }
        $this->saveAll();
    }

    /**
     * Save an individual maintainer's REST
     *
     * @param string $handle Maintainer's handle eg: cellog
     * @param string $name   The maintainers real name eg: Gregory Beaver
     * @param string $uri    URI to the person's blog etc.
     */
    function saveInfo($handle, $name, $uri = false)
    {
        $xml = $this->_getProlog('m', 'maintainer');
        $xml['m']['n'] = $name;
        $xml['m']['h'] = $handle;
        if ($uri) {
            $xml['m']['u'] = $uri;
        }
        $this->saveMaintainerREST(strtolower($handle) . '/info.xml', $xml);
    }

    /**
     * Grab information on a maintainer
     *
     * @param string $handle The user's handle eg: cellog
     * 
     * @return array
     */
    function getInfo($handle)
    {
        $path = $this->getRESTPath('m', strtolower($handle) . '/info.xml');
        $reader = new \Pyrus\XMLParser;
        if (!file_exists($path)) {
            return false;
        }
        try {
            $info = $reader->parse($path);
            return $info['m'];
        } catch (\Exception $e) {
            throw new Exception('Cannot read information on ' .
                'developer ' . $handle, $e);
        }
    }

    /**
     * Save a list of all maintainers in REST
     */
    function saveAll()
    {
        $xml = $this->_getProlog('m', 'allmaintainers');
        $xml['m']['h'] = array();
        foreach (new \DirectoryIterator($this->rest . '/m') as $file) {
            if ($file->isDot()) continue;
            if ($file->isDir()
                && $file->getBasename() != 'CVS'
                && $file->getBasename() != '.svn') {
                $xml['m']['h'][] = array(
                    'attribs' => array(
                        'xlink:href' => $this->uri . 'm/' . $file
                    ),
                    '_content' => $file->__toString()
                );
            }
        }
        $this->saveMaintainerREST('allmaintainers.xml', $xml);
    }
}