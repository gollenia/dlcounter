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
            $file['id'] = $item['id'];
            $file['size'] = $item['size'];

            $file['downloads'] = p_get_metadata($item['id'], 'downloads');
            
            $file['last_download'] = p_get_metadata($item['id'], 'last_download');
            array_push($result, $file);
        }

        $table = "<table class='shadow-md text-sm bg-white' x-data='sortDownloadTable()' class='text-sm' width='100%'>";
        
        $table .= "<thead class='bg-gray-200'><tr><th align='left' class='p-2 cursor-pointer' @click='sortByColumn'><i class='material-icons text-sm mr-2'>sort</i>Datei</th><th class='p-2 cursor-pointer' align='left' @click='sortByColumn'>Ort</th><th align='left'  class='p-2 cursor-pointer' @click='sortByColumn'>Erweiterung</th><th align='left' class='p-2 cursor-pointer' @click='sortByColumn'>Letzter Zugriff</th><th class='p-2 cursor-pointer' align='right' @click='sortByColumn'>Downloads</th></tr></thead><tbody x-ref='tbody'>";
        
        foreach( $result as $file ){
            $table .= "<tr><th class='p-2 whitespace-nowrap' align='left'><a class='wikilink1' href='" . ml($file['id']) . "'><i class='material-icons text-sm mr-2'>file_download</i></a>" . $file['file'] . "</th>".
                "<td class='p-2'><a class='wikilink1' href='" . wl($file['ns']) . "'>" . $file["ns"] . "</a></td><td class='p-2'>" . $file["extension"] . "</td><td class='p-2'>" . $file["last_download"] . "</td><td  class='p-2' align='right'>" . $file["downloads"] . "</td></tr>";
        }
        $table .= "</tbody></table>";

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
