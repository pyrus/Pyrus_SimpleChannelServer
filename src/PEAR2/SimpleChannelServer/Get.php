<?php
namespace PEAR2\SimpleChannelServer;
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
        $parts = pathinfo($new);
        $cloner = new \PEAR2\Pyrus\Package\Cloner($new, $this->get);
        if (!file_exists($this->get.$parts['filename'].'.tar')) {
            $cloner->toTar();
        }
        if (!file_exists($this->get.$parts['filename'].'.tgz')) {
            $cloner->toTgz();
        }
        if (!file_exists($this->get.$parts['filename'].'.phar')) {
            $cloner->toPhar();
        }
        if (!file_exists($this->get.$parts['filename'].'.zip')) {
            $cloner->toZip();
        }
        return true; 
    }

    function deleteRelease(\PEAR2\Pyrus\Package $release)
    {

    }
}
