<?php
/**
 * Base class for managing a REST based PEAR compatible channel server.
 *
 * @category Developer
 * @package  PEAR2_SimpleChannelServer
 * @author   Greg Beaver <cellog@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link     http://svn.php.net/viewvc/pear2/sandbox/SimpleChannelServer/
 */
namespace pear2\SimpleChannelServer\REST;
use pear2\SimpleChannelServer\Exception;
class Manager
{
    /**
     * Full path on the filesystem to the REST files
     *
     * @var string
     */
    protected $rest;
    /**
     * Relative path to REST files for URI link construction
     *
     * @var string
     */
    protected $uri;
    /**
     * Channel name this REST server applies to
     *
     * @var string
     */
    protected $channel;

    /**
     * @param string $savepath   full path to REST files
     * @param string $channel    the channel name
     * @param string $serverpath relative path within URI to REST files
     */
    function __construct($savepath, $channel, $serverpath = 'rest/')
    {
        $this->rest = $savepath;
        if (!file_exists($savepath)) {
            if (!@mkdir($savepath, 0777, true)) {
                throw new Exception('Could not initialize' .
                    'REST storage directory "' . $savepath . '"');
            }
        }
        $this->uri     = $serverpath;
        $this->channel = $channel;
        $this->chan    = $channel->name;
    }

    /**
     * Save release REST for a new package release.
     *
     * @param \pear2\Pyrus\Package $release
     * @param string              $releaser handle of person who is uploading this release
     */
    function saveRelease(\pear2\Pyrus\Package $new, $releaser)
    {
        if ($new->channel !== $this->chan) {
            throw new Exception('Cannot release ' .
                $new->name . '-' . $new->version['release'] . ', we are managing ' .
                $this->chan . ' channel, and package is in ' .
                $new->channel . ' channel');
        }
        $categories = new \pear2\SimpleChannelServer\Categories($this->channel);
        $category = new Category($this->rest, $this->channel,
            $this->uri, $categories);
        $package = new Package($this->rest, $this->channel,
            $this->uri);
        $maintainer = new Maintainer($this->rest, $this->channel,
            $this->uri);
        $release = new Release($this->rest, $this->channel,
            $this->uri);
        $maintainer->save($new);
        $package->save($new);
        $release->save($new, $releaser);
        $category->save($new);
    }

    /**
     * Remove a release from package REST
     * 
     * Removes REST.  If $deleteorphaned is true, then
     * maintainers who no longer maintain a package will be
     * deleted from package maintainer REST.
     * @param \pear2\Pyrus\Package $release
     * @param string $deleter handle of maintainer deleting this release
     * @param bool $deleteorphaned
     */
    function deleteRelease(\pear2\Pyrus\Package $release, $deleter, $deleteorphaned = true)
    {
        if ($new->channel !== $this->chan) {
            throw new Exception('Cannot delete release ' .
                $new->name . '-' . $new->version['release'] . ', we are managing ' .
                $this->chan . ' channel, and package is in ' .
                $new->channel . ' channel');
        }

        $category   = new Category($this->rest, $this->channel, $this->uri);
        $package    = new Package($this->rest, $this->channel, $this->uri);
        $maintainer = new Maintainer($this->rest, $this->channel, $this->uri);
        $release    = new Release($this->rest, $this->channel, $this->uri);

        $maintainer->erase($new, $deleteorphaned);
        $package->erase($new);
        $release->erase($new);
        $category->erase($new);
    }

    function __get($var)
    {
        if ($var == 'path') {
            return $this->uri;
        }
        if ($var == 'channel') {
            return $this->chan;
        }
    }

    protected function _getProlog($basetag, $schema)
    {
        return array($basetag => array(
                'attribs' =>
                    array(
                        'xmlns' => 'http://pear.php.net/dtd/rest.' . $schema,
                        'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                        'xmlns:xlink' => 'http://www.w3.org/1999/xlink',
                        'xsi:schemaLocation' => 'http://pear.php.net/dtd/rest.' .
                            $schema . ' http://pear.php.net/dtd/rest.' .
                            $schema . '.xsd',
                    ),
            ));
    }

    function getCategoryRESTLink($file)
    {
        return $this->uri . 'c/' . $file;
    }

    function getPackageRESTLink($file)
    {
        return $this->uri . 'p/' . $file;
    }

    function getReleaseRESTLink($file)
    {
        return $this->uri . 'r/' . $file;
    }

    function getMaintainerRESTLink($file)
    {
        return $this->uri . 'm/' . $file;
    }

    function getRESTPath($type, $file)
    {
        return $this->rest . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR .
            $file;
    }

    private function _initDir($dir, $dirname = false)
    {
        if (!$dirname) $dir = dirname($dir);
        if (!file_exists($dir)) {
            if (!@mkdir($dir, 0777, true)) {
                throw new Exception('Could not initialize' .
                    'REST category storage directory "' . $dir . '"');
            }
        }
    }

    private function _saveREST($path, $contents, $isxml, $type)
    {
        $this->_initDir($this->rest . '/' . $type . '/' . $path);
        if ($isxml) {
            $contents = (string) new \pear2\Pyrus\XMLWriter($contents);
        }
        file_put_contents($this->rest . '/' . $type . '/' . $path, $contents);
        chmod($this->rest . '/' . $type . '/' . $path, 0666);
    }

    function saveReleaseREST($path, $contents, $isxml = true)
    {
        $this->_saveREST($path, $contents, $isxml, 'r');
    }

    function saveCategoryREST($path, $contents, $isxml = true)
    {
        $this->_saveREST($path, $contents, $isxml, 'c');
    }

    function savePackageREST($path, $contents, $isxml = true)
    {
        $this->_saveREST($path, $contents, $isxml, 'p');
    }

    function saveMaintainerREST($path, $contents, $isxml = true)
    {
        $this->_saveREST($path, $contents, $isxml, 'm');
    }
}