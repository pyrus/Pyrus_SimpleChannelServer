<?php
/**
 * Channel writer for simple channel server.
 *
 * @category Developer
 * @package  PEAR2_SimpleChannelServer
 * @author   Brett Bieber <brett.bieber@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link     http://svn.php.net/viewvc/pear2/sandbox/SimpleChannelServer/
 */
namespace pear2\SimpleChannelServer;
class Channel extends \pear2\Pyrus\ChannelFile
{
    
    function __construct($name, $summary, $suggestedalias = null, $restpath = 'rest/')
    {
        parent::__construct('<?xml version="1.0" encoding="UTF-8"?>
<channel version="1.0" xmlns="http://pear.php.net/channel-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="http://pear.php.net/dtd/channel-1.0 http://pear.php.net/dtd/channel-1.0.xsd">
 <name>pear2.php.net</name>
 <suggestedalias>salty</suggestedalias>
 <summary>Simple PEAR Channel</summary>
 <servers>
  <primary>
   <rest>
    <baseurl type="REST1.0">http://foo/rest/</baseurl>
    <baseurl type="REST1.1">http://foo/rest/</baseurl>
    <baseurl type="REST1.3">http://foo/rest/</baseurl>
   </rest>
  </primary>
 </servers>
</channel>', true);

        $this->name = $name;
        $this->summary = $summary;
        $this->resetREST();
        $this->protocols->rest['REST1.0']->baseurl = 'http://'.$name.'/'.$restpath;
        $this->protocols->rest['REST1.1']->baseurl = 'http://'.$name.'/'.$restpath;
        $this->protocols->rest['REST1.2']->baseurl = 'http://'.$name.'/'.$restpath;
        $this->protocols->rest['REST1.3']->baseurl = 'http://'.$name.'/'.$restpath;
        if (!empty($suggestedalias)) {
            $this->alias = $suggestedalias;
        } else {
            $this->alias = self::guessChannelAlias($name);
        }
    }
    
    function getChannelFile()
    {
        return $this->__toString();
    }
    
    public static function guessChannelAlias($name)
    {
        if (strpos($name, '/') !== false) {
            // www.server.com/fish,simplecas.googlecode.com/svn
            $alias = explode('/', $name);
            if ($alias[count($alias)-1] != 'pear'
                && $alias[count($alias)-1] != 'svn') {
                // return fish
                return $alias[count($alias)-1];
            }
        }
        // Something like pear.saltybeagle.com,mychannel.com,localhost,simplecas.googlecode.com/svn
        $alias = explode('.', $name);
        if (count($alias) > 2
            && $alias[1] != 'googlecode') {
            // return saltybeagle
            return $alias[1];
        }
        // return mychannel or localhost or simplecas
        return $alias[0];
    }
}
