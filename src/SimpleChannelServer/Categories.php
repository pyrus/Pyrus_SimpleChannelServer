<?php
/**
 * For easy serialization and validation, the category information is
 * designed to be created like:
 * <code>
 * // make sure the class exists prior to unserialize attempt
 * include '/path/to/PEAR2/SimpleChannelServer/Categories.php';
 * if (!@unserialize(file_get_contents('/path/to/serialize/categories.inf'))) {
 *      $cat = pear\SimpleChannelServer\Categories::create('Name1',
 *          'Description 1', 'Alias1')->
 *          create('Name2', 'Description 2')->
 *          create('Name3', 'Description 3', 'Alias3')->
 *          create('Name4', 'Description 4');
 *      file_put_contents('/path/to/serialize/categories.inf', serialize($cat));
 * }
 * $categories = pear2\SimpleChannelServer\Categories::getCategories();
 * $categories->link('SimpleChannelServer', 'Developer');
 * </code>
 *
 * @category Developer
 * @package  PEAR2_SimpleChannelServer
 * @author   Greg Beaver <cellog@php.net>
 * @license  New BSD?
 * @link     http://svn.pear.php.net/wsvn/PEARSVN/sandbox/SimpleChannelServer/
 */
namespace pear2\SimpleChannelServer;
class Categories
{
    /**
     * Category information indexed by category name
     * @var array('Default' => array('desc' => 'Default Category', 'alias' => 'Default'));
     */
    protected $_categories = array();
    protected $_packages = array();
    
    protected $_restDir;

    public function __construct(\pear2\Pyrus\Channel $channel)
    {
        $rest = preg_replace('/https?:\/\/' . $channel->name . '/',
                            '',
                            $channel->protocols->rest['REST1.0']->baseurl);
        
        $this->_restDir = dirname($channel->path).$rest.'c';
        
        $dir = new \DirectoryIterator($this->_restDir);
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()
                && $fileinfo->isDir()
                && substr($fileinfo->getFilename(), 0, 1) != '.') {
                $parser = new \pear2\Pyrus\XMLParser;
                try {
                    $content = file_get_contents($fileinfo->getPathname().'/info.xml');
                    $content = $parser->parseString($content);
                    $content = current($content);
                    $this->_categories[$fileinfo->getFilename()] = array('desc'=>$content['d'],
                                                                         'alias'=>$content['a'],
                                                                         'xml'=>$content);
                } catch (\Exception $e) {
                    // Skip over this one, bad xml which we can rewrite.
                }
            }
        }
    }

    public function exists($category)
    {
        if (isset($this->_categories[$category])) {
            return true;
        }
        return false;
    }

    /**
     * Creates a channel category
     *
     * @param string $name        Category name
     * @param string $description Description of the category
     * @param string $alias       Alias of the category
     * 
     * @return pear2\SimpleChannelServer\Categories
     */
    public function create($name, $description, $alias = null)
    {
        if (!$alias) {
            $alias = $name;
        }
        $this->_categories[$name] = array('desc' => $description, 'alias' => $alias);
        $this->_info = $this->getCategories();
        return $this;
    }
    
    /**
     * returns categories which are defined
     *
     * @return array
     */
    public function getCategories()
    {
        return $this->_categories;
    }

    /**
     * get the category for a package
     *
     * @param string $package Name of package
     * 
     * @return string
     */
    public function getPackageCategory($package)
    {
        if (!isset($this->_packages[$package])) {
            $this->link($package, 'Default');
        }
        return $this->_packages[$package];
    }

    /**
     * link a package to a category
     *
     * @param string $package  name of the package
     * @param string $category name of the category
     * @param bool   $strict   if package is already in a category, throw exception
     * 
     * @return unknown
     */
    public function linkPackageToCategory($package, $category, $strict = false)
    {
        if (isset($this->_packages[$package]) && $strict) {
            throw new Categories\Exception(
                'Package "' . $package . '" is already linked to category "' .
                $this->_packages[$package] . '"');
        }
        if (!isset($this->_categories[$category])) {
            throw new Categories\Exception(
                'Unknown category "' . $category . '"');
        }
        $this->_packages[$package] = $category;
        return true;
    }

    /**
     * get the packages in a category
     *
     * @param string $category name of category
     * 
     * @return array
     */
    public function packagesInCategory($category)
    {
        $ret = array();
        foreach ($this->_packages as $p => $c) {
            if ($c === $category) {
                $ret[] = $p;
            }
        }
        return $ret;
    }
}