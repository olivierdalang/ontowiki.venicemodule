<?php
/**
 * This loads the cadastral sources from the postgis database into the virtuoso database
 * */


/************************************/
/************************************/
/*******  IMPORTING THE DATA  *******/
/************************************/
/************************************/

$vtm = new VTM();

$vtm->prepareBatchSparql('
PREFIX geo: <http://www.opengis.net/ont/geosparql#>
PREFIX virtrdf: <http://www.openlinksw.com/schemas/virtrdf#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
PREFIX srdf: <ttp://strdf.di.uoa.gr/ontology#>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
PREFIX : <http://dhlab.epfl.ch/vtm/#>
INSERT IN GRAPH <http://dhlab.epfl.ch/vtm/>{','}',100);



/************************************/
/***** IMPORT THE UGHI CADASTER *****/
/************************************/

$vtm->batchSparql('
:test_model_parent_A    a                   :class_source .
:test_model_parent_A    :date               "1600"^^xsd:integer .
:test_model_parent_A    rdfs:label          "Historical model of the original Fondaco dei Tedeschi (PLACEHOLDER FOR TESTING ONLY !)" .

:test_model_A    a                   :class_3d_model .
:test_model_A    :belongs_to         :test_model_parent_A .
:test_model_A    rdfs:label          "Test 3D model A" .
:test_model_A    :model              "http://dhlabpc2.epfl.ch/models/sample/horse.js" .
:test_model_A    :model_origin       "POINT(12.337051 45.439)"^^virtrdf:Geometry .
:test_model_A    :model_rotation     0 .
:test_model_A 	 geo:geometry    	 "MULTIPOLYGON(((12.337051 45.438453,12.33695 45.438025,12.336299 45.438129,12.336457 45.438539,12.337051 45.438453,12.337051 45.438453)))"^^virtrdf:Geometry .

:test_model_parent_B    a                   :class_source .
:test_model_parent_B    :date               "2000"^^xsd:integer .
:test_model_parent_B    rdfs:label          "Model of the Fondaco dei Tedeschi after the Louis Vuitton project (PLACEHOLDER FOR TESTING ONLY !)" .
:test_model_parent_B    :makes_obsolete     :test_model_parent_A .

:test_model_B    a                   :class_3d_model .
:test_model_B    :belongs_to         :test_model_parent_B .
:test_model_B    rdfs:label          "Test 3D model B" .
:test_model_B    :model              "http://dhlabpc2.epfl.ch/models/sample/tree.js" .
:test_model_B    :model_origin       "POINT(12.337051 45.438453)"^^virtrdf:Geometry .
:test_model_B    :model_rotation     0 .
:test_model_B 	 geo:geometry    	 "MULTIPOLYGON(((12.337051 45.438453,12.336892 45.4382,12.336299 45.438129,12.336457 45.438539,12.337051 45.438453,12.337051 45.438453)))"^^virtrdf:Geometry .


:building_entity_91552 :model_defined_by :test_model_A .
:building_entity_91552 :model_defined_by :test_model_B .
:building_entity_91552 :shape_defined_by :test_model_A .
:building_entity_91552 :shape_defined_by :test_model_B .
');

//commit pending queries
$vtm->endBatchSparql();

