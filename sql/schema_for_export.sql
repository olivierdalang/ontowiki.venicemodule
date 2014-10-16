DROP TABLE IF EXISTS "semantic"."features" CASCADE;
CREATE TABLE "semantic"."features"(
	id SERIAL PRIMARY KEY,
	resource TEXT,
	type TEXT,
	label TEXT,
	parent TEXT
);
SELECT AddGeometryColumn('semantic','features','geom',4326,'GEOMETRY',2);

DROP VIEW IF EXISTS "semantic"."features_view_polygons" CASCADE;
CREATE VIEW "semantic"."features_view_polygons" AS
	SELECT id,resource,type,label,parent,geom as geom FROM "semantic"."features"
	WHERE GeometryType(geom)='POLYGON' OR GeometryType(geom)='MULTIPOLYGON';

DROP VIEW IF EXISTS "semantic"."features_view_linestrings" CASCADE;
CREATE VIEW "semantic"."features_view_linestrings" AS
	SELECT id,resource,type,label,parent,geom as geom FROM "semantic"."features"
	WHERE GeometryType(geom)='LINESTRING' OR GeometryType(geom)='MULTILINESTRING';

DROP VIEW IF EXISTS "semantic"."features_view_points" CASCADE;
CREATE VIEW "semantic"."features_view_points" AS
	SELECT id,resource,type,label,parent,geom as geom FROM "semantic"."features"
	WHERE GeometryType(geom)='POINT' OR GeometryType(geom)='MULTIPOINT';