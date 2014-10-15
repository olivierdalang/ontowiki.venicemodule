<?php
/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2012, {@link http://aksw.org AKSW}
 * @license   http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * OntoWiki module – Venicemodule
 *
 * this is the main venicemodule module
 *
 * @category OntoWiki
 * @package  Extensions_Venicemodule
 * @author   {@link http://sebastian.tramp.name Sebastian Tramp}
 */
class VenicemoduleModule extends OntoWiki_Module
{
    protected $_session = null;

    public function init()
    {
        $this->_session = $this->_owApp->session;

        if( isset($_POST) ){

            if( isset($_POST['venicemodule_update']) ){
                echo 'update';
            }
            if( isset($_POST['venicemodule_create3D']) ){
                echo 'create3D';
            }

        }
    }

    public function getTitle()
    {
        return "Venice Time Machine - Operations";
    }

    public function getMenu()
    {
        return new OntoWiki_Menu();
    }

    public function getContents()
    {     
        return $this->render('venicemodule');
    }

    public function shouldShow()
    {
        return true;
    }

    
}
