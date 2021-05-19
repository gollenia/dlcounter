<?php
/**
 * DokuWiki Plugin dlcounter (Action Component)
 *
 * records and displays download counts for files with specified extensions in the media library
 *
 * @author Phil Ide <phil@pbih.eu>
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) {
    die();
}

class action_plugin_dlcounter extends DokuWiki_Action_Plugin
{

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     *
     * @return void
     */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('MEDIA_SENDFILE', 'BEFORE', $this, 'handle_media_sendfile');
   
    }

    /**
     * [Custom event handler which performs action]
     *
     * Called for event:
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     *
     * @return void
     */
    public function handle_media_sendfile(Doku_Event $event, $param)
    {
        $data = $event->data;
        $extension = str_replace(' ', '', strtolower($this->getConf('extensions')) );
        $extension = explode( ",", $extension );
        $ok = true;

        // Deprecated: Write to file
        if( in_array( strtolower($data['ext']), $extension ) ){
            $count = p_get_metadata($data['media'], 'downloads');
        
        
            if($count) {
                $count++;
            } else {
                $count = 1;
            }
            
            p_set_metadata($data['media'], ['downloads' => $count]);
            p_set_metadata($data['media'], ['last_download' => date("Y-m-d")]);
            idx_addPage($data['media'], false, true);
            
            if(p_get_metadata($data['media'], 'download_stats') == null) {
                p_set_metadata($data['media'], ['download_stats' => date("Y-m-d")]);
                error_log("nix da: " . p_get_metadata($data['media'], 'download_stats'));
            } else {
                var_dump("schon was da");
                $stats = p_get_metadata($data['media'], 'download_stats') . "," . date("Y-m-d");
                p_set_metadata($data['media'], ["download_stats" => $stats]);
                error_log("was da: " . p_get_metadata($data['media'], $stats));
            };
        }

        // Set Metadata
        
        
    }

}

