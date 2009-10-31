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
    private $_categories = array();
    private $_packages = array();
    /**
     * @var PEAR2_SimpleChannelServer_Categories
     */
    static private $_category;

    /**
     * No direct instantiation allowed
     */
    private function __construct()
    {
    }

    static function exists($category)
    {
        if (!isset(self::$_category)) {
            return false;
        }
        if (isset(self::$_category->_categories[$category])) {
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
    static function create($name, $description, $alias = null)
    {
        if (!isset(self::$_category)) {
            self::$_category = new Categories;
        }
        return self::$_category->_create($name, $description, $alias);
    }

    private function _create($name, $description, $alias = null)
    {
        if (isset($this->_categories[$name])) {
            throw new Categories\Exception(
                'Category "' . $name . '" has already been defined');
        }
        if (!$alias) {
            $alias = $name;
        }
        $this->_categories[$name] = array('desc' => $description, 'alias' => $alias);
        $this->_info = false;
        self::$_category = $this;
        $this->_info = $this->getCategories();
        return $this;
    }
    
    /**
     * returns categories which are defined
     *
     * @return array
     */
    static function getCategories()
    {
        if (self::$_category === null) {
            throw new Categories\Exception('You must construct a singleton instance with pear2\SimpleChannelServer\Categories::create($name, $description, $alias = null)');
        } else {
            return self::$_category->_categories;
        }
    }

    /**
     * get the category for a package
     *
     * @param string $package Name of package
     * 
     * @return string
     */
    static function getPackageCategory($package)
    {
        if (self::$_category === null) {
            throw new Categories\Exception('You must construct a singleton instance with pear2\SimpleChannelServer\Categories::create($name, $description, $alias = null)');
        } else {
            return self::$_category->getCategory($package);
        }
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
    static function linkPackageToCategory($package, $category, $strict = false)
    {
        if (!isset(self::$_category)) {
            self::$_category = new Categories;
        }
        return self::$_category->link($package, $category, $strict);
    }

    /**
     * get the packages in a category
     *
     * @param string $category name of category
     * 
     * @return array
     */
    static function packagesInCategory($category)
    {
        return self::$_category->packages($category);
    }

    /**
     * return all known packages in a specific category
     *
     * @param string $category name of category
     * 
     * @return array(string) Names of packages in the category
     */
    public function packages($category)
    {
        $ret = array();
        foreach ($this->_packages as $p => $c) {
            if ($c === $category) {
                $ret[] = $p;
            }
        }
        return $ret;
    }
    
    /**
     * find what category a package is in - if the category for this package is not
     * defined, it will assign it to the default category
     *
     * @param string $package Name of the package to check
     * 
     * @return string name of the category
     */
    public function getCategory($package)
    {
        if (!isset($this->_packages[$package])) {
            $this->link($package, 'Default');
        }
        return $this->_packages[$package];
    }
    
    /**
     * Links a package to a specific category
     *
     * @param string $package  name of package
     * @param string $category name of category
     * @param bool   $strict   ensure packages are only in one category
     * 
     * @return void
     */
    public function link($package, $category, $strict = false)
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
    }

    /**
     * called after serialized and woken up
     * 
     * @return void
     */
    function __wakeup()
    {
        self::$_category = $this;
    }
}