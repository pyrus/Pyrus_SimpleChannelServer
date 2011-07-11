<?php
/**
 * Class for managing category information for the PEAR channel.
 *
 * @category Developer
 * @package  PEAR2_SimpleChannelServer
 * @author   Greg Beaver <cellog@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link     http://svn.php.net/viewvc/pear2/sandbox/SimpleChannelServer/
 */
namespace PEAR2\SimpleChannelServer\REST;
use PEAR2\SimpleChannelServer\Categories;
class Category extends Manager
{
    protected $_categories;
    
    /**
     * Construct a new rest category object
     * 
     * @param string $savepath   full path to REST files
     * @param string $channel    the channel name
     * @param string $serverpath relative path within URI to REST files
     */
    function __construct($savepath, $channel, $serverpath = 'rest/', Categories $categories)
    {
        $this->_categories = $categories;
        parent::__construct($savepath, $channel, $serverpath);
    }

    /**
     * Save a package release's REST-related information
     *
     * @param \Pyrus\Package $new Package to save category for
     * 
     * @return void
     */
    function save(\Pyrus\Package $new)
    {
        $category = $this->_categories->getPackageCategory($new->name);
        $this->savePackages($category);
        $this->savePackagesInfo($category);
        $this->saveAllCategories();
    }

    /**
     * Delete a package release's REST-related information
     *
     * @param \Pyrus\Package $new Package to rease rest info for
     * 
     * @return void
     */
    function erase(\Pyrus\Package $new)
    {
        $category = $this->_categories->getPackageCategory($new->name);
        $this->savePackagesInfo($category);
    }

    /**
     * Save REST xml information for all categories
     * 
     * This is not release-dependent
     * 
     * @return void
     */
    function saveAllCategories()
    {
        $categories     = $this->_categories->getCategories();
        $xml            = $this->_getProlog('a', 'allcategories');
        $xml['a']['ch'] = $this->channel->name;
        $xml['a']['c']  = array();
        
        foreach ($categories as $category => $data) {
            $xml['a']['c'][] = array(
                'attribs' => array(
                    'xlink:href' =>
                    $this->getCategoryRESTLink(urlencode($category) . '/info.xml')),
                '_content' => $category,
            );
            $this->saveInfo($category, $data['d'], $data['a']);
        }
    
        $this->saveCategoryREST('categories.xml', $xml);
    }

    /**
     * Save information on a category
     * 
     * This is not release-dependent
     *
     * @param string $category The name of the category eg:Services
     * @param string $desc     Basic description for the category
     * @param string $alias    Optional alias category name
     * 
     * @return void
     */
    function saveInfo($category, $desc, $alias = false)
    {
        if (!$this->_categories->exists($category)) {
            $this->_categories->create($category, $desc, $alias);
        }
        $xml           = $this->_getProlog('c', 'category');
        $xml['c']['n'] = $category;
        $xml['c']['a'] = $alias ? $category : $alias;
        $xml['c']['c'] = $this->channel->name;
        $xml['c']['d'] = $desc;
        $this->saveCategoryREST(urlencode($category) . '/info.xml', $xml);
    }
    
    /**
     * Save packages.xml containing a list of all packages within this category
     * 
     * @param string $category Category to save
     * 
     * @return void
     */
    function savePackages($category)
    {
        $packages = '';
        foreach ($this->_categories->packagesInCategory($category) as $package) {
            $packages['p'][] = array('attribs' => array('xlink:href' => $this->getPackageRESTLink($package)),
                                     '_content' => $package);
        }
        $xml = $this->_getProlog('l', 'categorypackages');
        $xml['l'][] = $packages;
        $this->saveCategoryREST(urlencode($category) . DIRECTORY_SEPARATOR . 'packages.xml', $xml);
    }

    /**
     * Save packagesinfo.xml for a category
     *
     * @param string $category Category to update packages info for
     * 
     * @return void
     */
    function savePackagesInfo($category)
    {
        $xml  = array();
        $pdir = $this->rest . DIRECTORY_SEPARATOR . 'p';
        $rdir = $this->rest . DIRECTORY_SEPARATOR . 'r';
        $packages = $this->_categories->packagesInCategory($category);
        $reader   = new \Pyrus\XMLParser;
        clearstatcache();
        $xml['pi'] = array();
        foreach ($packages as $package) {
            $next = array();
            if (!file_exists($pdir . DIRECTORY_SEPARATOR . strtolower($package) .
                    DIRECTORY_SEPARATOR . 'info.xml')) {
                continue;
            }
            $f = $reader->parse($pdir . DIRECTORY_SEPARATOR . strtolower($package) .
                    DIRECTORY_SEPARATOR . 'info.xml');
            unset($f['p']['attribs']);
            $next['p'] = $f['p'];
            if (file_exists($rdir . DIRECTORY_SEPARATOR . strtolower($package) .
                    DIRECTORY_SEPARATOR . 'allreleases.xml')) {
                $r = $reader->parse($rdir . DIRECTORY_SEPARATOR .
                        strtolower($package) . DIRECTORY_SEPARATOR .
                        'allreleases.xml');
                unset($r['a']['attribs']);
                unset($r['a']['p']);
                unset($r['a']['c']);
                $next['a'] = $r['a'];
                $dirhandle = opendir($rdir . DIRECTORY_SEPARATOR .
                    strtolower($package));
                while (false !== ($entry = readdir($dirhandle))) {
                    if (strpos($entry, 'deps.') === 0) {
                        $version = str_replace(array('deps.', '.txt'), array('', ''), $entry);
                        
                        $next['deps']      = array();
                        $next['deps']['v'] = $version;
                        $next['deps']['d'] = file_get_contents($rdir . DIRECTORY_SEPARATOR .
                            strtolower($package) . DIRECTORY_SEPARATOR .
                            $entry);
                    }
                }
            }
            $xml['pi'][] = $next;
        }
        $xmlinf        = $this->_getProlog('f', 'categorypackageinfo');
        $xmlinf['f'][] = $xml;
        $this->saveCategoryREST(urlencode($category) . DIRECTORY_SEPARATOR . 'packagesinfo.xml', $xmlinf);
    }
}