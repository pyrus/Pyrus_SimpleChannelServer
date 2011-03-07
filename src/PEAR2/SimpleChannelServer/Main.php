<?php
/**
 * Class for a filesystem only PEAR compatible channel server.
 *
 * @category Developer
 * @package  PEAR2_SimpleChannelServer
 * @author   Greg Beaver <cellog@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link     http://svn.php.net/viewvc/pear2/sandbox/SimpleChannelServer/
 */
namespace PEAR2\SimpleChannelServer;
class Main
{
    /**
     * @var string
     */
    protected $channel;
    /**
     * @var string
     */
    protected $webpath;
    
    /**
     * Path to rest files
     * @var string
     */
    protected $restpath;
    /**
     * @var string
     */
    protected $uri;
    /**
     * REST manager
     *
     * @var PEAR2\SimpleChannelServer\REST\Manager
     */
    protected $rest;

    /**
     * GET manager
     *
     * @var PEAR2\SimpleChannelServer\Get
     */
    protected $get;

    /**
     * Construct simple channel server
     *
     * @param PEAR2\SimpleChannelServer\Channel $channel   channel object
     * @param string $webpath   full path to web files eg: /var/www/pear/
     * @param string $pyruspath Path to the pyrus controlled PEAR installation
     */
    function __construct($channel, $webpath, $pyruspath = null)
    {
        if (!realpath($webpath) || !is_writable($webpath)) {
            throw new Exception('Path to channel web files ' .
                $webpath .
                ' must exist and be writable');
        } else {
            $this->webpath = $webpath;
        }
        if (!$pyruspath) {
            $pyruspath = __DIR__;
        }
        $rest = $channel->protocols->rest;
        foreach ($rest as $restpath) {
            $restpath = str_replace('http://'.$channel->name.'/', '', $restpath);
            break;
        }
        if (dirname($restpath . 'a') . '/' !== $restpath) {
            $restpath .= '/';
        }
        $this->restpath = $webpath.'/'.$restpath;
        $this->uri      = 'http://' . $channel->name . '/';
        $this->channel  = $channel;
        $this->rest     = new REST\Manager($this->restpath,
                                           $channel,
                                           $restpath);
        $this->get      = new Get($webpath.'/get', $pyruspath);
        try {
            $a = \PEAR2\Pyrus\Config::singleton($pyruspath);
        } catch (Exception $e) {
            throw new Exception('Cannot initialize Pyrus Config',
                $e);
        }
    }
    
    function categorize($package, $category)
    {
        $categories = new Categories($this->channel);
        if (!$categories->exists($category)) {
            echo 'That category doesn\'t exist yet. Use add-category first' . PHP_EOL;
        }
        $categories->linkPackageToCategory($package, $category);
        $rcat = new REST\Category($this->restpath, $this->channel, 'rest/', $categories);
        $rcat->savePackages($category);
        $rcat->savePackagesInfo($category);
    }

    function saveRelease($package, $releaser)
    {
        $rest = $this->rest->saveRelease(new \PEAR2\Pyrus\Package($package), $releaser);
        $get  = $this->get->saveRelease($package, $releaser);
        return $rest && $get;
    }

    function saveChannel()
    {
        file_put_contents($this->webpath . '/channel.xml', $this->channel->getChannelFile());
        chmod($this->webpath . '/channel.xml', 0666);
    }

    /**
     * List all categories (unsorted)
     *
     * @return array
     */
    function listCategories()
    {
        $categories = new Categories($this->channel);
        return $categories->getCategories();
    }

    /**
     * List all packages, organized by category (unsorted)
     *
     * @return array
     */
    function listPackagesByCategory()
    {
        $categories = new Categories($this->channel);
        $ret = array();
        foreach ($categories->getCategories() as $cat) {
            $ret[$cat] = $categories->packagesInCategory($cat);
        }
        return $ret;
    }

    /**
     * List all packages, or all packages in a category
     *
     * @param string|null $category null to list all packages
     *
     * @return array
     */
    function listPackages($category = null)
    {
        $categories = new Categories($this->channel);
        if ($category) {
            return $categories->packagesInCategory($category);
        }
        if (!file_exists($this->rest->getRESTPath('p', 'allpackages.xml'))) {
            return array();
        }
        try {
            $list = $reader->parse($this->rest->getRESTPath('p', 'allpackages.xml'));
        } catch (\Exception $e) {
            throw new Exception('Unable to list packages',
                $e);
        }
        return $list['a']['p'];
    }

    /**
     * List all maintainers, or maintainers of a specific package
     *
     * @param string|null $package null to list all maintainers
     *
     * @return array
     */
    function listMaintainers($package = null)
    {
        if ($package === null) {
            if (!file_exists($this->rest->getRESTPath('p', 'allpackages.xml'))) {
                return array();
            }
            try {
                $list  = $reader->parse($this->rest->getRESTPath('m', 'allmaintainers.xml'));
                $maint = new REST\Maintainer($this->webpath,
                    $this->channel, $this->uri);
                $ret   = array();
                foreach ($list['m']['h'] as $info) {
                    $inf = $maint->getInfo($info['_content']);
                    if (!$inf) {
                        throw new Exception('Maintainer ' .
                            $info['_content'] . ' is listed as a maintainer, ' .
                            'but does not have an info file');
                    }
                    $ret[] = array(
                        'user' => $info['_content'],
                        'name' => $inf['n']
                    );
                }
                return $ret;
            } catch (\Exception $e) {
                throw new Exception('Unable to list maintainers',
                    $e);
            }
        }
        $reader = new \PEAR2\Pyrus\XMLParser;
        $path   = $this->rest->getRESTPath('r', strtolower($package) . '/maintainers2.xml');
        if (!file_exists($path)) {
            return array();
        }
        try {
            $list = $reader->parse($path);
        } catch (\Exception $e) {
            throw new Exception('Unable to list maintainers for' .
                ' package ' . $package,
                $e);
        }
        $ret = array();
        if (!isset($list['m']['m'][0])) {
            $list['m']['m'] = array($list['m']['m']);
        }
        $maint = new REST\Maintainer($this->webpath, $this->channel,
            $this->uri);
        foreach ($list['m']['m'] as $maintainer) {
            $info = $maint->getInfo($maintainer['h']);
            $inf  = array(
                'user' => $maintainer['h'],
            );
            if ($info) {
                $inf['name'] = $info['n'];
            }
            $inf['active'] = $maintainer['a'];
            $ret[]         = $inf;
        }
        return $ret;
    }

    /**
     * List release info with dependencies formatted for easy processing
     * by a web frontend.
     *
     * @param string $package Package name eg: PEAR2_SimpleChannelServer
     *
     * @return array
     */
    function listReleases($package)
    {
        $path = $this->rest->getRESTPath('r', strtolower($package) . '/allreleases2.xml');
        if (!file_exists($path)) {
            return array();
        }
        try {
            $list = $reader->parse($path);
        } catch (\Exception $e) {
            throw new Exception('Unable to list releases of ' .
                $package . ' package',
                $e);
        }
        if (!isset($list['a']['r'][0])) {
            $list['a']['r'] = array($list['a']['r']);
        }
        $ret = array();
        foreach ($list['a']['r'] as $info) {
            $inf = array(
                'version' => $info['v'],
                'stability' => $info['s'],
                'minimum PHP version' => $info['m']
            );

            $deps = unserialize(file_get_contents($this->rest->getPath('r',
                strtolower($package) . '/deps.' . $info['v'] . '.txt')));

            $inf['required'] = array();
            if (isset($deps['required']['package'])) {
                $inf['required']['package'] = $deps['required']['package'];
                if (!isset($inf['required']['package'][0])) {
                    $inf['required']['package'] = array($inf['required']['package']);
                }
            }
            if (isset($deps['required']['subpackage'])) {
                if (!isset($deps['required']['subpackage'][0])) {
                    $deps['required']['subpackage'] = array($deps['required']['subpackage']);
                }
                foreach ($deps['required']['subpackage'] as $s) {
                    $inf['required']['package'][] = $s;
                }
            }
            if (isset($deps['required']['extension'])) {
                if (!isset($deps['required']['extension'][0])) {
                    $deps['required']['extension'] = array($deps['required']['extension']);
                }
                foreach ($deps['required']['extension'] as $s) {
                    $inf['required']['extension'][] = $s;
                }
            }
            if (isset($deps['optional']['package'])) {
                $inf['optional']            = array();
                $inf['optional']['package'] = $deps['optional']['package'];
                if (!isset($inf['optional']['package'][0])) {
                    $inf['optional']['package'] = array($inf['optional']['package']);
                }
            }
            if (isset($deps['optional']['extension'])) {
                if (!isset($deps['optional']['extension'][0])) {
                    $deps['optional']['extension'] = array($deps['optional']['extension']);
                }
                foreach ($deps['optional']['extension'] as $s) {
                    $inf['optional']['extension'][] = $s;
                }
            }
            if (isset($deps['optional']['subpackage'])) {
                if (!isset($deps['optional']['subpackage'][0])) {
                    $deps['optional']['subpackage'] = array($deps['optional']['subpackage']);
                }
                foreach ($deps['optional']['subpackage'] as $s) {
                    $inf['optional']['package'][] = $s;
                }
            }
            if (isset($deps['group'])) {
                $inf['groups'] = array();
                if (!isset($deps['group'][0])) {
                    $deps['group'] = array($deps['group']);
                }
                foreach ($deps['group'] as $group) {
                    $inf['groups'] = $group['attribs'];
                }
            }
            $ret[] = $inf;
        }
        return $ret;
    }
}
