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


// get the VTM config to connect to PostGIS
require('../../../includes/config.php');

class VenicemoduleModule extends OntoWiki_Module
{
    protected $_session = null;
    private $vtm;

    public function init()
    {
        $this->_session = $this->_owApp->session;
        $this->vtm = new VTM();

        if( isset($_POST) ){
            if( isset($_POST['venicemodule_import']) ){
                $this->importFromPostGis();
            }
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

    private function importFromPostGis(){
        echo 'Initial import from postgis<br/>';

        //////////////////////////////
        // REMOVING ALL THE TRIPLES //
        //////////////////////////////

        echo "Removing all the triples...<br/>";

        $this->vtm->sparql('
            WITH <http://dhlab.epfl.ch/vtm/>
            DELETE {
               ?a  ?b  ?c
            }
            WHERE{
               ?a  ?b  ?c
            }
        ');

        // We delete the whole history for this model
        $versioning = $this->_owApp->erfurt->getVersioning()->deleteHistoryForModel( (string)$this->_owApp->selectedModel );

        




        //////////////////////////
        // IMPORTING EVERYTHING //
        //////////////////////////


        $tstart = microtime(true);
        $tlast = $tstart;

        /*******  IMPORTING THE ONTOLOGY  *******/

        $this->vtm->sparql( file_get_contents('../../../sparql/import-ontology.sparql') );
        echo "Created the Ontology in ".round(microtime(true)-$tlast,5)." seconds<br/>";
        $tlast = microtime(true);

        /*******  IMPORTING THE SOURCES  *******/

        $this->vtm->sparql( file_get_contents('../../../sparql/import-sources.sparql') );
        echo "Created the Sources in ".round(microtime(true)-$tlast,5)." seconds<br/>";
        $tlast = microtime(true);


        /*******  IMPORTING FROM POSTGIS  *******/

        include("../../../sparql/import-from_postgis.sparql.php");
        echo "Importing from postGIS in ".round(microtime(true)-$tlast,5)." seconds<br/>";
        $tlast = microtime(true);


        /*******  IMPORTING THE MODELS  *******/

        $this->vtm->sparql( file_get_contents('../../../sparql/import-models.sparql') );
        echo "Created the models in ".round(microtime(true)-$tlast,5)." seconds<br/>";
        $tlast = microtime(true);


        /*******  ADDING MANUAL CHANGES  *******/

        $this->vtm->sparql( file_get_contents('../../../sparql/import-manual_changes.sparql') );
        echo "Importing manual changes in ".round(microtime(true)-$tlast,5)." seconds<br/>";
        $tlast = microtime(true);

        echo "Finished importation in ".round(microtime(true)-$tstart,5)." seconds<br/>";




        $this->vtm->postgis->exec( 'VACUUM;' );
        


    }


    private function exportToPostGis(){
        echo 'exporting to postgis';

        $store      = $this->_owApp->erfurt->getStore();
        $graph      = (string)$this->_owApp->selectedModel;

        $sparqlQuery = new Erfurt_Sparql_SimpleQuery();
        $sparqlQuery->setProloguePart('SELECT ?s ?o');
        $sparqlQuery->addFrom($graph);
        $sparqlQuery->setWherePart('{ ?s <http://www.opengis.net/ont/geosparql#geometry> ?o . }');


        // create the table structure
        $query_1 = file_get_contents('extensions/venicemodule/sql/schema_for_export.sql');
        $this->vtm->postgis->exec( $query_1 );

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
            $this->vtm->postgis->exec( 'INSERT INTO "semantic"."features"(resource, type, label, parent, geom)
                VALUES(\''.$stmt['s']['value'].'\',\''.$stmt['type']['value'].'\',\''.$stmt['label']['value'].'\',\''.$stmt['parent']['value'].'\',ST_GeomFromText(\''.$stmt['geom']['value'].'\',4326))' );
        }

        $this->vtm->postgis->exec( 'VACUUM;' );
        


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
            $postgis_rows = $this->vtm->postgis->query( 'SELECT ST_AsText(geom) as wkt FROM "semantic"."features" WHERE resource = \''.$resource.'\'');
            $postgis_geom = $postgis_rows->fetch()['wkt'];

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

            // recreate the spatial index
            $this->vtm->virtuoso_query("
                INSERT INTO RDF_QUAD (g, s, p, o)
                VALUES (
                     iri_to_id('$graph'),
                     iri_to_id('$resource'),
                     iri_to_id ('http://www.opengis.net/ont/geosparql#geometry'),
                     DB.DBA.rdf_geo_add (rdf_box (st_geomfromtext ('$postgis_geom'), 256, 257, 0, 1 ))
                )");

        }


    }

    
}
