<?php
namespace PEAR2\SimpleChannelServer;
class CLI
{
    /**
     * Channel object
     *
     * @var \Pyrus\Channel
     */
    protected $channel;
    
    /**
     * Directory to the channel we're managing.
     *
     * @var string
     */
    public $dir;
    
    public $pyruspath = false;
    
    /**
     * The simplechannelserver object
     * 
     * @var PEAR2\SimpleChannelServer\Main
     */
    public $scs;
    
    public function getSCS()
    {
        if (!isset($this->scs)) {
            $channel_file = getcwd() . '/channel.xml';
            $this->dir = getcwd();
            if (!file_exists($channel_file)) {
                throw new Exception('No channel.xml file could be found! Create the channel, or change to the directory with the channel.xml.');
            }
            $this->channel = new \Pyrus\Channel(new \Pyrus\ChannelFile($channel_file));
            $this->scs = new Main($this->channel, $this->dir);
        }
        return $this->scs;
    }
    
    public function process()
    {
        if ($_SERVER['argc'] < 2) {
            $this->printUsage();
            return false;
        }
        switch ($_SERVER['argv'][1]) {
            case 'update':
                $this->handleUpdate();
                break;
            case 'create':
                return $this->handleCreate();
            case 'add-maintainer':
                    // is this even needed?
                    // yes, new maintainers that have not yet released anything.
                break;
            case 'add-category':
                $this->handleAddCategory();
                break;
            case 'categorize':
                $this->handleCategorize();
                break;
            case 'release':
                $this->handleRelease();
                break;
            default:
                echo 'Please use one of the following commands:'.PHP_EOL;
                $this->printUsage();
                break;
        }
    }
    
    function handleCategorize()
    {
        if ($_SERVER['argc'] < 3) {
            $this->printCategorizeUsage();
            return false;
        }
        $args = array();
        $args['package'] = $_SERVER['argv'][2];
        $args['category'] = $_SERVER['argv'][3];
        $this->getSCS()->categorize($args['package'], $args['category']);
        echo "Added  {$args['package']} to {$args['category']} \n";
    }
    
    function pyrusCategorize($frontend, $args)
    {
        $this->getSCS()->categorize($args['package'], $args['category']);
        echo "Added  {$args['package']} to {$args['category']} \n";
    }
    
    function printCategorizeUsage()
    {
        echo 'Usage: pearscs categorize package category
    This will add the given package to a category.

    package  Name of the package.
    category Name of the category.' . PHP_EOL;
    }
    
    function handleAddCategory()
    {
        if ($_SERVER['argc'] < 3) {
            $this->printCategoryUsage();
            return false;
        }

        $args = array();
        $args['category']    = $_SERVER['argv'][2];
        $args['description'] = (isset($_SERVER['argv'][3]))?
                                    $_SERVER['argv'][3]:$_SERVER['argv'][2];

        $this->getSCS();
        $categories = new Categories($this->channel);
        $categories->create($args['category'], $args['description']);
        $category = new REST\Category($this->dir . '/rest', $this->channel, 'rest/', $categories);
        $category->saveAllCategories();
        $category->savePackagesInfo($args['category']);
        echo "Added category ", $args['category'], "\n";
    }

    function pyrusAddCategory($frontend, $args)
    {
        if (!isset($this->channel)) {
            throw new Exception('Unknown channel, run the ' .
                                'scs-create command first');
        }

        $this->getSCS();
        $categories = new Categories($this->channel);
        $categories->create($args['category'], $args['description']);
        $category->savePackagesInfo($args['category']);
        echo "Added category ", $args['category'], "\n";
    }
    
    function printCategoryUsage()
    {
        echo 'Usage: pearscs add-category category [description]
    This will add a category to the current channel.
    
    category    Name of the category.
    description Description of the category.' . PHP_EOL;
    }

    function pyrusAddMaintainer($frontend, $args)
    {
        $this->getSCS();
        $maintainer = new REST\Maintainer($this->dir . '/rest', $this->channel->name);
        if (isset($args['uri'])) {
            $uri = $args['uri'];
        } else {
            $uri = null;
        }
        $maintainer->saveInfo($args['handle'], $args['name'], $uri);
        $maintainer->saveAll();
        echo "Added maintainer ", $args['handle'], "\n";
    }
    
    public function handleUpdate()
    {
        if (!isset($_SERVER['argv'][2])) {
            $this->printUpdateUsage();
            return;
        }
        if (!isset($this->channel)) {
            $this->printUpdateUsage();
            return;
        }
        $this->pyrusUpdate(null, $_SERVER['argv'][2]);
    }

    function pyrusUpdate($frontend, $args)
    {
        $this->getSCS();
        if (null === $frontend) {
            $maintainer = $args;
        } else {
            $chan = \Pyrus\Config::current()->default_channel;
            \Pyrus\Config::current()->default_channel = $this->channel->name;
            $maintainer = \Pyrus\Config::current()->handle;
            \Pyrus\Config::current()->default_channel = $chan;
        }
        
        $dirname = $this->dir . '/get/';
        $dir = new \DirectoryIterator($dirname);
        $errs = new \PEAR2\MultiErrors;
        foreach ($dir as $file) {
            if (!$file->isDot()
                && !$file->isDir()
                && substr($file->getFilename(), -3) != 'tar'
                && substr($file->getFilename(), 0, 1) != '.') {
                try {
                    $this->scs->saveRelease($dirname.$file->getFilename(), $maintainer);
                    echo $file->getFilename().' successfully saved.'.PHP_EOL;
                } catch(\Exception $e) {
                    echo $file->getFilename().' '.$e->getMessage().PHP_EOL;
                    $errs->E_ERROR[] = $e;
                }
            }
        }
        if (count($errs->E_ERROR)) {
            throw new Exception('Could not save some releases.',  0, $errs);
        }
    }
    
    public function handleRelease()
    {
        if (!isset($_SERVER['argv'][3])) {
            $this->printReleaseUsage();
            return;
        }
        $this->pyrusRelease(null, array('path' => $_SERVER['argv'][2],
                                        'maintainer' => $_SERVER['argv'][3]));
    }

    function pyrusRelease($frontend, $args)
    {
        $this->getSCS();
        if (null !== $frontend) {
            $chan = \Pyrus\Config::current()->default_channel;
            \Pyrus\Config::current()->default_channel = $this->channel->name;
            $args['maintainer'] = \Pyrus\Config::current()->handle;
            \Pyrus\Config::current()->default_channel = $chan;
        }
        $this->scs->saveRelease($args['path'], $args['maintainer']);
        echo 'Release successfully saved.'.PHP_EOL;
    }

    public function printReleaseUsage()
    {
        echo '
Usage: pearscs release packagefile maintainer
    This will release the package to the channel.
    
    packagefile The release .tgz file.
    maintainer  The channel maintainer performing the release.
    
';
    }
    
    public function handleCreate()
    {
        if ($_SERVER['argc'] < 4) {
            $this->printCreateUsage();
            return false;
        }
        
        $name        = $_SERVER['argv'][2];
        $summary     = $_SERVER['argv'][3];
        $alias       = null;

        $args = array('name' => $name,
                      'summary' => $summary,
                      'alias' => $alias,
                );
        if (isset($_SERVER['argv'][4])) {
            $args['alias'] = $_SERVER['argv'][4];
        }
        if (isset($_SERVER['argv'][5])) {
            $args['file'] = $_SERVER['argv'][5];
        }
        $this->pyrusCreate(null, $args);
    }

    function pyrusCreate($frontend, $args)
    {
        if (!isset($args['file'])) {
            $args['file'] = getcwd() . '/channel.xml';
        }
        $this->dir = dirname($args['file']);
        $this->channel = new Channel($args['name'],
                                     $args['summary'],
                                     $args['alias']);
        $scs = new Main($this->channel,
            $this->dir);
        $scs->saveChannel();
        echo '
Created '.$args['name'].'
      | ./channel.xml
      | ./rest/
      | ./get/'.PHP_EOL;
        return true;
    }
    
    public function printCreateUsage()
    {
        echo '
Usage: pearscs create pear.example.com summary [alias] [./channel.xml]
    This will create a file named channel.xml for the pear channel pear.example.com.
    
    summary  This is the a description for the channel.
    alias    Channel alias pear users can use as a shorthand.
    filename Path to where to create the channel.xml file. Current directory will be
             used by default.
    
';
    }
    
    public function printUpdateUsage()
    {
        echo '
Usage: pearscs update maintainer [channel.xml]
    This will update all releases within the /get/ directory.
';
    }
    
    public function printUsage()
    {
        echo '
Usage: pearscs create|update|release|... [option]
    Commands:
        update [channel.xml]                  Update the channel xml files.
        create pear.example.com summary [...] Create a new channel.
        add-maintainer handle                 Add a maintainer.
        add-category category [description]   Add a category.
        release package.tgz maintainer        Release package.
        categorize package category           Categorize a package.
';
    }
}

?>