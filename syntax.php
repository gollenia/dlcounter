<?php
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

/**
 * DokuWiki Plugin dlcounter (Syntax Component)
 *
 * @author Phil Ide <phil@pbih.eu>
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */

class syntax_plugin_dlcounter extends DokuWiki_Syntax_Plugin {

    /**
     * What kind of syntax are we?
     */
    function getType() {
        return 'substition';
    }

    /**
     * What about paragraphs?
     */
    function getPType() {
        return 'normal';
    }

    /**
     * Where to sort in?
     */
    function getSort() {
        return 155;
    }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{dlcounter>[^\}]+\}\}', $mode, 'plugin_dlcounter');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler) {
        if (isset($_REQUEST['comment'])) {
            return false;
        }
        $command = trim(substr($match, 12 ,-2));
        $x = explode('?', $command);
        $command = $x[0];
        $params = explode(' ', $x[1]);

        $data = array(
                    'command' => $command,
                    'file'    => '',
                    'sort'    => 'none',
                    'strip'   => false,
                    'align'   => 'right',
                    'minwidth' => 0,
                    'cpad'    => 1,
                    'halign'  => 'center',
                    'bold'    => 'b',
                    'header'  => true,
                    'htext'   => 'Downloads',
                    'exclude' => ''
                );

        foreach( $params as $item ){
            // switch turns out to be buggy for multiple iterations - grrrrr!
            if( $item == 'sort' )          $data['sort'] = $item;
            else if( $item == 'rsort' )    $data['sort'] = $item;
            else if( $item == 'strip' )    $data['strip'] = true;
            else if( $item == 'left' )     $data['align'] = $item;
            else if( $item == 'center' )   $data['align'] = $item;
            else if( $item == 'right' )    $data['align'] = $item;
            else if( $item == 'hleft' )    $data['halign'] = 'left';
            else if( $item == 'hcenter' )  $data['halign'] = 'center';
            else if( $item == 'hright' )   $data['halign'] = 'right';
            else if( $item == 'nobold' )   $data['bold'] = 'nobold';
            else if( $item == 'noheader' ) $data['header'] = false;
            else if( substr( $item, 0, 6) == 'htext=' ){
                $data['htext'] = explode('"', $x[1])[1];
            }
            else if( substr( $item, 0, 9) == 'minwidth=' ){
                $data['minwidth'] = explode('=', $item)[1];
            }
            else if( substr( $item, 0, 5) == 'cpad=' ){
                $data['cpad'] = explode('=', $item)[1];
            }
            else $data['file'] = $item;
        }
        return $data;
    }


    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $data) {
        
        global $conf;
        $opt=array('depth'=>0);
        $res = array(); // search result
        search($res, $conf['mediadir'], 'search_media', $opt);
        $extensions = str_replace(' ', '', strtolower($this->getConf('extensions')) );
        $extensions = explode( ",", $extensions );
        
        // prepare return array
        $result = [];
        foreach ($res as $item) {
            $file = [];
            if(!in_array(pathinfo($item['file'], PATHINFO_EXTENSION), $extensions)) {
                
                continue;
            }
            $file['extension'] = pathinfo($item['file'], PATHINFO_EXTENSION);
            $ns = explode(":",$item['id']);
            array_pop($ns);
            
            $file['ns'] = implode(":",$ns);
            $file['file'] = $item['file'];
            $file['size'] = $item['size'];

            $file['downloads'] = p_get_metadata($item['id'], 'downloads');
            
            $file['last_download'] = p_get_metadata($item['id'], 'last_download');
            array_push($result, $file);
        }

        $command = $data['command'];

        $id  = array_column($result, 'id');
        $downloads = array_column($data, 'downloads');

        array_multisort($downloads, SORT_DESC, $id, SORT_ASC, $result);

            $table = "<table width='100%'>";
            
            $table .= "<tr><th>Datei</th><th>Ort</th><th>Erweiterung</th><th>Größe</th><th>Letzter Download</th><th>Downloads Gesamt</th></tr>";
            
            foreach( $result as $file ){
                $table .= "<tr><th align='left'>" . $file['file'] . "</th>".
                    "<td>" . $file["ns"] . "</td><td>" . $file["extension"] . "</td><td>" . $file["size"] . "</td><td>" . $file["last_download"] . "</td><td>" . $file["downloads"] . "</td></tr>";
            }
            $table .= "</table>";
            $renderer->doc .= $table;
            
        
        return true;
    }


    
    function dlcounter_switchKeys( $arr, $back2Front ){
        $keys = array_keys( $arr );
        for( $i = 0; $i < count($keys); $i++ ){
            if( $back2Front ) $keys[$i] = $this->switchKeyHelperA( $keys[$i] );
            else $keys[$i] = $this->switchKeyHelperB( $keys[$i] );
        }
        return array_combine( $keys, $arr );
    }
    
    // move the fileame to the front of the path
    function switchKeyHelperA( $v ){
        $a = explode(':', $v);
        $f = array_pop($a);
        array_unshift( $a, $f );
        return implode(':', $a );
    }
    
    // move the filename from the front of the path to the end
    function switchKeyHelperB( $v ){
        $a = explode(':', $v);
        $f = array_shift($a);
        array_push( $a, $f );
        return implode(':', $a );
    }

}
