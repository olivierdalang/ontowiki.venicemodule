<?php
/**
 * This loads the cadastral sources from the postgis database into the virtuoso database
 * */


/************************************/
/************************************/
/*******  IMPORTING THE DATA  *******/
/************************************/
/************************************/

$vtm->prepareBatchSparql('
PREFIX geo: <http://www.opengis.net/ont/geosparql#>
PREFIX virtrdf: <http://www.openlinksw.com/schemas/virtrdf#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
PREFIX srdf: <ttp://strdf.di.uoa.gr/ontology#>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
PREFIX : <http://dhlab.epfl.ch/vtm/#>
INSERT IN GRAPH <http://dhlab.epfl.ch/vtm/>{','}',100);



/************************************************************/
/***** CREATE 4 BUILDIGS THAT DON'T EXIST TODAY ANYMORE *****/
/************************************************************/

$vtm->batchSparql("
:building_entity_manual_1      a                           :class_building .
:building_entity_manual_1      :shape_defined_by           :austrian_italian_lot_source_89 .
:building_entity_manual_1      rdfs:label                  \"A building that was demolished\" .
:building_entity_manual_1      :doesnt_appear_on           :source_ITB_UN_VOL_2009 .

:building_entity_manual_2      a                           :class_building .
:building_entity_manual_2      :shape_defined_by           :austrian_italian_lot_source_90 .
:building_entity_manual_2      rdfs:label                  \"A building that was demolished\" .
:building_entity_manual_2      :doesnt_appear_on           :source_ITB_UN_VOL_2009 .

:building_entity_manual_3      a                           :class_building .
:building_entity_manual_3      :shape_defined_by           :austrian_italian_lot_source_91 .
:building_entity_manual_3      rdfs:label                  \"A building that was demolished\" .
:building_entity_manual_3      :doesnt_appear_on           :source_ITB_UN_VOL_2009 .

:building_entity_manual_4      a                           :class_building .
:building_entity_manual_4      :shape_defined_by           :austrian_italian_lot_source_92 .
:building_entity_manual_4      rdfs:label                  \"A building that was demolished\" .
:building_entity_manual_4      :doesnt_appear_on           :source_ITB_UN_VOL_2009 .
");


/*************************************/
/***** ADDS SHAPES TO BUILDINGS  *****/
/*************************************/

$vtm->batchSparql("
:building_entity_95912      :shape_defined_by           :austrian_italian_lot_source_100 .
    ");

$vtm->batchSparql("
:building_entity_96110      :shape_defined_by           :austrian_italian_lot_source_113 .
:building_entity_96110      :shape_defined_by           :austrian_italian_lot_source_112 .
:building_entity_96110      :shape_defined_by           :austrian_italian_lot_source_115 .
");



/********************************************/
/***** MERGE PONTE RIALTO TO A BUILDING *****/
/********************************************/




$vtm->sparql('
DELETE FROM <http://dhlab.epfl.ch/vtm/> {
    ?s ?p ?o
}
WHERE {
    ?s ?p ?o
    FILTER( ?s=<http://dhlab.epfl.ch/vtm/#building_entity_92151> )
    FILTER( ?p=<http://www.w3.org/2000/01/rdf-schema#label> )
}
');
$vtm->batchSparql('<http://dhlab.epfl.ch/vtm/#building_entity_92151>      <http://www.w3.org/2000/01/rdf-schema#label>        "Ponte Rialto" .');

$rmv = ['94466','92135','96905','91907','85222','92444','93891'];
foreach($rmv as $r){
    $vtm->sparql('
    DELETE FROM <http://dhlab.epfl.ch/vtm/> {
        ?s ?p ?o
    }
    WHERE {
        ?s ?p ?o
        FILTER( ?s=<http://dhlab.epfl.ch/vtm/#building_entity_'.$r.'> )
    }
    ');
    $vtm->batchSparql(':building_entity_92151      :shape_defined_by           :itb_lot_source_'.$r.' .');
}

//commit pending queries
$vtm->endBatchSparql();

