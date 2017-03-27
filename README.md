# qUERY sead

Query-based Multidimensional browsing Over Relational databases. [HUMLab facetted browser]

Somewhat cleaned up version of the QSEAD PHP application.

## Swagger API install

http://swagger.io/docs/

Swagger Node tool:

https://github.com/swagger-api/swagger-node
```
npm install -g swagger
npm install -g swagger-tools
npm install -g http-server

swagger yaml file reside under api/swagger

swagger project edit        Opens swagger editor in a browser
swagger docs                Opens https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md

```

file: index.php


http://www.humlab.umu.se

This system is a faceted browsing system for the SEAD database.

Overall the systems is built using client-side javascript and html, and serverside PHP.

##Infrastructure

- Apache-webserver (http://projects.apache.org/projects/http_server.html)
- PHP7 (http://www.php.net)
- Database backend postgressql 9.x (http://www.postgresql.org/)
- PostGIS 1.4 (http://postgis.refractions.net/)
- Batik rasterizer (http://xmlgraphics.apache.org/batik/tools/rasterizer.html)
- jQuery http://jquery.com/
- REMOVED Highchart (educational use) http://www.highcharts.com/

##Postgres installation notes

- sudo apt-get install postgresql-8.4-postgis sudo su postgres createdb -p 5433 postgistemplate createlang -p 5433 plpgsql postgistemplate psql -p 5433 -d postgistemplate -f /usr/share/postgresql/8.4/contrib/postgis-1.5/postgis.sql
- psql -p 5433 -d postgistemplate -f /usr/share/postgresql/8.4/contrib/postgis-1.5/spatial_ref_sys.sql
- psql -p 5433 -d postgistemplate -c "SELECT postgis_full_version();"
- createdb -p 5433 -T postgistemplate -O ships ships
- pg_restore -p 5433 -d ships -U postgres ships.dump

##Batik rasterizer (jar-files):
- REMOVED Located in jslib/highchart/exporting_server/batik-1.7

##Directory permission notes:
- api/cache and subdirectories must have write permissionss

##Javascript libries:
Own core libraries:
- <control_bar.js>
- <facet.discrete.js>
- <facet.geo.js>
- <facet.js>
- <facet.range.js>
- <layout.js>
- <main.js>
- <result.js>
- <slot.js>
- <user.js>
- <util.js>

##Result modules javascript libraries:
- REMOVED <diagram_module.js (SHIPS)>
- <list_module.js (SHIPS)>
- <map_module.js (SHIPS)>


##PHP-scripts used in ajax requests and request:
- <load_facet.php> loads facet information for different type of facets
- <load_result.php>; loads result_information for different types of results (map, table and diagram)
- <get_data_table.php>; get the result zip-file with tab-separated data and documentation into a zip-file
- <map_download.php>; get the map as png with a world-file as well as the placenames of the relevant polygons in the  map.(parished or counties)
- <get_view_state.php>; get a view state from database to be used to recreate a view state
- <save_view_state.php>; saves a view_state into the database
- REMOVED <diagram_symbol.php>; renders a image based on color and type
- REMOVED (SEAD): <get_xy_statistics.php>; get a textfile with statistics for a point in the map

##The start-up parameters are:
view_state - which view state to start from
client_language -  which language to be used sv_SE

##Initialization sequence:
* <interface.php>  outlines  properties and heading of the html-pagee
* MOVED TO index.php: layout.php - outlines the HTML-page
* Stylesheet used: style.css
* <get_facet_definitions.php> returns facet definitions as a JavScript object
* <get_result_definitions.php> returns result definitions as a JavScript object
* <language_init.php> - returns language dictionary
* REMOVED <script_config.php> load specific js-library for a application
* loads all js-libraries

Typical facet-oriented user activities:
* Add a  facet from controlbar using <control_bar_click_callback> and also later <facet_create_facet> in <facet.js>
* Remove a facet using javascript function <facet_remove_facet> in <facet.js>
* Change ordering of facets by drag and drop  starting with function <slot_action_callback>
* Minimize the size of a facet using <facet_collapse_toggle> in <facet.js>
* Restore the size of facet using <facet_collapse_toggle> in  <facet.js>
* Make a selection in a discrete facet by clicking on row see <facet_row_clicked_callback> in <facet.js>
* Remove  a selection in a discrete facet by clicking on row see <facet_row_clicked_callback> in <facet.js>
* Make a selection in a range(interval) facet by change the lower or upper limits (via text-forms or sliders).<facet_range_changed_callback> is  called from the flash-component
* Make a selection in geo/mapfilter facet by adding a rectangle in the map-filter  calling  <facet_geo_marker_tool_click_callback> when selection rectangle is completed
* Remove a selection in the geo/mapfilter facet <facet_geo_get_marker_pair_by_marker> ,<facet_geo_points_is_within_critical_proximity> <facet_geo_destroy_marker_pair>
* Scroll in a discrete facet and when the client cache data does need to be populate <facet_load_data> is called in <facet.js>

General result-oriented user activities:
* Maximize the result area to make the area bigger <result_maximize>
* Restore the result area to fit it into the whole webpage (only when it is maximized)
* Activate the map view using <result_switch_view> with the "map" as a argument and later <result_render_view_map>
* Activate the diagram view <result_switch_view> with the "diagram" as a argument and later on <result_render_view_diagram>
* Activate the list view <result_switch_view> with the "list" as a argument and later <result_render_view_list>
* Change aggregation level for the result view (SHIPS) which triggeres <result_switch_view> and loads new data and renders the view
* Add  a result variable for the result views triggering <result_switch_view>  and loads new data and renders the view
* Remove a result variable for the result views triggering <result_switch_view>  and loads new data and renders the view

Result list oriented user activities:
* Download zip-file with data and documentation by calling <get_data_table.php>

Result map oriented user activities (SHIPS):
* Select active variable for the thematic visualisation. Triggering <result_load_data>
* Set the year that the map should show using a time-bar. Triggering <result_map_time_bar_changed_callback> and later <result_load_data>
* Zoom and pan in the map.
* Download the thematic visualisation-layer with a historic background map using <map_download.php> with use_historic=true as PNG-image
* Download the thematic visualisation-layer   using <map_download.php>  as PNG-image
* Download coordinate file for map layer with a historic background map , via a link to a file on the server (pgw-file)
* Download coordinate file for map layer without background map via a link to a file on the server (pgw-file))
* Download legend as a image by calling <map_legend_image.php>

The client is mainly sending request to server using
* <facet_load_data> in <facet.js> for facet content (discrete values, intervals and geo-information)
* <result_load_data> in <result.js> for content into the result area (map, diagram and "list-like" information"
