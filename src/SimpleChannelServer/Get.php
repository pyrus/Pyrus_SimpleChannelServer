<?php
namespace pear2\SimpleChannelServer;
class Get
{
    protected $get;

    protected $pyruspath;

    function __construct($savepath, $pyruspath)
    {
        $this->get = $savepath;
        $this->pyruspath = $pyruspath;
        if (!file_exists($savepath)) {
            if (!@mkdir($savepath, 0777, true)) {
                throw new Exception('Could not initialize' .
                    'GET storage directory "' . $savepath . '"');
            }
        }
    }

    function saveRelease($new, $releaser)
    {
        $cloner = new \pear2\Pyrus\Package\Cloner($new, $this->get);
        $cloner->toTar();
        $cloner->toTgz();
        $cloner->toPhar();
        $cloner->toZip();
        return true; 
    }

    function deleteRelease(\pear2\Pyrus\Package $release)
    {

    }
}
