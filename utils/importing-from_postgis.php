<?php
/**
 * This loads the cadastral sources from the postgis database into the virtuoso database
 * */

try{

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




//TODO : when the entity is the same as the source (lots, isole...), merge them (not sure?)

$filter = '';
$filter = 'WHERE geom && ST_Transform(ST_MakeEnvelope(12.3320,45.435,12.3375,45.440,4326),3004)'; //uncomment this to restrict to rialto


/************************************/
/***** IMPORT THE UGHI CADASTER *****/
/************************************/

$vtm->batchSparql('
:source_Ughi_Map    a                   :class_source .
:source_Ughi_Map    rdfs:label          "Ughi Map" .
:source_Ughi_Map    :date               "1729"^^xsd:integer .
');
foreach($vtm->postgis->query("SELECT *, ST_AsText(ST_Transform(ST_Force_2D(geom),4326)) as wkt FROM sources.\"digitized_Ughi_Cadastre\" $filter") as $i=>$row){
   $wkt = $row['wkt'];
   $nbr = $row['id'];
   $src = ':ughi_lot_source_'.$nbr;
   //$src = ':'.uniqid();
   $vtm->batchSparql("$src      a                           :class_lot .");
   $vtm->batchSparql("$src      :belongs_to                 :source_Ughi_Map .");
   $vtm->batchSparql("$src      geo:geometry                \"$wkt\"^^virtrdf:Geometry .");
   $vtm->batchSparql("$src      rdfs:label                  \"Ughi lot #$nbr\" .");
}


/******************************************/
/***** IMPORT THE NAPOLEONIC CADASTER *****/
/******************************************/

$vtm->batchSparql('
:source_Napoleonic_Cadaster a                 :class_source .
:source_Napoleonic_Cadaster rdfs:label        "Napeoleonic Cadaster" .
:source_Napoleonic_Cadaster :date             "1808"^^xsd:integer .
:source_Napoleonic_Cadaster :makes_obsolete   :source_Ughi_Map .
');
foreach($vtm->postgis->query("SELECT *, ST_AsText(ST_Transform(ST_Force_2D(geom),4326)) as wkt FROM sources.\"digitized_Napoleonic_Cadastre\" $filter") as $i=>$row){
   $wkt = $row['wkt'];
   $nbr = $row['id'];
   $src = ':napoleonic_lot_source_'.$nbr;
   //$src = ':'.uniqid();
   $vtm->batchSparql("$src      a                           :class_lot .");
   $vtm->batchSparql("$src      :belongs_to                 :source_Napoleonic_Cadaster .");
   $vtm->batchSparql("$src      geo:geometry                \"$wkt\"^^virtrdf:Geometry .");
   $vtm->batchSparql("$src      rdfs:label                  \"Napoleonic Lot #$nbr\" .");
}


/****************************************/
/***** IMPORT THE AUSTRIAN CADASTER *****/
/****************************************/

$vtm->batchSparql('
:source_Austrian_Cadaster   a                    :class_source .
:source_Austrian_Cadaster   rdfs:label           "Austrian Cadaster" .
:source_Austrian_Cadaster   :date                "1838"^^xsd:integer .
:source_Austrian_Cadaster   :makes_obsolete      :source_Napoleonic_Cadaster .
');
foreach($vtm->postgis->query("SELECT *, ST_AsText(ST_Transform(ST_Force_2D(geom),4326)) as wkt FROM sources.\"digitized_Austrian_Cadastre\" $filter") as $i=>$row){
   $wkt = $row['wkt'];
   $nbr = $row['id'];
   $src = ':austrian_lot_source_'.$nbr;
   //$src = ':'.uniqid();
   $vtm->batchSparql("$src      a                           :class_lot .");
   $vtm->batchSparql("$src      :belongs_to                 :source_Austrian_Cadaster .");
   $vtm->batchSparql("$src      geo:geometry                \"$wkt\"^^virtrdf:Geometry .");
   $vtm->batchSparql("$src      rdfs:label                  \"Austrian lot #$nbr (source)\" .");
}

/****************************************/
/***** IMPORT THE AUSTRIAN ITALIAN CADASTER *****/
/****************************************/

$vtm->batchSparql('
:source_Austrian-Italian_Cadaster   a                    :class_source .
:source_Austrian-Italian_Cadaster   rdfs:label           "Austrian Italian Cadaster" .
:source_Austrian-Italian_Cadaster   :date                "1867"^^xsd:integer .
:source_Austrian-Italian_Cadaster   :makes_obsolete      :source_Austrian_Cadaster .
');
foreach($vtm->postgis->query("SELECT *, ST_AsText(ST_Transform(ST_Force_2D(geom),4326)) as wkt FROM sources.\"digitized_Austrian_Italian_Cadastre\" $filter") as $i=>$row){
   $wkt = $row['wkt'];
   $nbr = $row['id'];
   $src = ':austrian_italian_lot_source_'.$nbr;
   //$src = ':'.uniqid();
   $vtm->batchSparql("$src      a                           :class_lot .");
   $vtm->batchSparql("$src      :belongs_to                 :source_Austrian-Italian_Cadaster .");
   $vtm->batchSparql("$src      geo:geometry                \"$wkt\"^^virtrdf:Geometry .");
   $vtm->batchSparql("$src      rdfs:label                  \"Austrian-Italian lot #$nbr (source)\" .");
}


/********************************/
/******** IMPORT THE ITB ********/
/********************************/

/******** IMPORT UN_VOL ********/

$vtm->batchSparql('
:source_ITB_UN_VOL_2009    a               :class_source .
:source_ITB_UN_VOL_2009    :makes_obsolete  :source_Austrian-Italian_Cadaster .
:source_ITB_UN_VOL_2009    rdfs:label      "ITB UN_VOL 2009" .
:source_ITB_UN_VOL_2009    :date           "2009"^^xsd:integer . #this is repeated since the query isnt smart enough yet
');
$un_vol_query = "SELECT id, MAX(uv_qcolmo) as uv_qcolmo, ST_AsText(ST_Transform(ST_Multi(ST_Union(ST_Force_2D(geom))),4326)) as wkt FROM sources.\"imported_UN_VOL_merged\" $filter GROUP BY id";
foreach($vtm->postgis->query( $un_vol_query ) as $i=>$row){
   
   $wkt = $row['wkt'];
   $height = $row['uv_qcolmo'];
   $nbr = $row['id'];
   $src = ':itb_lot_source_'.$nbr;
   //$src = ':'.uniqid();
   $vtm->batchSparql("$src      a                           :class_lot .");
   $vtm->batchSparql("$src      :belongs_to                 :source_ITB_UN_VOL_2009 .");
   $vtm->batchSparql("$src      geo:geometry                \"$wkt\"^^virtrdf:Geometry .");
   $vtm->batchSparql("$src      :height                     \"$height\"^^xsd:float .");
   $vtm->batchSparql("$src      rdfs:label                  \"ITB lot #$nbr (source)\" .");

   //create the building entity
   $ent = ':building_entity_'.$nbr;
   //$ent = ':'.uniqid();
   $vtm->batchSparql("$ent      a                           :class_building .");
   $vtm->batchSparql("$ent      :shape_defined_by           $src .");
   $vtm->batchSparql("$ent      rdfs:label                  \"A building\" .");
}

/******** IMPORT TR_EME ********/

$vtm->batchSparql('
:source_ITB_TR_EME_2009    a               :class_source .
:source_ITB_TR_EME_2009    rdfs:label      "TR_EME" .
:source_ITB_TR_EME_2009    rdfs:label      "ITB TR_EME 2009" .
:source_ITB_TR_EME_2009    :date           "2009"^^xsd:integer . #this is repeated since the query isnt smart enough yet
');
$tr_eme_query = "SELECT id, ST_AsText(ST_Transform(ST_Multi(ST_Union(ST_Force_2D(geom))),4326)) as wkt FROM sources.\"imported_TR_EME_merged\" $filter GROUP BY id";
foreach($vtm->postgis->query($tr_eme_query) as $i=>$row){
   $wkt = $row['wkt'];
   $src = ':itb_isola_source_'.$i;
   //$src = ':'.uniqid();
   $vtm->batchSparql("$src      a                           :class_isola .");
   $vtm->batchSparql("$src      :belongs_to                 :source_ITB_TR_EME_2009 .");
   $vtm->batchSparql("$src      geo:geometry                \"$wkt\"^^virtrdf:Geometry .");
   $vtm->batchSparql("$src      rdfs:label                  \"ITB isola (source)\" .");
}

/******** IMPORT PONTE ********/

$vtm->batchSparql('
:source_ITB_PONTE_2009 a               :class_source .
:source_ITB_PONTE_2009 rdfs:label      "PONTE" .
:source_ITB_PONTE_2009    rdfs:label      "ITB PONTE 2009" .
:source_ITB_PONTE_2009    :date           "2009"^^xsd:integer . #this is repeated since the query isnt smart enough yet
');
foreach($vtm->postgis->query("SELECT *, ST_AsText(ST_Transform(ST_Force_2D(geom),4326)) as wkt FROM sources.\"imported_PONTE_merged\" $filter") as $i=>$row){
   $wkt = $row['wkt'];
   $src = ':itb_ponte_source_'.$i;
   //$src = ':'.uniqid();
   $vtm->batchSparql("$src      a                           :class_ponte .");
   $vtm->batchSparql("$src      :belongs_to                 :source_ITB_PONTE_2009 .");
   $vtm->batchSparql("$src      geo:geometry                \"$wkt\"^^virtrdf:Geometry .");
   $vtm->batchSparql("$src      rdfs:label                  \"ITB bridge (source)\" .");
}

/******** IMPORT LOCALITA ********/

$vtm->batchSparql('
:source_ITB_LOCALITA_2009  a               :class_source .
:source_ITB_LOCALITA_2009  rdfs:label      "LOCALITA" .
:source_ITB_LOCALITA_2009    rdfs:label      "ITB LOCALITA 2009" .
:source_ITB_LOCALITA_2009    :date           "2009"^^xsd:integer . #this is repeated since the query isnt smart enough yet
');
$localita_query = "SELECT id, MIN(loc_nome) as loc_nome, ST_AsText(ST_Transform(ST_Multi(ST_Union(ST_Force_2D(geom))),4326)) as wkt FROM sources.\"imported_LOCALITA_merged\" $filter GROUP BY id";
foreach($vtm->postgis->query( $localita_query ) as $i=>$row){
   $wkt = $row['wkt'];
   $loc_nome = $row['loc_nome'];
   $src = ':itb_localita_source_'.$i;
   //$src = ':'.uniqid();
   $vtm->batchSparql("$src      a                           :class_localita .");
   $vtm->batchSparql("$src      :belongs_to                 :source_ITB_LOCALITA_2009 .");
   $vtm->batchSparql("$src      geo:geometry                \"$wkt\"^^virtrdf:Geometry .");
   $vtm->batchSparql("$src      rdfs:label                  \"".addslashes($loc_nome)." (source)\" .");
}


$vtm->batchSparql('
:source_ITB_EL_STR_2009    a               :class_source .
:source_ITB_EL_STR_2009    rdfs:label      "EL_STR" .
:source_ITB_EL_STR_2009    rdfs:label      "ITB EL_STR 2009" .
:source_ITB_EL_STR_2009    :date           "2009"^^xsd:integer . #this is repeated since the query isnt smart enough yet
');

foreach($vtm->postgis->query("SELECT *, ST_AsText(ST_Transform(ST_Force_2D(geom),4326)) as wkt FROM sources.\"imported_EL_STR_merged\" $filter") as $i=>$row){
   $wkt = $row['wkt'];
   $src = ':itb_street_source_'.$i;
   //$src = ':'.uniqid();
   $vtm->batchSparql("$src      a                           :class_street .");
   $vtm->batchSparql("$src      :belongs_to                 :source_ITB_EL_STR_2009 .");
   $vtm->batchSparql("$src      geo:geometry                \"$wkt\"^^virtrdf:Geometry .");
   $vtm->batchSparql("$src      rdfs:label                  \"ITB Street (source)\" .");
}

$vtm->batchSparql('
:source_ITB_EL_CAN_2009    a               :class_source .
:source_ITB_EL_CAN_2009    rdfs:label      "EL_CAN" .
:source_ITB_EL_CAN_2009    rdfs:label      "ITB EL_CAN 2009" .
:source_ITB_EL_CAN_2009    :date           "2009"^^xsd:integer . #this is repeated since the query isnt smart enough yet
');

foreach($vtm->postgis->query("SELECT *, ST_AsText(ST_Transform(ST_Force_2D(geom),4326)) as wkt FROM sources.\"imported_EL_CAN_merged\" $filter") as $i=>$row){
   $wkt = $row['wkt'];
   $src = ':itb_canal_source_'.$i;
   //$src = ':'.uniqid();
   $vtm->batchSparql("$src      a                           :class_canal .");
   $vtm->batchSparql("$src      :belongs_to                 :source_ITB_EL_CAN_2009 .");
   $vtm->batchSparql("$src      geo:geometry                \"$wkt\"^^virtrdf:Geometry .");
   $vtm->batchSparql("$src      rdfs:label                  \"ITB canal (source)\" .");
}


/**********************************************/
/***** IMPORT THE OPENSTREETMAPS TOPONYMY *****/
/**********************************************/

$vtm->batchSparql('
:source_openstreetmaps_2014    a               :class_source .
:source_openstreetmaps_2014    rdfs:label      "Openstreetmaps" .
:source_openstreetmaps_2014    :date           "2014"^^xsd:integer .
');


//commit pending queries
$vtm->endBatchSparql();


}
catch (SparqlException $e){
    // Manage the sparql errors
    header("HTTP/1.1 500 Internal server error");
    die(  'Sparql error "'.$e->getMessage().'" on query `'.$e->getQuery().'`'  );
}/*
catch (Exception $e){
    // Manage the sparql errors
    header("HTTP/1.1 500 Internal server error");
    die(  'Unknown error "'.$e->getMessage().'"'  );
}*/