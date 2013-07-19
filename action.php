<?php
/**
 * DokuWiki Plugin autostartpage (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Charles Knight <charles@rabidaudio.com>
 */
 
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class action_plugin_autostartpage extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler &$controller) {
       $controller->register_hook('IO_NAMESPACE_CREATED', 'BEFORE', $this, 'autostartpage_handle');
    }

    /**
     * [Custom event handler which performs action]
     *
     * @author Charles Knight, charles@rabidaudio.com
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */

    public function autostartpage_handle(Doku_Event &$event, $param) {
        global $conf;
        global $INFO;

        $templatefile = wikiFN($this->getConf('templatefile'), '', false);
        if(@file_exists($templatefile)){
            $wikitext=io_readFile($templatefile);
        }

        $ns=$event->data[0];
        $ns_type=$event->data[1];
        if($ns_type === "pages" && $wikitext){
            /**
             *  This event is called before the page has been created, so we can't look to see if it is a start page
             *  the good news: we can create a start page, and if the user created the namespace with a start page,
             *  it will probably just overwrite ours.
             */
            $id=$ns.":".$conf['start'];
            $file=wikiFN($id);
            $silent=$this->getConf('silent');

            $goodns=preg_replace("/".$conf['sepchar']."/"," ",noNS($ns));
            $page=preg_replace("/".$conf['sepchar']."/"," ",noNS($id));
            $f=$conf['start'];

            /**THESE ARE THE CODES FOR TEMPLATES**/
            // @ID@         full ID of the page
            // @NS@         namespace of the page
            // @PAGE@       page name (ID without namespace and underscores replaced by spaces)
            // @!PAGE@      same as above but with the first character uppercased
            // @!!PAGE@     same as above but with the first character of all words uppercased
            // @!PAGE!@     same as above but with all characters uppercased
            // @FILE@       page name (ID without namespace, underscores kept as is)
            // @!FILE@      same as above but with the first character uppercased
            // @!FILE!@     same as above but with all characters uppercased
            // @USER@       ID of user who is creating the page
            // @NAME@       name of user who is creating the page
            // @MAIL@       mail address of user who is creating the page
            // @DATE@       date and time when edit session started
            /**PLUS WE ADDED THESE**/
            // @!NS@        namespace of the page (with spaces) but with the first character uppercased
            // @!!NS@       namespace of the page (with spaces) but with the first character of all words uppercased
            // @!!NS!@      namespace of the page (with spaces) but with all characters uppercased
            /**TODO: ADD THESE, TOO**/
            // %a %d-%m-%y etc.     e.g. Thu 06-12-12. Strftime placeholders are replaced by page creation time
            // %%           a literal % character appears in your template 
            
            $wikitext=preg_replace("/@NS@/", $ns, $wikitext);
            $wikitext=preg_replace("/@!NS@/", ucfirst($goodns), $wikitext);
            $wikitext=preg_replace("/@!!NS@/", ucwords($goodns), $wikitext);
            $wikitext=preg_replace("/@!!NS!@/", strtoupper($goodns), $wikitext);
            $wikitext=preg_replace("/@ID@/", $id, $wikitext);
            $wikitext=preg_replace("/@PAGE@/",$page, $wikitext);
            $wikitext=preg_replace("/@!PAGE@/",ucfirst($page), $wikitext);
            $wikitext=preg_replace("/@!!PAGE@/",$uupage=ucwords($page), $wikitext);
            $wikitext=preg_replace("/@!PAGE!@/",strtoupper($page), $wikitext);
            $wikitext=preg_replace("/@FILE@/",$f, $wikitext);
            $wikitext=preg_replace("/@!FILE@/",ucfirst($f), $wikitext);
            $wikitext=preg_replace("/@!FILE!@/",strtoupper($f), $wikitext);
            $wikitext=preg_replace("/@USER@/",$_SERVER['REMOTE_USER'], $wikitext);
            $wikitext=preg_replace("/@NAME@/",$INFO['userinfo']['name'], $wikitext);
            $wikitext=preg_replace("/@MAIL@/",$INFO['userinfo']['mail'], $wikitext);
            $wikitext=preg_replace("/@DATE@/",strftime("%D"), $wikitext);
            
            if(!@file_exists($file)){
                /**
                 *  really, we should trigger IO_WIKIPAGE_WRITE, but if we do that, it will
                 *  create a new namespace, causing an infinite loop! instead, we just write
                 *  the file directly and hope for the best. If you can find a way to do this
                 *  by triggering IO_WIKIPAGE_WRITE, then by all means, do it.
                 */
                $ok = io_saveFile($file, $wikitext, false);

                if ($ok and !$silent){
                    msg($this->getLang('createmsg').' <a href="'.wl($id).'">'.noNS($id).'</a>', 1);
                }elseif (!$silent){
                    msg($this->getLang('failmsg'), -1);
                }
            }
        }elseif (!$wikitext and !$silent){
                msg($this->getLang('templatemissing'));
        }
    }
}

// vim:ts=4:sw=4:et: