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

            if( isset($_POST['venicemodule_export']) ){
                $this->exportToPostGis();
            }
            if( isset($_POST['venicemodule_update']) ){
                $this->updateFromPostGis();
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


    private function exportToPostGis(){
        echo 'exporting to postgis';

        $store      = $this->_owApp->erfurt->getStore();
        $graph      = (string)$this->_owApp->selectedModel;

        $sparqlQuery = new Erfurt_Sparql_SimpleQuery();
        $sparqlQuery->setProloguePart('SELECT ?s ?o');
        $sparqlQuery->addFrom($graph);
        $sparqlQuery->setWherePart('{ ?s <http://www.opengis.net/ont/geosparql#geometry> ?o . }');

        // get the VTM config to connect to PostGIS
        require('../../../includes/config.php');

        // create the table structure
        $query_1 = file_get_contents('extensions/venicemodule/sql/schema_for_export.sql');
        $db_postgis->exec( $query_1 );

        //get all geoentities
        $sparqlQuery = new Erfurt_Sparql_SimpleQuery();
        $sparqlQuery->setProloguePart('SELECT ?s, ?geom, ?label, ?parent, ?type');
        $sparqlQuery->addFrom($graph);
        $sparqlQuery->setWherePart('{ 
            ?s <http://www.opengis.net/ont/geosparql#geometry> ?geom . 
            ?s a ?type .
            ?s <http://www.w3.org/2000/01/rdf-schema#label> ?label .
            OPTIONAL{ ?s <http://dhlab.epfl.ch/vtm/#belongs_to> ?parent . }
        }');

        $result = $store->sparqlQuery($sparqlQuery, array('result_format' => 'extended'));
        foreach ($result['results']['bindings'] as $stmt) {
            $db_postgis->exec( 'INSERT INTO "semantic"."features"(resource, type, label, parent, geom)
                VALUES(\''.$stmt['s']['value'].'\',\''.$stmt['type']['value'].'\',\''.$stmt['label']['value'].'\',\''.$stmt['parent']['value'].'\',ST_GeomFromText(\''.$stmt['geom']['value'].'\',4326))' );
        }

        $db_postgis->exec( 'VACUUM;' );
        


    }
    private function updateFromPostGis(){
        echo 'updating from postgis';

        $versioning = $this->_owApp->erfurt->getVersioning();
        $store      = $this->_owApp->erfurt->getStore();
        $graph      = (string)$this->_owApp->selectedModel;


        //delete all the geometries
        $sparqlQuery = new Erfurt_Sparql_SimpleQuery();
        $sparqlQuery->setProloguePart('SELECT ?s, ?geom');
        $sparqlQuery->addFrom($graph);
        $sparqlQuery->setWherePart('{ ?s <http://www.opengis.net/ont/geosparql#geometry> ?geom . }');

        $result = $store->sparqlQuery($sparqlQuery, array('result_format' => 'extended'));
        // transform them to statement array to be compatible with store methods
        foreach ($result['results']['bindings'] as $stmt) {

            $resource = $stmt['s']['value'];
            $geom = $stmt['geom']['value'];

            //TODO : escape this properly
            $postgis_rows = $db_postgis->query( 'SELECT ST_AsText(geom) as wkt FROM "semantic"."features" WHERE resource = \''.$resource.'\'');
            $postgis_geom = $postgis_rows[0]['wkt'];

            // if the geom is the same in the postgis db as in the triplestore, we don't need to do anything
            if($postgis_geom == $geom){
                continue;
            }

            // REMOVE OLD STATEMENT
            // start action
            $actionSpec                = array();
            $actionSpec['type']        = 21;
            $actionSpec['modeluri']    = $graph;
            $actionSpec['resourceuri'] = $resource;

            $versioning->startAction($actionSpec);

            // do action
            $store->deleteMatchingStatements($graph, $resource, 'http://www.opengis.net/ont/geosparql#geometry', null);
            $store->addStatement($graph, $resource, 'http://www.opengis.net/ont/geosparql#geometry', ['value'=>$postgis_geom,'type'=>"<http://www.openlinksw.com/schemas/virtrdf#Geometry>"]);

            // stopping action
            $versioning->endAction();

        }


        /*
        //$resource   = $this->_owApp->selectedResource;


        // action spec for versioning
        $actionSpec                = array();
        $actionSpec['type']        = 20;
        $actionSpec['modeluri']    = (string)$graph;
        $actionSpec['resourceuri'] = $resource;

        // starting action
        $versioning->startAction($actionSpec);

        
        // stopping action
        $versioning->endAction();
        */

    }

    
}
